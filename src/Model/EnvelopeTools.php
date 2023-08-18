<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class EnvelopeTools
{
    private static $hasher;

    public static function setHasher(callable $hasher): void
    {
        static::$hasher = $hasher;
    }

    public static function getLogger(ScheduleEnvelope $envelope): LoggerInterface
    {
        return $envelope->has(LoggerAwareStamp::class) ? $envelope->get(LoggerAwareStamp::class)->getLogger() : new NullLogger();
    }

    public static function debug(ScheduleEnvelope $envelope, string $message, array $context = []): void
    {
        static::getLogger($envelope)->debug(str_replace('{{ task }}', static::shortTaskName($envelope), $message), $context);
    }

    public static function info(ScheduleEnvelope $envelope, string $message, array $context = []): void
    {
        static::getLogger($envelope)->info(str_replace('{{ task }}', static::shortTaskName($envelope), $message), $context);
    }

    public static function notice(ScheduleEnvelope $envelope, string $message, array $context = []): void
    {
        static::getLogger($envelope)->notice(str_replace('{{ task }}', static::shortTaskName($envelope), $message), $context);
    }

    public static function warning(ScheduleEnvelope $envelope, string $message, array $context = []): void
    {
        static::getLogger($envelope)->warning(str_replace('{{ task }}', static::shortTaskName($envelope), $message), $context);
    }

    public static function error(ScheduleEnvelope $envelope, string $message, array $context = []): void
    {
        static::getLogger($envelope)->error(str_replace('{{ task }}', static::shortTaskName($envelope), $message), $context);
    }

    public static function taskName(ScheduleEnvelope $envelope): string
    {
        if (null !== ($args = $envelope->has(ArgumentsStamp::class) ? $envelope->get(ArgumentsStamp::class)->getArguments() : null)) {
            asort($args);
            $args = @json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $periodicalExpr = (string)$envelope->get(PeriodicalStampInterface::class);

        return $envelope->getCommand() . ($periodicalExpr ? ' ['.$periodicalExpr.']' : '') . ($args ? ' '.$args : '');
    }

    public static function argsHash($args): string
    {
        if ($args instanceof ScheduleEnvelope) {
            $args = $args->has(ArgumentsStamp::class) ? $args->get(ArgumentsStamp::class)->getArguments() : null;
        }

        if (empty($args)) {
            return sha1('');
        }

        if (is_array($args)) {
            asort($args);
            $args = @json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return sha1($args ?: '');
    }

    public static function shortTaskName(ScheduleEnvelope $envelope): string
    {
        return substr(static::taskName($envelope), 0, 144);
    }

    public static function calculateHash(ScheduleEnvelope $envelope): string
    {
        if (null !== static::$hasher) {
            return call_user_func(static::$hasher, $envelope);
        }

        return sha1(static::shortTaskName($envelope));
    }
}
