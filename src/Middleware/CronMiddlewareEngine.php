<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Okvpn\Bundle\CronBundle\Model\ScheduleStamp;

final class CronMiddlewareEngine implements MiddlewareEngineInterface
{
    /**
     * @inheritDoc
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        /** @var ScheduleStamp $stamp */
        if ($stamp = $envelope->get(ScheduleStamp::class)) {
            if (!$stamp->cronExpression()->isDue()) {
                return $stack->end()->handle($envelope, $stack);
            }
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
