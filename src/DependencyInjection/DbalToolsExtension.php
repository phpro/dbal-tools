<?php

declare(strict_types=1);

namespace Phpro\DbalTools\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class DbalToolsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('commands.xml');
        $loader->load('fixtures.xml');
        $loader->load('migrations.xml');
        $loader->load('schema.xml');
    }
}
