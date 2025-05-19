<?php

declare(strict_types=1);

namespace Phpro\DbalTools;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class DbalToolsBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $configDir = __DIR__.'/../config';

        $container->import($configDir.'/collection.xml');
        $container->import($configDir.'/commands.xml');
        $container->import($configDir.'/fixtures.xml');
        $container->import($configDir.'/migrations.xml');
        $container->import($configDir.'/schema.xml');
        $container->import($configDir.'/validators.xml');
    }
}
