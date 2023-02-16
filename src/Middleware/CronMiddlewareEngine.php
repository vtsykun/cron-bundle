<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Cron\CronChecker;
use Okvpn\Bundle\CronBundle\Model\LoggerAwareStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Okvpn\Bundle\CronBundle\Model\ScheduleStamp;

final class CronMiddlewareEngine implements MiddlewareEngineInterface
{
    private $timeZone;
    private $checker;
    private $clock;

    public function __construct(CronChecker $checker, string $timeZone = null, /*\Psr\Clock\ClockInterface*/ $clock = null)
    {
        $this->timeZone = $timeZone;
        $this->checker = $checker;
        $this->clock = $clock;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        if (!$stamp = $envelope->get(ScheduleStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        if ($this->checker->isDue($stamp->cronExpression(), $this->timeZone, $this->clock ? $this->clock->now() : 'now')) {
            if ($envelope->has(LoggerAwareStamp::class)) {
                $envelope->get(LoggerAwareStamp::class)->getLogger()->info("> The schedule task {$envelope->getCommand()} is due now!");
            }

            return $stack->next()->handle($envelope->without(ScheduleStamp::class), $stack);
        }

        return $stack->end()->handle($envelope, $stack);
    }
}
