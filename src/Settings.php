<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use Lens\Bundle\MeiliSearchBundle\Index\Index;
use Symfony\Contracts\HttpClient\ResponseInterface;

readonly class Settings
{
    public function __construct(
        private MeiliSearch $meiliSearch,
    ) {
    }

    public function getAsync(string $index): ResponseInterface
    {
        return $this->meiliSearch->get($this->uri($index));
    }

    public function get(string $index): array
    {
        return $this->getAsync($index)->toArray();
    }

    public function updateAsync(string $index, array $settings = []): ResponseInterface
    {
        return $this->meiliSearch->patch($this->uri($index), [
            'json' => $settings,
        ]);
    }

    public function update(string $index, array $settings = []): array
    {
        return $this->updateAsync($index, $settings)->toArray();
    }

    /**
     * Reset all settings to the default, remote MeiliSearch (not repository), settings.
     */
    public function resetAsync(string $index): ResponseInterface
    {
        return $this->meiliSearch->delete($this->uri($index));
    }

    /**
     * Reset all settings to the default, remote MeiliSearch (not repository), settings.
     */
    public function reset(string $index): array
    {
        return $this->resetAsync($index)->toArray();
    }

    /**
     * Loads the settings from the repository and updates it for the index.
     */
    public function synchronizeAsync(string $index, array $settings = []): ResponseInterface
    {
        $index = $this->meiliSearch->index($index);
        $repository = $this->meiliSearch->repository($index->id);

        $settings = array_replace_recursive($repository->settings($index), $settings);

        return $this->updateAsync($index->id, $settings);
    }

    /**
     * Loads the settings from the repository and updates it for the index.
     */
    public function synchronize(string $index, array $settings = []): array
    {
        return $this->synchronizeAsync($index, $settings)->toArray();
    }

    /**
     * @return ResponseInterface[]
     */
    public function synchronizeAllAsync(array $settings = []): array
    {
        $requests = [];

        /** @var Index $index */
        foreach ($this->meiliSearch as $index) {
            $requests[] = $this->synchronizeAsync($index->id, $settings);
        }

        return $requests;
    }

    public function synchronizeAll(): array
    {
        return array_map(
            static fn (ResponseInterface $response) => $response->toArray(),
            $this->synchronizeAllAsync(),
        );
    }

    private function uri(string $index): string
    {
        return '/indexes/'.$index.'/settings';
    }
}
