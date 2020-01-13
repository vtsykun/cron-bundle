<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle;

/**
 * Marker interface only for simplify DI.
 *
 * Used for autoconfigure, not required for implementation.
 *
 * You can use tag `okvpn.cron` instead.
 */
interface CronSubscriberInterface extends CronServiceInterface
{
    /**
     * Returns a valid cron expression, like * /10 * * * *
     *
     * @return string
     */
    public static function getCronExpression(): string;
}
