<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Loader;

use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

interface ScheduleLoaderInterface
{
    /**
     * @param array $options Any options, like group.
     *
     * @return iterable|ScheduleEnvelope[]
     */
    public function getSchedules(array $options = []): iterable;
}
