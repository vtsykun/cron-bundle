<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Utils;

/**
 * @internal
 */
final class CronUtils
{
    public static function toDate(/*float|string|\DateTimeInterface */ $unix, /* \DateTimeInterface|\DateTimeZone|string|null */ $timezone): \DateTimeImmutable
    {
        $timezone = $timezone instanceof \DateTimeInterface ? $timezone->getTimezone() : $timezone;
        $timezone = \is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;

        if ($unix instanceof \DateTimeInterface) {
            $unix = $unix->format('U.u');
        }

        return \DateTimeImmutable::createFromFormat("U.u", sprintf("%.6f", $unix), $timezone);
    }
}
