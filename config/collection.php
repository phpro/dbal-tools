<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Phpro\DbalTools\Collection\PatchCollection;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(PatchCollection::class);
};
