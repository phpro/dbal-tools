<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Phpro\DbalTools\Fixtures\FixturesRunner;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(FixturesRunner::class)
        ->args([
            tagged_iterator('phpro.dbal_tools.fixture'),
        ]);
};
