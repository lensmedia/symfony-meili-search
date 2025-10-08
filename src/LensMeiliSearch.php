<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use Doctrine\Persistence\Proxy;
use InvalidArgumentException;
use Lens\Bundle\MeiliSearchBundle\Attribute\Index;
use LogicException;
use Meilisearch\Client;
use Meilisearch\Contracts\IndexesQuery;
use Meilisearch\Contracts\IndexesResults;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Exceptions\TimeOutException;
use Meilisearch\Search\SearchResult;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class LensMeiliSearch
{
    public const DEFAULT_CLIENT = 'default';

    /** @var Client[] */
    private array $clients = [];

    /** @var LoadedIndex[] */
    private array $indexes = [];

    /** @var LensMeiliSearchDocumentLoaderInterface[] */
    private array $documentLoaders = [];

    public function __construct(
        private readonly ClientInterface $httpClient,
        array $clients,
        private array $groups,
    ) {
        foreach ($clients as $name => $client) {
            $this->addClient($name, $client['url'], $client['key']);
        }
    }

    public function initializeIndexLoaders(iterable $indexLoaders): void
    {
        foreach ($indexLoaders as $indexLoader) {
            if (!($indexLoader instanceof LensMeiliSearchIndexLoaderInterface)) {
                throw new InvalidArgumentException(sprintf(
                    'Index loader "%s" must implement %s.',
                    get_debug_type($indexLoader),
                    LensMeiliSearchIndexLoaderInterface::class,
                ));
            }

            foreach ($indexLoader->getIndexes() as $index) {
                if (isset($this->indexes[$index->uid])) {
                    throw new LogicException(sprintf('Duplicate index "%s" detected', $index->uid));
                }

                $this->indexes[$index->uid] = new LoadedIndex(
                    $index,
                    $this->client($index->client),
                    context: $index->context,
                );
            }
        }
    }

    /**
     * @return Index[]
     */
    public function configuredIndexes(string $filter = '*', int $filterFlags = 0): array
    {
        $results = [];

        foreach ($this->indexes as $index) {
            if ('*' !== $filter && !fnmatch($filter, $index->config->uid, $filterFlags)) {
                continue;
            }

            if (!($index instanceof LoadedIndex)) {
                throw new InvalidArgumentException(sprintf(
                    'Index loader "%s" must return an array of %s instances.',
                    get_debug_type($index),
                    Index::class,
                ));
            }

            $results[$index->config->uid] = $index->config;
        }

        return array_merge($results);
    }

    public function registerDocumentLoaders(iterable $documentLoaders): void
    {
        foreach ($documentLoaders as $documentLoader) {
            if (!($documentLoader instanceof LensMeiliSearchDocumentLoaderInterface)) {
                throw new InvalidArgumentException(sprintf(
                    'Document loader "%s" must implement %s.',
                    get_debug_type($documentLoader),
                    LensMeiliSearchDocumentLoaderInterface::class,
                ));
            }

            foreach ($documentLoader->supports() as $class) {
                if (!class_exists($class)) {
                    throw new InvalidArgumentException(sprintf(
                        '%s::supports returns classname "%s" which is not a valid class.',
                        $documentLoader::class,
                        $class,
                    ));
                }

                if (isset($this->documentLoaders[$class])) {
                    throw new InvalidArgumentException(sprintf(
                        'Document loader for class "%s" is already defined.',
                        $class,
                    ));
                }

                $this->documentLoaders[$class] = $documentLoader;
            }
        }
    }

    public function addClient(string $name, string $url, string $key): void
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Client name cannot be empty.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf('Client "%s" has an invalid URL.', $name));
        }

        if ('' === $key) {
            throw new InvalidArgumentException(sprintf('Client "%s" has an empty key.', $name));
        }

        if ($this->hasClient($name)) {
            throw new InvalidArgumentException(sprintf('Client "%s" is already configured.', $name));
        }

        $this->clients[$name] = new Client($url, $key, $this->httpClient);
    }

    public function client(Client|string $name): Client
    {
        if (is_string($name)) {
            if (!$this->hasClient($name)) {
                throw new InvalidArgumentException(sprintf('Client "%s" is not configured.', $name));
            }

            return $this->clients[$name];
        }

        return $name;
    }

    public function clientForIndex(string $uid): Client
    {
        $this->checkIndex($uid);

        return $this->indexes[$uid]->client;
    }

    /**
     * Simulates a remote index without having to fetch it first, useful for updates so you do not do multiple queries.
     *
     * Unlike getIndex, this will not create the remote index if it does not exist and should only be used when you know
     * the remote index exits.
     */
    public function index(string $uid): Indexes
    {
        $this->checkIndex($uid);

        $index = $this->indexes[$uid];

        return $index->remote ?? $this->client($index->config->client)->index($index->config->uid);
    }

    /**
     * Get existing index by name or create a new one if it does not exist and client is provided.
     */
    public function getIndex(string $uid): Indexes
    {
        $this->checkIndex($uid);

        $index = $this->indexes[$uid];
        $index->remote ??= $this->obtainRemoteIndex($index->config);

        return $index->remote;
    }

    /**
     * Returns the remote index. If an index does not exist on remote, it is created with the configured settings.
     *
     * @param bool $updateExistingIndexSettings Update settings for existing indexes (use for synchronization).
     *
     * @throws \Meilisearch\Exceptions\ApiException When the index does not exist and cannot be created.
     */
    public function obtainRemoteIndex(Index $index, bool $updateExistingIndexSettings = false, int $timeoutInMs = 5000, int $intervalInMs = 50): Indexes
    {
        $client = $this->client($index->client);

        $uid = $index->uid;

        try {
            $remoteIndex = $client->getIndex($uid);
            if ($updateExistingIndexSettings) {
                $remoteIndex->updateSettings($this->settings($index->uid));
            }

            return $remoteIndex;
        } catch (ApiException $exception) {
            if (404 !== $exception->getCode()) {
                throw $exception;
            }
        }

        $createIndex = $client->createIndex($uid, [
            'primaryKey' => $index->primaryKey,
        ]);

        if (isset($createIndex['taskUid'])) {
            $task = $client->waitForTask($createIndex['taskUid'], $timeoutInMs, $intervalInMs);

            if (isset($task['error'])) {
                throw new RuntimeException($task['error']['message'] ?? 'Task failed to complete.');
            }
        }

        $this->updateSettings($uid);

        return $client->getIndex($uid);
    }

    public function config(string $uid): Index
    {
        $this->checkIndex($uid);

        return $this->indexes[$uid]->config;
    }

    /**
     * Returns the configured settings for an index.
     *
     * @see https://www.meilisearch.com/docs/reference/api/settings#settings-interface
     */
    public function settings(string $uid): object
    {
        $this->checkIndex($uid);

        // Make sure there is always one setting, otherwise JSON encode in meilisearch/meilisearch-php makes
        // an array instead of object when the array is empty ("use defaults for all").
        return (object)$this->config($uid)->settings;
    }

    /**
     * Update settings to the configured settings from the index
     */
    public function updateSettings(string $uid): array
    {
        return $this->index($uid)->updateSettings($this->settings($uid));
    }

    /**
     * @throws \Meilisearch\Exceptions\TimeOutException if task tames too long
     */
    public function addDocuments(string $uid, iterable $documents, array $context = [], int $taskTimeoutInMs = 5000, int $taskIntervalInMs = 50): array
    {
        $entries = [];

        $context = array_replace_recursive(
            $this->config($uid)->context, // Configured context (Index annotation)
            ['index' => $uid], // Forcing index in context
            $context, // Provided context
        );

        foreach ($documents as $document) {
            if (!($document instanceof Document)) {
                $document = $this->toDocument($document, $context);
            }

            $config = $this->config($uid);

            $data = $document->data;
            $primaryKey = $config->primaryKey ?? 'id';
            if (!isset($data[$primaryKey])) {
                throw new LogicException(sprintf(
                    'Document data for index "%s" does not have the configured primary key property (%s), make sure to return it in the document data.',
                    $uid,
                    $primaryKey,
                ));
            }

            $entries[] = $data;
        }

        $task = $this->index($uid)->addDocuments($entries);
        if (isset($task['taskUid'])) {
            try {
                return $this->index($uid)->waitForTask($task['taskUid'], $taskTimeoutInMs, $taskIntervalInMs);
            } catch (TimeOutException) {
                return $task;
            }
        }

        return $task;
    }

    public function toDocument(object $data, array $context = []): Document
    {
        $class = $data::class;
        if (is_a($data, Proxy::class, true)) {
            $class = get_parent_class($data);
        }

        if (!isset($this->documentLoaders[$class])) {
            throw new InvalidArgumentException(sprintf(
                'Class "%s" does not seem to have a configured loader.',
                $class,
            ));
        }

        return $this->documentLoaders[$class]->toDocument($data, $context);
    }

    public function getRemoteIndexes(Client|string $client, ?IndexesQuery $options = null): IndexesResults
    {
        $options ??= new IndexesQuery();
        $options->setLimit(99999);

        return $this->client($client)->getIndexes($options);
    }

    public function getAllRemoteIndexes(?IndexesQuery $options = null): array
    {
        return array_map(
            fn ($client) => $this->getRemoteIndexes($client, $options),
            $this->clients,
        );
    }

    public function deleteIndex(string $uid): array
    {
        $response = $this->getIndex($uid)->delete();

        unset($this->indexes[$uid]);

        return $response;
    }

    public function deleteRemoteIndex(Client|string $client, string $uid): array
    {
        return $this->client($client)->deleteIndex($uid);
    }

    public function search(string $uid, ?string $query, array $searchParams = [], array $options = []): SearchResult
    {
        return $this->getIndex($uid)->search($query, $searchParams, ['raw' => false] + $options);
    }

    /**
     * @param string|array $groups Group name or array of index names to search.
     *
     * @return SearchResult[]
     */
    public function groupSearch(string|array $groups, ?string $query, array $searchParams = [], array $options = []): array
    {
        if (is_string($groups)) {
            $groups = $this->groupIndexes($groups);
        }

        dd($groups);
    }

    private function hasClient(string $name): bool
    {
        return isset($this->clients[$name]);
    }

    private function hasIndex(string $uid): bool
    {
        return isset($this->indexes[$uid]);
    }

    private function checkIndex(string $uid): void
    {
        if (!$this->hasIndex($uid)) {
            throw new InvalidArgumentException(sprintf(
                'Index "%s" is not configured, check your loaders.',
                $uid,
            ));
        }
    }

    private function groupIndexes(string $group): array
    {
        if (!$this->hasGroup($group)) {
            throw new InvalidArgumentException(sprintf('Group "%s" is not configured.', $group));
        }

        return $this->groups[$group];
    }

    private function hasGroup(string $name): bool
    {
        return isset($this->groups[$name]);
    }

    /**
     * Get class name from object, even if it is a doctrine proxy.
     */
    private function class(object $class): string
    {
        if ($class instanceof Proxy) {
            $className = get_class($class);
            $pos = strrpos($className, '\\' . Proxy::MARKER . '\\');

            if ($pos !== false) {
                return substr($className, $pos + Proxy::MARKER_LENGTH + 2);
            }
        }

        return get_class($class);
    }
}

