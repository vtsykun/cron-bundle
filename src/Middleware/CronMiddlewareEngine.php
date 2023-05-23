<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Cron\CronChecker;
use Okvpn\Bundle\CronBundle\Model\ArgumentsStamp;
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
            $this->log($envelope, "> Run schedule task {{ task }}");
            return $stack->next()->handle($envelope, $stack);
        }

        try {
            $isDue = $this->checker->isDue($expr = $stamp->cronExpression(), $this->timeZone, $this->clock ? $this->clock->now() : 'now');
        } catch (\Throwable $e) {
            $this->log($envelope, "> The cron expression $expr for task {{ task }} is invalid. {$e->getMessage()}", 'error', ['e' => $e]);
            return $stack->end()->handle($envelope, $stack);
        }

        if ($isDue) {
            $this->log($envelope, "> The schedule task {{ task }} is due now!");
            return $stack->next()->handle($envelope->without(ScheduleStamp::class), $stack);
        } else {
            $this->log($envelope, "> Skipped the schedule task {{ task }} by cron restriction", 'debug');
        }

        return $stack->end()->handle($envelope, $stack);
    }

    private function log(ScheduleEnvelope $envelope, string $message, string $logLevel = 'info', array $context = []): void
    {
        /** @var LoggerAwareStamp $stamp */
        if (!$stamp = $envelope->get(LoggerAwareStamp::class)) {
            return;
        }

        $args = $envelope->has(ArgumentsStamp::class) ? $envelope->get(ArgumentsStamp::class)->getArguments() : null;
        $args = $args ? @json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        $taskName = $envelope->getCommand() . ($args ? ' ' . substr($args, 0, 120) : '');

        $message = str_replace('{{ task }}', $taskName, $message);
        $stamp->getLogger()->log($logLevel, $message, $context);
    }
}
