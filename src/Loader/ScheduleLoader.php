<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Loader;

use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

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
    public function getSchedules(): iterable
    {
        foreach ($this->loaders as $loader) {
            yield from $loader->getSchedules();
        }
    }
}
