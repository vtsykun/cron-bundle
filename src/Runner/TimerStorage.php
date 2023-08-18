<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Runner;

use Okvpn\Bundle\CronBundle\Model\EnvelopeTools as ET;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

/**
 * Storage all running timers for demand crond.
 *
 * Simply access to schedule tasks
 */
class TimerStorage
{
    /** @psalm-var array<string, array{0: \Closure, 1: ScheduleEnvelope}> */
    protected $timers = [];

    public function attach(ScheduleEnvelope $envelope, \Closure $runner): void
    {
        $this->timers[ET::calculateHash($envelope)] = [$runner, $envelope];
    }

    public function remove($envelope): void
    {
        $envelope = $envelope instanceof ScheduleEnvelope ? ET::calculateHash($envelope) : $envelope;
        unset($this->timers[$envelope]);
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

        return isset($this->timers[$envelope]);
    }

    public function find(string $command, /* array|string */ $args = null): ?ScheduleEnvelope
    {
        foreach ($this->timers as list($timer, $envelope)) {
            if ($envelope->getCommand() === $command && (null === $args || ET::argsHash($envelope) === ET::argsHash($args))) {
                return $envelope;
            }
        }

        return null;
    }
}
