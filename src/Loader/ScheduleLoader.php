<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Loader;

final class ScheduleLoader implements ScheduleLoaderInterface
{
    /** @var iterable|ScheduleLoaderInterface[] */
    private $loaders;

    public function __construct(iterable $loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * @inheritDoc
     */
    public function getSchedules(array $options = []): iterable
    {
        foreach ($this->loaders as $loader) {
            yield from $loader->getSchedules($options);
        }
    }
}
