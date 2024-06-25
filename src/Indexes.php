<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use Lens\Bundle\MeiliSearchBundle\Index\Index;
use Symfony\Contracts\HttpClient\ResponseInterface;

readonly class Indexes
{
    public const DEFAULT_OPTIONS = [
        'prefix' => '',
        'suffix' => '',
    ];

    private array $options;

    public function __construct(
        private MeiliSearch $meiliSearch,
        array $options = [],
    ) {
        $this->options = array_replace_recursive(self::DEFAULT_OPTIONS, $options);
    }

    public function all(): array
    {
        return $this->meiliSearch->get($this->uri())->toArray();
    }

    public function get(string $index): array
    {
        return $this->meiliSearch->get($this->uri($index))->toArray();
    }

    /**
     * Settings:
     * primaryKey   String / null   null    Primary key of the requested index
     *
     * @see https://www.meilisearch.com/docs/reference/api/indexes#create-an-index
     */
    public function createAsync(string $index, array $settings = []): ResponseInterface
    {
        $settings = array_merge($settings, ['uid' => $index]);

        return $this->meiliSearch->post($this->uri(), [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'body' => $this->meiliSearch->json($settings),
        ]);
    }

    public function create(string $index, array $settings = []): array
    {
        return $this->createAsync($index, $settings)->toArray();
    }

    /**
     * Settings:
     * primaryKey *    String / null     N/A    Primary key of the requested index
     *
     * @see https://www.meilisearch.com/docs/reference/api/indexes#update-an-index
     */
    public function updateAsync(string $index, array $settings = []): ResponseInterface
    {
        return $this->meiliSearch->patch($this->uri($index), [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'body' => $this->meiliSearch->json($settings),
        ]);
    }

    /** @see updateAsync */
    public function update(string $index, array $settings = []): array
    {
        return $this->updateAsync($index, $settings)->toArray();
    }

    public function deleteAsync(string $index): ResponseInterface
    {
        return $this->meiliSearch->delete($this->uri($index));
    }

    public function delete(string $index): array
    {
        return $this->deleteAsync($index)->toArray();
    }

    private function uri(Index|string|null $index = null, ?string $suffix = null): string
    {
        if ($index instanceof Index) {
            $index = $index->id;
        }

        if ($index) {
            $index = $this->options['prefix'].$index.$this->options['suffix'];
        }

        return '/indexes'.($index ? '/'.$index : '').($suffix ? '/'.ltrim($suffix, '/') : '');
    }
}
