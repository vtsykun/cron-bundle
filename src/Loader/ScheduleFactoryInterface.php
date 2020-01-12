<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Loader;

use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

interface ScheduleFactoryInterface
{
    /**
     * Create from array.
     *
     * @param array $config
     * @return ScheduleEnvelope
     */
    public function create(array $config): ScheduleEnvelope;
}
