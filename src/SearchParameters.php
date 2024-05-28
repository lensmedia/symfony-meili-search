<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

class SearchParameters
{
    /** @var string|null Uid of the requested index (default: N/A) */
    public ?string $indexUid = null;

    /** @var string Query string (required) */
    public string $q;

    /** @var int|null Number of documents to skip (default: 0) */
    public ?int $offset = null;

    /** @var int|null Maximum number of documents returned (default: 20) */
    public ?int $limit = null;

    /** @var int|null Maximum number of documents returned for a page (default: 1) */
    public ?int $hitsPerPage = null;

    /** @var int|null Request a specific page of results (default: 1) */
    public ?int $page = null;

    /** @var int|null Filter queries by an attribute's value (default: null) */
    public ?int $filter = null;

    /** @var string[]|null Display the count of matches per facet (default: null) */
    public ?array $facets = null;

    /** @var string[]|null Attributes to display in the returned documents (default: ["*"]) */
    public ?array $attributesToRetrieve = null;

    /** @var string[]|null Attributes whose values have to be cropped (default: null) */
    public ?array $attributesToCrop = null;

    /** @var int|null Maximum length of cropped value in words (default: 10) */
    public ?int $cropLength = null;

    /** @var string|null String marking crop boundaries (default: "…") */
    public ?string $cropMarker = null;

    /** @var string[]|null Highlight matching terms contained in an attribute (default: null) */
    public ?array $attributesToHighlight = null;

    /** @var string|null String inserted at the start of a highlighted term (default: "<em>") */
    public ?string $highlightPreTag = null;

    /** @var string|null String inserted at the end of a highlighted term (default: "</em>") */
    public ?string $highlightPostTag = null;

    /** @var bool|null Return matching terms location (default: false) */
    public ?bool $showMatchesPosition = null;

    /** @var string[]|null Sort search results by an attribute's value (default: null) */
    public ?array $sort = null;

    /** @var string|null Strategy used to match query terms within documents (default: last) */
    public ?string $matchingStrategy = null;

    /** @var bool|null Display the global ranking score of a document (default: ) */
    public bool $showRankingScore = true;

    /** @var string[]|null Restrict search to the specified attributes (default: ["*"]) */
    public ?array $attributesToSearchOn = null;

    private function __construct()
    {
    }

    public static function create(string $query, ?string $index = null, array $options = []): self
    {
        $instance = new self();

        $instance->indexUid = $index;
        $instance->q = $query;
        $instance->offset = $options['offset'] ?? null;
        $instance->limit = $options['limit'] ?? null;
        $instance->hitsPerPage = $options['hitsPerPage'] ?? null;
        $instance->page = $options['page'] ?? null;
        $instance->filter = $options['filter'] ?? null;
        $instance->facets = $options['facets'] ?? null;
        $instance->attributesToRetrieve = $options['attributesToRetrieve'] ?? null;
        $instance->attributesToCrop = $options['attributesToCrop'] ?? null;
        $instance->cropLength = $options['cropLength'] ?? null;
        $instance->cropMarker = $options['cropMarker'] ?? null;
        $instance->attributesToHighlight = $options['attributesToHighlight'] ?? null;
        $instance->highlightPreTag = $options['highlightPreTag'] ?? null;
        $instance->highlightPostTag = $options['highlightPostTag'] ?? null;
        $instance->showMatchesPosition = $options['showMatchesPosition'] ?? null;
        $instance->sort = $options['sort'] ?? null;
        $instance->matchingStrategy = $options['matchingStrategy'] ?? null;
        $instance->showRankingScore = $options['showRankingScore'] ?? true;
        $instance->attributesToSearchOn = $options['attributesToSearchOn'] ?? null;

        return $instance;
    }
}
