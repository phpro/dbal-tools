<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Console\Command;

use Doctrine\DBAL\Connection;
use Phpro\DbalTools\Fixtures\Fixture;
use Phpro\DbalTools\Fixtures\FixturesRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Psl\Str\Byte\strip_prefix;

#[AsCommand(
    name: 'doctrine:fixtures',
    description: 'Load application fixtures'
)]
final class FixturesCommand extends Command
{
    public function __construct(
        private readonly FixturesRunner $fixturesRunner,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Load all application fixtures')
            ->addOption(name: 'type', mode: InputOption::VALUE_REQUIRED,
                description: 'Only create / truncate specific type (FQCN) "App\Domain\Model\User".'
            )
            ->addOption('truncate', 't', InputOption::VALUE_NONE, 'Truncate all fixture tables and relations.')
            ->addOption('reload', 'r', InputOption::VALUE_NONE, 'Truncate and Import all fixture.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = null !== $input->getOption('type') ? strip_prefix((string) $input->getOption('type'), '\\') : null;
        $truncate = $input->getOption('truncate') || $input->getOption('reload');
        if ($truncate) {
            $this->truncateFixtures($type, $io);

            if (!$input->getOption('reload')) {
                return Command::SUCCESS;
            }
        }

        $this->createFixtures($type, $io);

        return Command::SUCCESS;
    }

    private function createFixtures(?string $type, SymfonyStyle $io): void
    {
        if (null !== $type) {
            $io->text('Importing "'.$type.'"');
        }

        $successFullFixtures = 0;
        foreach ($this->fixturesRunner->execute($type) as $id => $fixture) {
            ++$successFullFixtures;
            $io->text('Imported '.\get_class($fixture).': "'.$id.'"');
        }

        $io->success('Imported '.(string) $successFullFixtures.' fixtures');
    }

    private function truncateFixtures(?string $type, SymfonyStyle $io): void
    {
        foreach ($this->fixturesRunner->fixtures as $fixture) {
            if (!$this->isOfType($type, $fixture)) {
                continue;
            }

            foreach ($fixture->tables() as $table) {
                $this->connection->executeQuery(
                    $this->connection->getDatabasePlatform()->getTruncateTableSQL($table, true)
                );
                $io->text('Truncated '.$table);
            }
        }
    }

    private function isOfType(?string $type, Fixture $fixture): bool
    {
        if (null === $type) {
            return true;
        }

        return $fixture->type() === $type;
    }
}
