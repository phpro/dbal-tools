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

        $container->import($configDir.'/collection.php');
        $container->import($configDir.'/commands.php');
        $container->import($configDir.'/fixtures.php');
        $container->import($configDir.'/migrations.php');
        $container->import($configDir.'/schema.php');
        $container->import($configDir.'/validators.php');
    }
}
