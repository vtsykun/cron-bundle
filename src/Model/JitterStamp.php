<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

final class JitterStamp implements CommandStamp, PeriodicalStampInterface
{
    private $stamp;
    private $maxSeconds;

    public function __construct(float $maxSeconds, PeriodicalStampInterface $stamp)
    {
        $this->maxSeconds = $maxSeconds;
        $this->stamp = $stamp;
    }

    /**
     * {@inheritdoc}
     */
    public function getNextRunDate(\DateTimeInterface $run): \DateTimeInterface
    {
        $nextRun = $this->stamp->getNextRunDate($run);
        $nextRun = (float)$nextRun->format('U.u') + $this->maxSeconds * random_int(0, PHP_INT_MAX)/PHP_INT_MAX;

        return new \DateTimeImmutable('@'. round($nextRun, 6), $run->getTimezone());
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return sprintf('%s with 0-%d sec jitter', $this->stamp, $this->maxSeconds);
    }
}
