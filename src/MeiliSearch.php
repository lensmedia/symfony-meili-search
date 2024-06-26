<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use JsonException;
use JsonSerializable;
use Lens\Bundle\MeiliSearchBundle\Exception\GroupNotFound;
use Lens\Bundle\MeiliSearchBundle\Exception\IndexNotFound;
use Lens\Bundle\MeiliSearchBundle\Exception\NormalizationFailed;
use Lens\Bundle\MeiliSearchBundle\Exception\NormalizerNotFound;
use Lens\Bundle\MeiliSearchBundle\Index\Index;
use Lens\Bundle\MeiliSearchBundle\Index\IndexCollectionTrait;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

use const JSON_THROW_ON_ERROR;

class MeiliSearch implements MeiliSearchInterface, MeiliSearchNormalizerInterface
{
    use IndexCollectionTrait;

    public const DEFAULT_OPTIONS = [
        'indexes' => [
            'prefix' => '',
            'suffix' => '',
        ],
        'json_encode_options' => 0,
    ];

    private readonly HttpClientInterface $httpClient;
    private readonly bool $isAdmin;

    public readonly Settings $settings;
    public readonly Documents $documents;
    public readonly Indexes $indexes;
    public readonly array $options;

    public function __construct(
        HttpClientInterface $httpClient,
        /** @param MeiliSearchNormalizerInterface[] $normalizers */
        private readonly iterable $normalizers,
        private readonly array $groups,
        string $uri,
        #[SensitiveParameter]
        string $searchKey,
        #[SensitiveParameter]
        ?string $adminKey = null,
        array $options = self::DEFAULT_OPTIONS,
    ) {
        $this->options = array_replace_recursive(self::DEFAULT_OPTIONS, $options);

        $this->httpClient = $httpClient->withOptions([
            'base_uri' => $uri,
            'auth_bearer' => $adminKey ?? $searchKey,
        ]);

        $this->isAdmin = (null !== $adminKey);
        $this->settings = new Settings($this);
        $this->indexes = new Indexes($this);
        $this->documents = new Documents($this);
    }

    public function searchAsync(SearchParameters $parameters): ResponseInterface
    {
        $id = $parameters->indexUid;

        if (!$this->isManagedIndex($id)) {
            throw new IndexNotFound($id);
        }

        return $this->post('/indexes/'.$this->addIndexAffixes($id).'/search', [
            'json' => (array)$parameters,
        ]);
    }

    public function search(SearchParameters $parameters): array
    {
        return $this->searchAsync($parameters)->toArray();
    }

    public function multiSearchAsync(array $searchParameters): ResponseInterface
    {
        array_walk($searchParameters, static fn (SearchParameters $parameters) => true);

        $queries = [];
        foreach ($searchParameters as $searchParameter) {
            $id = $searchParameter->indexUid;

            if (!$this->isManagedIndex($id)) {
                throw new IndexNotFound($id);
            }

            $query = array_filter((array)$searchParameter, static fn ($value) => null !== $value);
            $query['indexUid'] = $this->addIndexAffixes($id);
            $queries[] = $query;
        }

        return $this->post('/multi-search', [
            'json' => ['queries' => $queries],
        ]);
    }

    public function multiSearch(array $searchParameters): array
    {
        $results = $this->multiSearchAsync($searchParameters)->toArray();

        return $results['results'] ?? [];
    }

    public function groupSearchAsync(string $group, SearchParameters $searchParameters): ResponseInterface
    {
        if (empty($this->groups[$group])) {
            throw new GroupNotFound($group);
        }

        $group = $this->groups[$group];

        $queries = [];
        foreach ($group as $index) {
            $queries[] = $indexParameters = clone $searchParameters;
            $indexParameters->indexUid = $index;
        }

        return $this->multiSearchAsync($queries);
    }

    public function groupSearch(string $group, SearchParameters $searchParameters, bool $mergeResults = false): array
    {
        $results = $this->groupSearchAsync($group, $searchParameters)->toArray();

        if ($mergeResults) {
            return $this->mergeSearchResults($results);
        }

        return $results['results'] ?? [];
    }

    public function mergeSearchResults(array $results): array
    {
        $output = [];
        foreach ($results['results'] as $result) {
            if (empty($result['indexUid'])) {
                throw new RuntimeException('IndexUid is missing in the result.');
            }

            foreach ($result['hits'] as $hit) {
                $output[] = array_merge($hit, [
                    '_index' => $result['indexUid'],
                ]);
            }
        }

        usort($output, static fn ($a, $b) => $b['_rankingScore'] <=> $a['_rankingScore']);

        return $output;
    }

    public function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->httpClient->request(Request::METHOD_GET, $uri, $options);
    }

    public function post(string $uri, array $options = []): ResponseInterface
    {
        return $this->httpClient->request(Request::METHOD_POST, $uri, $options);
    }

    public function patch(string $uri, array $options = []): ResponseInterface
    {
        return $this->httpClient->request(Request::METHOD_PATCH, $uri, $options);
    }

    public function put(string $uri, array $options = []): ResponseInterface
    {
        return $this->httpClient->request(Request::METHOD_PUT, $uri, $options);
    }

    public function delete(string $uri, array $options = []): ResponseInterface
    {
        return $this->httpClient->request(Request::METHOD_DELETE, $uri, $options);
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function normalize(object $object, array $context): array
    {
        try {
            foreach ($this->normalizers as $normalizer) {
                if ($normalizer->supportsNormalization($object, $context)) {
                    return $normalizer->normalize($object, $context);
                }
            }

            if ($object instanceof JsonSerializable) {
                return $object->jsonSerialize();
            }

            if (method_exists($object, '__serialize')) {
                return $object->__serialize();
            }
        } catch (Throwable $e) {
            throw new NormalizationFailed($object, $e);
        }

        throw new NormalizerNotFound($object);
    }

    public function supportsNormalization(object $object, array $context): bool
    {
        return true;
    }

    /**
     * @throws JsonException
     */
    public function json(mixed $data, int $options = JSON_THROW_ON_ERROR): string
    {
        $options |= $this->options['normalization']['json_encode_options'] ?? 0;

        return json_encode($data, $options);
    }

    public function addIndexAffixes(Index|string $index): string
    {
        if ($index instanceof Index) {
            $index = $index->id;
        }

        return $this->options['indexes']['prefix'].$index.$this->options['indexes']['suffix'];
    }

    public function removeIndexAffixes(string $index): string
    {
        $prefix = $this->options['indexes']['prefix'];
        if ($prefix && str_starts_with($index, $prefix)) {
            $index = substr($index, strlen($prefix));
        }

        $suffix = $this->options['indexes']['suffix'];
        if ($suffix && str_ends_with($index, $suffix)) {
            $index = substr($index, 0, -strlen($suffix));
        }

        return $index;
    }
}
