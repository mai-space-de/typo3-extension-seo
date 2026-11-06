<?php

declare(strict_types=1);

namespace Maispace\MaiSeo\Command;

use Maispace\MaiSeo\Analytics\AnalyticsStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mai-seo:analytics:purge',
    description: 'Löscht Analytics-Aufrufe, die älter als die Aufbewahrungsfrist sind',
)]
final class PurgeAnalyticsHitsCommand extends Command
{
    public function __construct(
        private readonly AnalyticsStore $store,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Aufbewahrung in Tagen', '180');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        if ($days < 1) {
            $io->error('Die Option --days muss eine positive Zahl sein.');

            return Command::INVALID;
        }

        $deleted = $this->store->deleteOlderThan(time() - $days * 86400);
        $io->success(sprintf('%d Analytics-Einträge gelöscht (Frist: %d Tage).', $deleted, $days));

        return Command::SUCCESS;
    }
}
