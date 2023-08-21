<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

use Okvpn\Bundle\CronBundle\Utils\CronUtils;

class PeriodicalScheduleStamp implements CommandStamp, PeriodicalStampInterface
{
    private $interval;
    private $from;

    public function __construct(
       /* string|int|float|\DateInterval */ $interval,
       /* string|int|float|\DateTimeImmutable */ $from = 0
    ) {
        $this->from = \is_string($from) && !\is_numeric($from) ? (new \DateTimeImmutable($from))->getTimestamp() : (int)$from;
        $interval = \is_string($interval) && !\is_numeric($interval) ? ('P' === ($interval[0] ?? '') ? new \DateInterval($interval) : \DateInterval::createFromDateString($interval)) : $interval;

        if ($interval instanceof \DateInterval) {
            $time1 = new \DateTimeImmutable('now');
            $interval = (float)$time1->add($interval)->format('U.u') - (float)$time1->format('U.u');
        }

        $this->interval = (float)$interval;
    }

    /**
     * {@inheritdoc}
     */
    public function getNextRunDate(\DateTimeInterface $run): \DateTimeInterface
    {
        $now = (float)$run->format('U.u');
        $unix = ($now - $this->from);

        $delay = $this->interval - fmod($unix, $this->interval);

        return CronUtils::toDate($delay + $now, $run);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return '@periodical ' . round($this->interval, 2) . ' sec';
    }
}
