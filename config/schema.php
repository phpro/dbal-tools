<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\DBAL\Schema\Schema;
use Phpro\DbalTools\Schema\ApplicationSchemaProvider;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ApplicationSchemaProvider::class)
        ->args([
            tagged_iterator('phpro.dbal_tools.schema.table'),
            tagged_iterator('phpro.dbal_tools.schema.sequence'),
        ]);

    $services->set(Schema::class)
        ->factory([service(ApplicationSchemaProvider::class), 'createSchema']);
};
