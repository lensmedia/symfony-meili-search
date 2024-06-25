<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Command;

use Lens\Bundle\MeiliSearchBundle\MeiliSearch;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: self::NAME,
    description: 'Displays a list of all configured groups.',
)]
class GroupsCommand extends Command
{
    public const NAME = 'lens:meili-search:groups';

    public function __construct(
        private readonly MeiliSearch $meiliSearch,
    ) {
        parent::__construct(self::NAME);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Lens MeiliSearch groups');

        $reflectionClass = new ReflectionClass(MeiliSearch::class);
        $groups = $reflectionClass->getProperty('groups');
        $groups->setAccessible(true);
        $groups = $groups->getValue($this->meiliSearch);

        $data = [];
        foreach ($groups as $group => $indexes) {
            $data[] = [$group, implode(', ', $indexes)];
        }

        $io->table(['Group', 'Indexes'], $data);

        return Command::SUCCESS;
    }
}
