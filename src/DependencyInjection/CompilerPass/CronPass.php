<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\DependencyInjection\CompilerPass;

use Cron\CronExpression;
use Okvpn\Bundle\CronBundle\CronSubscriberInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CronPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $commands = $tasks = [];
        $tagged = $container->findTaggedServiceIds('okvpn.cron');
        foreach ($tagged as $id => $configs) {
            $commands[] = $id;
            $class = $container->getDefinition($id)->getClass();
            $expression = null;
            if (\is_subclass_of($class, CronSubscriberInterface::class)) {
                $expression = \call_user_func([$class, 'getCronExpression']);
            }

            foreach ($configs as $config) {
                $cron = $config['cron'] ?? $expression;
                if (null !== $cron && !CronExpression::isValidExpression($cron)) {
                    throw new \InvalidArgumentException(sprintf('Cron expression "%s" is not a valid for service %s', $cron, $id));
                }

                $config['cron'] = $expression;
                $config['command'] = $config['command'] ?? $id;
                $tasks[] = $config;
            }
        }

        $prevTasks = $container->getDefinition('okvpn_cron.array_loader')
            ->getArgument(0) ?: [];
        foreach ($prevTasks as &$task) {
            if ($container->hasDefinition($task['command'])) {
                unset($task['shell']);
                $commands[] = $task['command'];
            }
        }
        foreach ($container->findTaggedServiceIds('okvpn.cron_service') as $id => $configs) {
            $commands[] = $id;
        }

        $commands = array_map(function ($serviceId) { return new Reference($serviceId); }, array_unique($commands));

        $defaultOptions = $container->getParameter('okvpn.config.default_options') ?: [];
        $tasks = \array_merge($tasks, $prevTasks ?: []);
        \uasort($tasks, function ($a, $b) { return -1 * (($a['priority'] ?? 0) <=> ($b['priority'] ?? 0));});
        $tasks = \array_map(function ($task) use ($defaultOptions) {
            return \array_merge($defaultOptions, $task);
        }, $tasks);

        $container->getDefinition('okvpn_cron.array_loader')
            ->replaceArgument(0, $tasks);
        $container->getDefinition('okvpn_cron.commands_locator')
            ->replaceArgument(0, $commands);
    }
}
