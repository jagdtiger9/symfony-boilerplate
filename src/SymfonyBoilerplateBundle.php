<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SymfonyBoilerplateBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('timezone')->defaultValue('UTC')->end()
                ->scalarNode('locale')->defaultValue('en')->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('symfony_boilerplate.timezone', $config['timezone']);
        $builder->setParameter('symfony_boilerplate.locale', $config['locale']);

        $container->import(__DIR__ . '/../config/services.php');
    }
}
