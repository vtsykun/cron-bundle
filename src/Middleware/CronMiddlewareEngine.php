<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Cron\CronExpression;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Okvpn\Bundle\CronBundle\Model\ScheduleStamp;

final class CronMiddlewareEngine implements MiddlewareEngineInterface
{
    private $timeZone;

    public function __construct(string $timeZone = null)
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @inheritDoc
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        if (!$stamp = $envelope->get(ScheduleStamp::class)) {
            $stack->next()->handle($envelope, $stack);
        }

        if (CronExpression::factory($stamp->cronExpression())->isDue('now', $this->timeZone)) {
            return $stack->next()->handle($envelope->without(ScheduleStamp::class), $stack);
        }

        return $stack->end()->handle($envelope, $stack);
    }
}
