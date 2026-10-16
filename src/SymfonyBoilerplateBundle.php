<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate;

use Aljerom\SymfonyBoilerplate\DomainEvents\EventRepositoryInterface;
use Aljerom\SymfonyBoilerplate\DomainEvents\NullEventRepository;
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

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->setParameter('symfony_boilerplate.timezone', $config['timezone']);
        $container->setParameter('symfony_boilerplate.locale', $config['locale']);

        // Default to a no-op event repository so a project compiles without an
        // event store; a consuming project overrides this binding. App-level
        // services are registered before loadExtension() runs, so its own
        // alias/definition wins when present.
        if (!$container->hasAlias(EventRepositoryInterface::class) && !$container->hasDefinition(EventRepositoryInterface::class)) {
            $container->setAlias(EventRepositoryInterface::class, NullEventRepository::class);
        }

        $configurator->import(__DIR__ . '/../config/services.php');
    }
}
