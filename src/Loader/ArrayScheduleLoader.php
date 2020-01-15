<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Loader;

final class ArrayScheduleLoader implements ScheduleLoaderInterface
{
    private $tasks;
    private $factory;

    public function __construct(array $tasks, ScheduleFactoryInterface $factory)
    {
        $this->factory = $factory;
        $this->tasks = $tasks;
    }

    /**
     * @inheritDoc
     */
    public function getSchedules(array $options = []): iterable
    {
        $groups = $options['groups'] ?? [];
        foreach ($this->tasks as $task) {
            if ($groups && !\in_array($task['group'] ?? 'default', $groups)) {
                continue;
            }

            $task['options'] = $options;
            yield $this->factory->create($task);
        }
    }
}
