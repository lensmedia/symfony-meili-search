<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Command;

use Lens\Bundle\MeiliSearchBundle\LensMeiliSearch;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: self::NAME, description: self::DESCRIPTION)]
class UpdateIndexes extends Command
{
    public const NAME = 'lens:meili-search:update-indexes';
    public const DESCRIPTION = 'Update indexes from the loaded configurations';

    public function __construct(
        private readonly LensMeiliSearch $lensMeiliSearch,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('matching', InputArgument::OPTIONAL, 'Filter indexes to update by matching string (uses php.net/fnmatch). Eg. "company" or "blog_*".', '*');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $matchingOption = $input->getArgument('matching');
        $isMatching = '*' !== $matchingOption && '' !== $matchingOption;

        $indexConfigurations = $this->lensMeiliSearch->configuredIndexes($matchingOption);
        $totalIndexConfigurations = count($indexConfigurations);

        $io = new SymfonyStyle($input, $output);
        $count = count($indexConfigurations);

        if ($isMatching) {
            $io->title(sprintf('Updating %d/%d matching (%s) indexes', $count, $totalIndexConfigurations, $matchingOption));
        } else {
            $io->title(sprintf('Updating %d indexes', $count));
        }

        $io->progressStart(count($indexConfigurations));

        foreach ($indexConfigurations as $indexConfiguration) {
            $this->lensMeiliSearch->obtainRemoteIndex($indexConfiguration, updateExistingIndexSettings: true);

            $io->progressAdvance();
        }

        $io->progressFinish();

        return Command::SUCCESS;
    }
}
