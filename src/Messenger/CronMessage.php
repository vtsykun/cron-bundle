<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Messenger;

use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

final class CronMessage
{
    private $schedule;

    public function __construct(ScheduleEnvelope $schedule)
    {
        $this->schedule = $schedule;
    }

    public function getSchedule(): ScheduleEnvelope
    {
        return $this->schedule;
    }
}
