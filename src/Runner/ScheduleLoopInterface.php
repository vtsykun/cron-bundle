<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Runner;

/**
 * Simple version of React EventLoop without IO/stream.
 *
 * Used to schedule periodic timers and execute cron loop each minute
 */
interface ScheduleLoopInterface /* \React\EventLoop\LoopInterface */
{
    /**
     * Enqueue a callback to be invoked repeatedly after the given interval.
     *
     * @param int|float $interval The number of seconds to wait before execution.
     * @param \Closure  $callback The callback to invoke
     *
     * @return void
     */
    public function addTimer(float $interval, \Closure $callback): void;

    /**
     * Enqueue a callback to be invoked repeatedly after the given interval.
     *
     * @param int|float $interval The number of seconds to wait before execution.
     * @param \Closure  $callback The callback to invoke
     *
     * @return void
     */
    public function addPeriodicTimer(float $interval, \Closure $callback): void;

    /**
     * Cancel a pending timer.
     *
     * @param \Closure|object $timer The timer to cancel.
     *
     * @return void
     */
    public function cancelTimer($timer): void;

    /**
     * Schedule a callback to be invoked on a future tick of the event loop.
     *
     * @param \Closure $listener
     * @return void
     */
    public function futureTick(\Closure $listener): void;

    /**
     * Return the current loop time
     *
     * @return \DateTimeImmutable
     */
    public function now(): \DateTimeImmutable;

    /**
     * Run the event loop until there are no more tasks to perform.
     *
     * @return void
     */
    public function run(): void;

    /**
     * Instruct a running event loop to stop.
     *
     * @return void
     */
    public function stop(): void;
}
