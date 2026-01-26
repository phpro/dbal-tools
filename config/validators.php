<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\DBAL\Schema\Schema;
use Phpro\DbalTools\Validator\SchemaFieldValidator;
use Phpro\DbalTools\Validator\TableKeyExistsValidator;
use Phpro\DbalTools\Validator\UniqueValidator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(SchemaFieldValidator::class)
        ->args([
            service(Schema::class),
        ])
        ->tag('validator.constraint_validator');

    $services->set(TableKeyExistsValidator::class)
        ->args([
            service('doctrine.dbal.default_connection'),
        ])
        ->tag('validator.constraint_validator');

    $services->set(UniqueValidator::class)
        ->args([
            service('doctrine.dbal.default_connection'),
            service('property_accessor'),
        ])
        ->tag('validator.constraint_validator');
};
