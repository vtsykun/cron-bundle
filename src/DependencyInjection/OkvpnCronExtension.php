<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\DependencyInjection;

use Okvpn\Bundle\CronBundle\CronSubscriberInterface;
use Okvpn\Bundle\CronBundle\Loader\ScheduleLoaderInterface;
use Okvpn\Bundle\CronBundle\Middleware\MiddlewareEngineInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 */
final class OkvpnCronExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $defaults = $config['default_options'] ?? [];
        $container->setParameter('okvpn.config.default_options', $defaults);
        if (isset($config['lock_factory'])) {
            $container->getDefinition('okvpn_okvpn_cron.middleware.lock')
                ->replaceArgument(0, new Reference($config['lock_factory']));
        }

        $tasks = [];
        foreach (($config['tasks'] ?? []) as $config) {
            $config['shell'] = true;
            $tasks[] = $config;
        }

        $container->getDefinition('okvpn_cron.array_loader')
            ->replaceArgument(0, $tasks);
        $container->getDefinition('okvpn_cron.schedule_factory')
            ->replaceArgument(0, $config['with_stamps'] ?? []);

        $container->registerForAutoconfiguration(MiddlewareEngineInterface::class)
            ->addTag('okvpn_cron.middleware');
        $container->registerForAutoconfiguration(ScheduleLoaderInterface::class)
            ->addTag('okvpn_cron.loader');
        $container->registerForAutoconfiguration(CronSubscriberInterface::class)
            ->addTag('okvpn.cron');
    }
}
