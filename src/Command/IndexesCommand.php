<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Command;

use Lens\Bundle\MeiliSearchBundle\MeiliSearch;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: self::NAME,
    description: 'Dump the indexes references.',
)]
class IndexesCommand extends Command
{
    public const NAME = 'lens:meili-search:indexes';
    public function __construct(
        private readonly MeiliSearch $meiliSearch,
    ) {
        parent::__construct(self::NAME);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Dumping indexes references');

        $prefix = $this->meiliSearch->options['indexes']['prefix'] ?? '';
        if ($prefix !== '') {
            $io->comment(sprintf('Prefix: %s', $prefix));
        }

        $suffix = $this->meiliSearch->options['indexes']['suffix'] ?? '';
        if ($suffix !== '') {
            $io->comment(sprintf('Suffix: %s', $suffix));
        }

        $hasAffixes = $prefix !== '' || $suffix !== '';

        $data = [];
        foreach ($this->meiliSearch->repositories() as $repository) {
            foreach ($repository->indexes() as $index) {
                $context = empty($index->context)
                    ? ''
                    : implode(', ', array_keys($index->context));

                $entry = [
                    $index->id,
                    $context,
                    $index->primaryKey,
                    $index->repository::class ?? '',
                ];

                if ($hasAffixes) {
                    array_splice($entry, 1, 0, [
                        $prefix.$index->id.$suffix
                    ]);
                }

                $data[] = $entry;
            }
        }

        static $headers = ['id', 'context', 'pk', 'repository'];

        if ($hasAffixes) {
            array_splice($headers, 1, 0, 'affixed');
        }

        $io->table($headers, $data);

        return Command::SUCCESS;
    }
}
