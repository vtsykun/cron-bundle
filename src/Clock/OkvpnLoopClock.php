<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Okvpn\Bundle\CronBundle\Clock;

/**
 * Bridge for SF 4-5 support. Remove after update requires >= SF 6
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class OkvpnLoopClock /*implements ClockInterface*/
{
    private $timezone;

    public function __construct(/*\DateTimeZone|string */$timezone = null)
    {
        if (\is_string($timezone = $timezone ?? \date_default_timezone_get())) {
            $this->timezone = new \DateTimeZone($timezone);
        } else {
            $this->timezone = $timezone;
        }
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->timezone);
    }

    public function sleep($seconds): void
    {
        if (0 < $s = (int) $seconds) {
            \sleep($s);
        }

        if (0 < $us = $seconds - $s) {
            \usleep((int) ($us * 1E6));
        }
    }

    public function withTimeZone(/*\DateTimeZone|string*/ $timezone): static
    {
        $clone = clone $this;
        $clone->timezone = \is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;

        return $clone;
    }
}
