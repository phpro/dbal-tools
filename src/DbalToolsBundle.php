<?php

declare(strict_types=1);

namespace Phpro\DbalTools;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @psalm-suppress DeprecatedInterface - Symfony 8.1 deprecates HttpKernel's BundleInterface, its replacement does not exist before 8.1.
 * @psalm-suppress UnusedPsalmSuppress - Only needed for Symfony 8.1 and up.
 */
final class DbalToolsBundle extends AbstractBundle
{
    /**
     * @psalm-suppress ParamNameMismatch - Symfony 8.1 renamed these parameters, the names cannot match both 7.4/8.0 and 8.1.
     * @psalm-suppress UnusedPsalmSuppress - Only needed for Symfony 8.1 and up.
     */
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
