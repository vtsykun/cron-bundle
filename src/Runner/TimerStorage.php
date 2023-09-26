<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Runner;

use Okvpn\Bundle\CronBundle\Model\EnvelopeTools as ET;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

/**
 * Storage of all running timers for a crond demand.
 *
 * Gives easy access to active scheduled tasks
 */
class TimerStorage
{
    /** @psalm-var array<string, array{0: \Closure, 1: ScheduleEnvelope}> */
    protected $timers = [];

    public function attach(ScheduleEnvelope $envelope, \Closure $runner): void
    {
        $this->timers[ET::calculateHash($envelope)] = [$runner, $envelope];
    }

    public function refreshEnvelope(ScheduleEnvelope $envelope): void
    {
        if ($this->hasTimer($hash = ET::calculateHash($envelope))) {
            $this->timers[$hash][1] = $envelope;
        }
    }

    public function remove($envelope): void
    {
        $envelope = $envelope instanceof ScheduleEnvelope ? ET::calculateHash($envelope) : $envelope;

        if (\is_string($envelope)) {
            unset($this->timers[$envelope]);
        }
    }

    /**
     * @return array{0: \Closure, 1: ScheduleEnvelope}
     */
    public function getTimer($envelope): array
    {
        $envelope = $envelope instanceof ScheduleEnvelope ? ET::calculateHash($envelope) : $envelope;
        if (!isset($this->timers[$envelope])) {
            throw new \LogicException("Timer $envelope does not exists");
        }

        return $this->timers[$envelope];
    }

    /**
     * @return array<string, array{0: \Closure, 1: ScheduleEnvelope}>
     */
    public function getTimers(): array
    {
        return $this->timers;
    }

    public function hasTimer($envelope): bool
    {
        $envelope = $envelope instanceof ScheduleEnvelope ? ET::calculateHash($envelope) : $envelope;

        return $envelope && isset($this->timers[$envelope]);
    }

    public function findByHash(string $hash): ?ScheduleEnvelope
    {
        return $this->timers[$hash][1] ?? null;
    }

    public function find(string $command, /* array|string */ $args = null): ?ScheduleEnvelope
    {
        foreach ($this->timers as [$timer, $envelope]) {
            if ($envelope->getCommand() === $command && (null === $args || ET::argsHash($envelope) === ET::argsHash($args))) {
                return $envelope;
            }
        }

        return null;
    }
}
