<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Runner;

use Okvpn\Bundle\CronBundle\Clock\OkvpnLoopClock;
use Symfony\Component\Clock\ClockInterface;

class StandaloneLoop implements ScheduleLoopInterface
{
    protected $clock;
    protected $timers = [];
    protected $needSort = false;
    protected $running = false;

    public function __construct(?ClockInterface $clock = null, ?string $timeZone = null)
    {
        $this->clock = $clock ?: new OkvpnLoopClock($timeZone);
    }

    /**
     * {@inheritdoc}
     */
    public function addTimer(float $interval, \Closure $callback): void
    {
        $this->timers[] = [$callback, $interval + $this->getUnix(), 0];
        $this->needSort = true;
    }

    /**
     * {@inheritdoc}
     */
    public function addPeriodicTimer(float $interval, \Closure $callback): void
    {
        $this->timers[] = [$callback, $interval + $this->getUnix(), $interval];
        $this->needSort = true;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTimer($timer): void
    {
        foreach ($this->timers as $i => $queue) {
            if ($queue[0] === $timer) {
                unset($this->timers[$i]);
            }
        }

        $this->needSort = true;
    }

    /**
     * {@inheritdoc}
     */
    public function futureTick(\Closure $listener): void
    {
        $this->timers[] = [$listener, 0, 0];
        $this->needSort = true;
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $this->running = true;
        while ($this->running && $this->timers) {
            $this->sortTasks();

            foreach ($this->timers as $i => $timer) {
                $unix = $this->getUnix();
                if ($timer[1] < $unix) {
                    $this->execute($timer);
                    if ($timer[2] === 0) {
                        unset($this->timers[$i]);
                    } else {
                        $this->timers[$i][1] = $timer[2] + $timer[1];
                    }
                    $this->needSort = true;
                    continue;
                }
                break;
            }

            if (($sleep = $this->getNextSleep()) > 0 && $this->running) {
                $this->sleep($sleep);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        $this->running = false;
    }

    /**
     * {@inheritdoc}
     */
    public function now(): \DateTimeImmutable
    {
        return $this->clock->now();
    }

    protected function getUnix(): float
    {
        return (float)$this->clock->now()->format('U.u');
    }

    protected function sortTasks(): void
    {
        if (true === $this->needSort) {
            \usort($this->timers, static function ($a, $b) {
                return $a[1] <=> $b[1];
            });

            $this->timers = \array_values($this->timers);
        }

        $this->needSort = false;
    }

    protected function getNextSleep(): float
    {
        $this->sortTasks();

        return ($this->timers[0][1] ?? 0) - $this->getUnix();
    }

    protected function sleep(float $seconds): void
    {
        $this->clock->sleep($seconds);
    }

    protected function execute(array $timer): void
    {
        \call_user_func($timer[0]);
    }
}
