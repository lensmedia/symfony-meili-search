<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Index;

use JetBrains\PhpStorm\ArrayShape;

/**
 * Defines the settings for the MeiliSearch indexes matching the class.
 */
interface IndexSettingsInterface
{
    /**
     * If you return an array from this method, it will be used to synchronize the index settings
     * based on these values when called.
     *
     * @see https://www.meilisearch.com/docs/reference/api/settings
     */
    #[ArrayShape([
        'displayedAttributes' => 'string[]', // default: ['*']
        'searchableAttributes' => 'string[]', // default: ['*']
        'filterableAttributes' => 'string[]', // default: []
        'sortableAttributes' => 'string[]', // default: []
        'rankingRules' => 'string[]', // default: ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness']
        'stopWords' => 'string[]', // default: []
        'nonSeparatorTokens' => 'string[]', // default: []
        'separatorTokens' => 'string[]', // default: []
        'dictionary' => 'string[]', // default: []
        'synonyms' => 'string[]', // default: []
        'distinctAttribute' => 'string', // default: null
        'typoTolerance' => [
            'enabled' => true,
            'minWordSizeForTypos' => [
                'oneTypo' => 'int', // default: 5
                'twoTypos' => 'int', // default: 9
            ],
            'disableOnWords' => 'string[]', // default: []
            'disableOnAttributes' => 'string[]', // default: []
        ],
        'faceting' => [
            'maxValuesPerFacet' => 'int', // default: 100
        ],
        'pagination' => [
            'maxTotalHits' => 'int', // default: 1000
        ],
        'proximityPrecision' => 'string', // default: 'byWord' (byWord, byAttribute)
        'searchCutoffMs' => 'int', // default: null (meilisearch defaults to 1500ms)
    ])]
    public function settings(Index $index): array;
}
