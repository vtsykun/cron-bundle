<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\DependencyInjection;

use Okvpn\Bundle\CronBundle\Cron\CronChecker;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('okvpn_cron');
        $rootNode = \method_exists($treeBuilder, 'getRootNode') ?
            $treeBuilder->getRootNode() :
            $treeBuilder->root('okvpn_cron');

        // Disable doctrine listener for classes in search bundle for MQ performance
        // this will leave the search functionality and if you need to update the index, you can do it manually
        $rootNode->children()
            ->scalarNode('timezone')->defaultNull()->end()
            ->scalarNode('loop_engine')->defaultNull()->end()
            ->arrayNode('messenger')
                ->children()
                    ->booleanNode('enable')->defaultFalse()->end()
                    ->scalarNode('default_bus')->end()
                ->end()
            ->end()
            ->scalarNode('lock_factory')->end()
            ->variableNode('default_policy')->end()
            ->arrayNode('with_stamps')
                ->scalarPrototype()
                    ->validate()
                    ->always(function ($value) {
                        if (!\is_string($value) || !\class_exists($value)) {
                            throw new \InvalidArgumentException(sprintf('Class don\'t exists or this value "%s" is not a valid class name', $value));
                        }
                        return $value;
                    })
                    ->end()
                ->end()
            ->end()
            ->arrayNode('tasks')
                ->arrayPrototype()
                ->ignoreExtraKeys(false)
                    ->children()
                        ->scalarNode('command')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('cron')
                            ->validate()
                            ->always(function ($value) {
                                if ($value && false === CronChecker::isValidExpression($value)) {
                                    throw new \InvalidArgumentException(sprintf('This value "%s" is not a valid cron expression', $value));
                                }
                                return $value;
                            })
                            ->end()
                        ->end()
                        ->integerNode('timeout')->end()
                        ->scalarNode('interval')->end()
                        ->integerNode('jitter')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
