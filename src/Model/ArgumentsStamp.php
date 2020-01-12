<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

class ArgumentsStamp implements CommandStamp
{
    private $arguments;

    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
