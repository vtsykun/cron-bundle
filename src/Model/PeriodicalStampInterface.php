<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

interface PeriodicalStampInterface
{
    public function getNextRunDate(\DateTimeInterface $run): \DateTimeInterface;

    public function __toString(): string;
}
