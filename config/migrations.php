<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Provider\SchemaProvider;
use Phpro\DbalTools\Schema\ApplicationSchemaProvider;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('doctrine.migrations.dependency_factory', DependencyFactory::class)
        ->args([
            service('doctrine.migrations.configuration_loader'),
            service('logger'),
        ])
        ->call('setService', [
            SchemaProvider::class,
            service(ApplicationSchemaProvider::class),
        ]);
};
