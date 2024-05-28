<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use InvalidArgumentException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Documents
{
    public function __construct(
        private readonly MeiliSearch $meiliSearch,
        private int $jsonEncodeOptions = 0,
    ) {
    }

    public function setJsonEncodeOptions(int $jsonEncodeOptions): void
    {
        $this->jsonEncodeOptions = $jsonEncodeOptions;
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
        return $this->meiliSearch->post('/indexes/'.$index.'/documents/fetch', [
            'json' => $parameters,
        ]);
    }

    /** @see fetchAsync */
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
        return $this->meiliSearch->get('/indexes/'.$index.'/documents/'.$document, [
            'query' => $parameters,
        ]);
    }

    /** @see getAsync */
    public function get(string $index, string $document, array $parameters = []): array
    {
        return $this->getAsync($index, $document, $parameters)->toArray();
    }

    /** @var array<string, Batch> */
    private array $persisted = [];

    public function batchPersist(string $index, object|iterable $documents = [], string $primaryKey = 'id', array $normalizationContext = []): void
    {
        $index = $this->meiliSearch->index($index);

        $documents = is_iterable($documents)
            ? $documents
            : [$documents];

        foreach ($documents as $document) {
            if (is_object($document)) {
                $document = $this->meiliSearch->normalize($document, array_replace_recursive($normalizationContext, [
                    'index' => $index,
                ]));
            }

            if (!isset($document[$primaryKey])) {
                throw new InvalidArgumentException(sprintf(
                    'Document is missing the primary key property "%s" for index "%s" after normalization, either add an "id" column or specify it using the "primaryKey" parameter.',
                    $primaryKey,
                    $index->id,
                ));
            }

            if (!isset($this->persisted[$index->id])) {
                $this->persisted[$index->id] = new Batch($index, $primaryKey);
            }

            $key = (string)$document[$primaryKey];
            $this->persisted[$index->id]->documents[$key] = json_encode($document, $this->jsonEncodeOptions + JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @return ResponseInterface[]
     */
    public function batchFlush(): array
    {
        $items = [];

        $responses = [];
        foreach ($this->persisted as $index => $batch) {
            $responses[$index] = $this->meiliSearch->post('/indexes/'.$index.'/documents', [
                'headers' => [
                    'content-type' => 'application/x-ndjson',
                ],
                'query' => ['primaryKey' => $batch->primaryKey],
                'body' => implode(PHP_EOL, $batch->documents),
            ]);
        }

        $resolved = array_map(fn (ResponseInterface $response) => $response->toArray(), $responses);

        $this->persisted = [];

        return $resolved;
    }
}
