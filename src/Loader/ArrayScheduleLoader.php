<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Loader;

final class ArrayScheduleLoader implements ScheduleLoaderInterface
{
    private $configuration;
    private $factory;

    public function __construct(array $configuration, ScheduleFactoryInterface $factory)
    {
        $this->factory = $factory;
        $this->configuration = $configuration;
    }

    /**
     * @inheritDoc
     */
    public function getSchedules(): iterable
    {
        foreach ($this->configuration as $config) {
            yield $this->factory->create($config);
        }
    }
}
