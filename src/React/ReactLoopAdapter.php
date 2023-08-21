<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\React;

use Okvpn\Bundle\CronBundle\Runner\ScheduleLoopInterface;
use Okvpn\Bundle\CronBundle\Utils\CronUtils;
use React\EventLoop\ExtEvLoop;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\EventLoop\TimerInterface;

class ReactLoopAdapter implements ScheduleLoopInterface
{
    private $loop;
    private $timers = [];
    private $useWeakRef;
    private $nowAccessor;
    private $loopTime;
    private $timeZone;

    public function __construct(LoopInterface $loop = null, string $timeZone = null)
    {
        $this->loop = $loop;
        $this->timeZone = $timeZone;
        $this->useWeakRef = class_exists(\WeakReference::class);
    }

    /**
     * {@inheritdoc}
     */
    public function addTimer(float $interval, \Closure $callback): void
    {
        $timer = $this->getLoop()->addTimer($interval, $callback);

        $this->timers[spl_object_hash($callback)] = $this->useWeakRef ? \WeakReference::create($timer) : $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function addPeriodicTimer(float $interval, \Closure $callback): void
    {
        $timer = $this->getLoop()->addPeriodicTimer($interval, $callback);

        $this->timers[spl_object_hash($callback)] = $this->useWeakRef ? \WeakReference::create($timer) : $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTimer($timer): void
    {
        if ($timer instanceof TimerInterface) {
            $this->getLoop()->cancelTimer($timer);
            return;
        }

        $weakRef = $this->timers[spl_object_hash($timer)] ?? null;
        $weakRef = $weakRef instanceof \WeakReference ? $weakRef->get() : $weakRef;
        unset($this->timers[spl_object_hash($timer)]);

        if (null !== $weakRef) {
            $this->getLoop()->cancelTimer($weakRef);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function futureTick(\Closure $listener): void
    {
        $this->getLoop()->futureTick($listener);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $this->getLoop()->run();
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        $this->getLoop()->stop();
    }

    /**
     * {@inheritdoc}
     */
    public function now(): \DateTimeImmutable
    {
        if (null === $this->nowAccessor) {
            $this->nowAccessor = $this->createLoopAccessor();
        }


        $now = ($this->nowAccessor)();
        if (null !== $this->timeZone) {
            $now = CronUtils::toDate($now, $this->timeZone);
        }

        return $now;
    }

    public function setDefaultLoopTime(\DateTimeImmutable $loopTime = null): void
    {
        $this->loopTime = $loopTime;
    }

    public function getLoop(): LoopInterface
    {
        if (null === $this->loop) {
            $this->loop = Loop::get();
        }

        return $this->loop;
    }

    private function createLoopAccessor(): \Closure
    {
        if ($this->loop instanceof StreamSelectLoop) {
            return static function (): \DateTimeImmutable {
                return new \DateTimeImmutable('now');
            };
        }

        if ($this->loop instanceof ExtEvLoop && (new \ReflectionObject($this->loop))->hasProperty('loop')) {
            return \Closure::bind(function (): \DateTimeImmutable {
                $now = $this->loop->now();
                return CronUtils::toDate($now);
            }, $this->loop, ExtEvLoop::class);
        }

        return function (): \DateTimeImmutable {
            return $this->loopTime ?: new \DateTimeImmutable('now');
        };
    }
}
