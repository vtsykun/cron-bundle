<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Event;

use Okvpn\Bundle\CronBundle\Runner\ScheduleLoopInterface;
use Symfony\Contracts\EventDispatcher\Event;

class StartLoopEvent extends Event
{
    public const START_LOOP = 'startLoop';

    private $loop;

    public function __construct(ScheduleLoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function getLoop(): ScheduleLoopInterface
    {
        return $this->loop;
    }
}
