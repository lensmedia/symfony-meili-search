<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use InvalidArgumentException;
use Lens\Bundle\MeiliSearchBundle\Index\Index;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Documents
{
    public function __construct(
        private readonly MeiliSearch $meiliSearch,
    ) {
    }

    /**
     * Parameters:
     * offset	Integer	0	Number of documents to skip
     * limit	Integer	20	Number of documents to return
     * fields	Array of strings/null	*	Document attributes to show (case-sensitive, comma-separated)
     * filter	String/Array of array of strings/null	N/A	Refine results based on attributes in the filterableAttributes list
     *
     * @see https://www.meilisearch.com/docs/reference/api/documents#get-documents-with-post
     *
     * @param array{ offset: int, limit: int, fields: string[], filter: (string|null)[] } $parameters
     */
    public function fetchAsync(string $index, array $parameters = []): ResponseInterface
    {
        return $this->meiliSearch->post($this->uri($index, '/fetch'), [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'body' => empty($parameters)
                ? '{}'
                : $this->meiliSearch->json($parameters),
        ]);
    }

    public function fetch(string $index, array $parameters = []): array
    {
        return $this->fetchAsync($index, $parameters)->toArray();
    }

    /**
     * Parameters:
     * fields	*	Document attributes to show (case-sensitive, comma-separated)
     *
     * @see https://www.meilisearch.com/docs/reference/api/documents#get-one-document
     *
     * @param array{ fields: string } $parameters
     */
    public function getAsync(string $index, string $document, array $parameters = []): ResponseInterface
    {
        return $this->meiliSearch->get($this->uri($index, $document), [
            'query' => $parameters,
        ]);
    }

    /** @see getAsync */
    public function get(string $index, string $document, array $parameters = []): array
    {
        return $this->getAsync($index, $document, $parameters)->toArray();
    }

    /**
     * @see https://www.meilisearch.com/docs/reference/api/documents#add-or-replace-documents
     */
    public function addAsync(string $index, object|array $document, array $normalizationContext = []): ResponseInterface
    {
        $index = $this->meiliSearch->index($index);
        $primaryKey = $index->primaryKey;

        $document = $this->handleObjectArgument($index, $document, $normalizationContext);

        return $this->meiliSearch->post($this->uri($index), [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'query' => ['primaryKey' => $primaryKey],
            'body' => $this->meiliSearch->json($document),
        ]);
    }

    /** @see addAsync */
    public function add(string $index, object|array $document, array $normalizationContext = []): array
    {
        return $this->addAsync($index, $document, $normalizationContext)->toArray();
    }

    /**
     * @see https://www.meilisearch.com/docs/reference/api/documents#add-or-update-documents
     */
    public function updateAsync(string $index, object|array $document, array $normalizationContext = []): ResponseInterface
    {
        $index = $this->meiliSearch->index($index);
        $primaryKey = $index->primaryKey;

        $document = $this->handleObjectArgument($index, $document, $normalizationContext);

        return $this->meiliSearch->put($this->uri($index, $document[$primaryKey]), [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'query' => ['primaryKey' => $primaryKey],
            'body' => $this->meiliSearch->json($document),
        ]);
    }

    /** @see updateAsync */
    public function update(string $index, object|array $document, array $normalizationContext = []): array
    {
        return $this->updateAsync($index, $document, $normalizationContext)->toArray();
    }

    public function deleteAsync(string $index, string|int $document): ResponseInterface
    {
        return $this->meiliSearch->delete($this->uri($index, $document));
    }

    public function delete(string $index, string|int $document): array
    {
        return $this->deleteAsync($index, $document)->toArray();
    }

    public function deleteBatchAsync(string $index, array $ids): ResponseInterface
    {
        if (empty($ids)) {
            throw new InvalidArgumentException('No document IDs provided to delete.');
        }

        return $this->meiliSearch->post($this->uri($index, '/delete-batch'), [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'body' => $this->meiliSearch->json($ids),
        ]);
    }

    public function deleteBatch(string $index, array $ids): array
    {
        return $this->deleteBatchAsync($index, $ids)->toArray();
    }

    public function clearAsync(string $index): ResponseInterface
    {
        return $this->meiliSearch->delete($this->uri($index));
    }

    public function clear(string $index): array
    {
        return $this->clearAsync($index)->toArray();
    }

    /** @var array<string, Batch> */
    private array $persisted = [];

    public function batchPersist(string $index, object|iterable $documents, array $normalizationContext = []): void
    {
        $index = $this->meiliSearch->index($index);
        $primaryKey = $index->primaryKey;

        $documents = is_iterable($documents)
            ? $documents
            : [$documents];

        foreach ($documents as $document) {
            $document = $this->handleObjectArgument($index, $document, $normalizationContext);

            if (!isset($this->persisted[$index->id])) {
                $this->persisted[$index->id] = new Batch($index);
            }

            $key = (string)$document[$primaryKey];
            $this->persisted[$index->id]->documents[$key] = $this->meiliSearch->json($document, ~JSON_PRETTY_PRINT);
        }
    }

    /**
     * @return ResponseInterface[]
     */
    public function batchFlush(): array
    {
        if (empty($this->persisted)) {
            return [];
        }

        $responses = [];
        foreach ($this->persisted as $index => $batch) {
            $responses[$index] = $this->meiliSearch->post($this->uri($index), [
                'headers' => [
                    'content-type' => 'application/x-ndjson',
                ],
                'query' => ['primaryKey' => $batch->index->primaryKey],
                'body' => implode(PHP_EOL, $batch->documents),
            ]);
        }

        $resolved = array_map(static fn (ResponseInterface $response) => $response->toArray(), $responses);

        $this->persisted = [];

        return $resolved;
    }

    private function handleObjectArgument(Index $index, object|array $document, array $normalizationContext): array
    {
        if (is_object($document)) {
            $document = $this->meiliSearch->normalize($document, array_replace_recursive($index->context, $normalizationContext, [
                'index' => $index,
            ]));
        }

        if (!isset($document[$index->primaryKey])) {
            throw new InvalidArgumentException(sprintf(
                'Document is missing the primary key property "%s" for index "%s" after normalization. If your object\'s primary key is not "id" you can define it on the repository index.',
                $index->primaryKey,
                $index->id,
            ));
        }

        return $document;
    }

    private function uri(Index|string $index, ?string $suffix = null): string
    {
        if ($index instanceof Index) {
            $index = $index->id;
        }

        return '/indexes/'.$index.'/documents'.($suffix ? '/'.ltrim($suffix, '/') : '');
    }
}
