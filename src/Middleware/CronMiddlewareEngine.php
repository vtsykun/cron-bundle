<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Cron\CronChecker;
use Okvpn\Bundle\CronBundle\Model\EnvelopeTools as ET;
use Okvpn\Bundle\CronBundle\Model\EnvironmentStamp;
use Okvpn\Bundle\CronBundle\Model\PeriodicalStampInterface;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Okvpn\Bundle\CronBundle\Model\ScheduleStamp;
use Okvpn\Bundle\CronBundle\Runner\ScheduleLoopInterface;
use Okvpn\Bundle\CronBundle\Runner\TimerStorage;
use Psr\Clock\ClockInterface as PsrClockInterface;

final class CronMiddlewareEngine implements MiddlewareEngineInterface
{
    private $timeZone;
    private $checker;
    private $clock;

    /** @var ScheduleLoopInterface|null */
    private $scheduleLoop;

    private $lastLoopId = null;
    private $lastLoopTasks = [];
    private $timers;

    public function __construct(CronChecker $checker, string $timeZone = null, PsrClockInterface $clock = null, ScheduleLoopInterface $scheduleLoop = null, TimerStorage $timers = null)
    {
        $this->timeZone = $timeZone;
        $this->checker = $checker;
        $this->clock = $clock;
        $this->scheduleLoop = $scheduleLoop;
        $this->timers = $timers ?? new TimerStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        if (!$stamp = $envelope->get(PeriodicalStampInterface::class)) {
            ET::info($envelope, "{{ task }} > Run schedule task.");
            return $stack->next()->handle($envelope, $stack);
        }

        $env = $envelope->get(EnvironmentStamp::class);
        $useDemand = $env && $env->get('demand');
        $noLoop = $env ? $env->get('no-loop') ?? false : false;
        $now = $env ? $env->get('now') : null;
        $now = $now instanceof \DateTimeInterface ? $now : $this->getNow();

        return true === $useDemand && null !== $this->scheduleLoop && false === $noLoop ?
            $this->handleDemand($now, $envelope, $stamp, $stack) :
            $this->handleNoDemand($now, $envelope, $stamp, $stack);
    }

    private function handleDemand(\DateTimeInterface $loopTime, ScheduleEnvelope $envelope, PeriodicalStampInterface $stamp, StackInterface $stack): ScheduleEnvelope
    {
        $loopId = (int)(60 * floor($loopTime->getTimestamp()/60));
        if ($this->lastLoopId !== $loopId) {
            $this->cancelOrphanTasks();
            $this->lastLoopId = $loopId;
        }

        $taskHash = ET::calculateHash($envelope);
        $this->lastLoopTasks[$taskHash] = 1;
        if ($this->timers->hasTimer($taskHash)) {
            return $stack->end()->handle($envelope, $stack);
        }

        $loop = $this->scheduleLoop;
        $nextTime = $stamp->getNextRunDate($now = $loop->now());

        $this->timers->attach($envelope, $runner = static function () use ($envelope, $stack, $stamp, $loop, &$runner) {
            ET::info($envelope, "{{ task }} > Run schedule task.");

            ($clone = clone $stack)->next()->handle($envelope->without(PeriodicalStampInterface::class), $clone);

            $nextTime = $stamp->getNextRunDate($now = $loop->now());
            $delay = (float) $nextTime->format('U.u') - (float) $now->format('U.u');
            $loop->addTimer($delay, $runner);

            ET::debug($envelope, "{{ task }} > was scheduled with delay $delay sec.");
        });

        $delay = (float) $nextTime->format('U.u') - (float) $now->format('U.u');

        ET::debug($envelope, "{{ task }} > was scheduled with delay $delay sec.");
        $loop->addTimer($delay, $runner);

        return ($clone = clone $stack)->end()->handle($envelope, $clone);
    }

    private function handleNoDemand(\DateTimeInterface $now, ScheduleEnvelope $envelope, PeriodicalStampInterface $stamp, StackInterface $stack): ScheduleEnvelope
    {
        if ($stamp instanceof ScheduleStamp) {
            try {
                $isDue = $this->checker->isDue($expr = $stamp->cronExpression(), $this->timeZone, $now);
            } catch (\Throwable $e) {
                ET::error($envelope, "{{ task }} > The cron expression $expr for task is invalid. {$e->getMessage()}", ['e' => $e]);
                return $stack->end()->handle($envelope, $stack);
            }
        } else {
            $currentTime = (int)(60 * floor($now->getTimestamp()/60));
            $now = new \DateTimeImmutable('@'.($currentTime-1), $now->getTimezone());

            $nextRun = $stamp->getNextRunDate($now);
            $nextRun = (int)(60 * floor($nextRun->getTimestamp()/60));
            $isDue = $nextRun === $currentTime;
        }

        if ($isDue) {
            ET::info($envelope, "{{ task }} > The schedule task is due now!");
            return $stack->next()->handle($envelope->without(PeriodicalStampInterface::class), $stack);
        } else {
            ET::debug($envelope, "{{ task }} > Skipped the schedule task by cron restriction");
        }

        return $stack->end()->handle($envelope, $stack);
    }

    private function cancelOrphanTasks(): void
    {
        foreach ($this->timers->getTimers() as $hash => list($timer, $envelope)) {
            if (!isset($this->lastLoopTasks[$hash])) {
                ET::notice($envelope, "{{ task }} > task canceled - is not active anymore");

                $this->scheduleLoop->cancelTimer($timer);
                $this->timers->remove($hash);
            }
        }

        $this->lastLoopTasks = [];
    }

    private function getNow(): \DateTimeImmutable
    {
        return $this->clock ? $this->clock->now() : new \DateTimeImmutable('now', $this->timeZone ? new \DateTimeZone($this->timeZone) : null);
    }
}
