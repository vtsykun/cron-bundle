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
use Okvpn\Bundle\CronBundle\Utils\CronUtils;
use Psr\Clock\ClockInterface as PsrClockInterface;

final class CronMiddlewareEngine implements MiddlewareEngineInterface
{
    private $timeZone;
    private $checker;
    private $clock;

    /** @var ScheduleLoopInterface|null */
    private $scheduleLoop;

    private $lastLoopTasks = [];
    private $timers;

    public function __construct(CronChecker $checker, ?string $timeZone = null, ?PsrClockInterface $clock = null, ?ScheduleLoopInterface $scheduleLoop = null, ?TimerStorage $timers = null)
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
        $env = $envelope->has(EnvironmentStamp::class) ? $envelope->get(EnvironmentStamp::class)->toArray() : [];
        // For testing usage. drops next middlewares
        if ($dryRun = ($env['dry-run'] ?? false)) {
            $stack->end();
        }

        if (!$stamp = $envelope->get(PeriodicalStampInterface::class)) {
            $dryRun ? null : ET::info($envelope, "{{ task }} > Run schedule task.");
            return $stack->next()->handle($envelope, $stack);
        }

        $useDemand = $env['demand'] ?? false;
        $noLoop = $env['no-loop'] ?? false;
        $now = ($env['now'] ?? null) instanceof \DateTimeInterface ? $env['now'] : $this->getNow();

        return true === $useDemand && null !== $this->scheduleLoop && false === $noLoop ?
            $this->handleDemand($now, $envelope, $stamp, $stack) :
            $this->handleNoDemand($now, $envelope, $stamp, $stack);
    }

    private function handleDemand(\DateTimeInterface $now, ScheduleEnvelope $envelope, PeriodicalStampInterface $stamp, StackInterface $stack): ScheduleEnvelope
    {
        $this->lastLoopTasks[$hash = ET::calculateHash($envelope)] = 1;
        if ($this->timers->hasTimer($hash)) {
            [$timer, $prevEnvelope] = $this->timers->getTimer($hash);
            if ((string)$prevEnvelope->get(PeriodicalStampInterface::class) !== (string) $stamp) {
                $this->timers->remove($hash);
                $this->scheduleLoop->cancelTimer($timer);

                ET::notice($envelope, "{{ task }} > Cron expression has been changed.");
            } else {
                $this->timers->refreshEnvelope($envelope);
                return $stack->end()->handle($envelope, $stack);
            }
        }

        $prevEnvelope = $envelope;
        $timers = $this->timers;
        $loop = $this->scheduleLoop;

        $nextTime = $stamp->getNextRunDate($now = $loop->now());

        $timers->attach($envelope, $runner = static function (/* $periodical = true*/) use ($timers, $prevEnvelope, $hash, $stack, $stamp, $loop, &$runner): ScheduleEnvelope {
            if (null === ($envelope = $timers->findByHash($hash))) {
                ET::notice($prevEnvelope, "{{ task }} > Task canceled. Someone detached an envelope from timers storage");
                return ($clone = clone $stack)->end()->handle($envelope->without(PeriodicalStampInterface::class), $clone);
            }

            ET::info($envelope, "{{ task }} > Run schedule task.");

            try {
                $result = ($clone = clone $stack)->next()->handle($envelope->without(PeriodicalStampInterface::class), $clone);
            } catch (\Throwable $e) {
                $result = $envelope;
                ET::error($envelope, "{{ task }} > Task ERRORED. {$e->getMessage()}", ['e' => $e]);
            }

            if (false !== (\func_get_args()[0] ?? null)) {
                $nextTime = $stamp->getNextRunDate($now = $loop->now());
                $delay = (float) $nextTime->format('U.u') - (float) $now->format('U.u');

                $loop->addTimer($delay, $runner);
                ET::debug($envelope, \sprintf("{{ task }} > was scheduled with delay %.6f sec.", $delay));
            }

            return $result;
        });

        $delay = (float) $nextTime->format('U.u') - (float) $now->format('U.u');

        ET::debug($envelope, \sprintf("{{ task }} > was scheduled with delay %.6f sec.", $delay));
        $loop->addTimer($delay, $runner);

        return ($clone = clone $stack)->end()->handle($envelope, $clone);
    }

    public function onLoopEnd(): void
    {
        $this->cancelOrphanTasks();
        $this->lastLoopTasks = [];
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
            $currentTime = (int)(60 * \floor($now->getTimestamp()/60));
            $now = CronUtils::toDate(($currentTime-1), $now);

            $nextRun = $stamp->getNextRunDate($now);
            $nextRun = (int)(60 * \floor($nextRun->getTimestamp()/60));
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
        foreach ($this->timers->getTimers() as $hash => [$timer, $envelope]) {
            if (!isset($this->lastLoopTasks[$hash])) {
                ET::notice($envelope, "{{ task }} > task canceled - is not active anymore");

                $this->scheduleLoop->cancelTimer($timer);
                $this->timers->remove($hash);
            }
        }
    }

    private function getNow(): \DateTimeImmutable
    {
        return $this->clock ? $this->clock->now() : new \DateTimeImmutable('now', $this->timeZone ? new \DateTimeZone($this->timeZone) : null);
    }
}
