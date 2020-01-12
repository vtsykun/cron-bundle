<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Runner;

use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

interface ScheduleRunnerInterface
{
    /**
     * @param ScheduleEnvelope $envelope
     * @return ScheduleEnvelope
     */
    public function execute(ScheduleEnvelope $envelope): ScheduleEnvelope;
}
