<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\DBAL\Connection;
use Phpro\DbalTools\Console\Command\FixturesCommand;
use Phpro\DbalTools\Fixtures\FixturesRunner;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(FixturesCommand::class)
        ->args([
            service(FixturesRunner::class),
            service(Connection::class),
        ])
        ->tag('console.command');
};
