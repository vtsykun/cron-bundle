<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Loader;

use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

interface ScheduleLoaderInterface
{
    /**
     * @return iterable|ScheduleEnvelope[]
     */
    public function getSchedules(): iterable;
}
