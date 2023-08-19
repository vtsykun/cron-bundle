<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Event;

use Okvpn\Bundle\CronBundle\Runner\ScheduleLoopInterface;
use Symfony\Contracts\EventDispatcher\Event;

class LoopEvent extends Event
{
    /**
     * Dispatch on init event loop, before $loop->run()
     */
    public const LOOP_INIT = 'loopInit';

    /**
     * Dispatch before running event loops. Executed every minutes
     */
    public const LOOP_START = 'loopStart';

    /**
     * Dispatch after running event loops. Executed every minutes
     */
    public const LOOP_END = 'loopEnd';

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
