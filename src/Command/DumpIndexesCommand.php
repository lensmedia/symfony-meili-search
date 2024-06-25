<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Command;

use JsonException;
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
class DumpIndexesCommand extends Command
{
    public const NAME = 'lens:meili-search:dump-indexes';
    public function __construct(
        private readonly MeiliSearch $meiliSearch,
    ) {
        parent::__construct(self::NAME);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Dumping indexes references');

        $prefix = $this->meiliSearch->indexes->options['prefix'] ?? '';
        if ($prefix !== '') {
            $io->comment(sprintf('Prefix: %s', $prefix));
        }

        $suffix = $this->meiliSearch->indexes->options['suffix'] ?? '';
        if ($suffix !== '') {
            $io->comment(sprintf('Suffix: %s', $suffix));
        }

        $data = [];

        foreach ($this->meiliSearch->repositories() as $repository) {
            foreach ($repository->indexes() as $index) {
                $context = empty($index->context)
                    ? ''
                    : implode(', ', array_keys($index->context));

                $data[] = [
                    $prefix.$index->id.$suffix,
                    $context,
                    $index->primaryKey,
                    $index->repository::class,
                ];
            }
        }

        $io->table([
            'id',
            'context',
            'pk',
            'repository',
        ], $data);

        return Command::SUCCESS;
    }
}
