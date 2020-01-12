<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

class OutputStamp implements CommandStamp
{
    private $output;

    public function __construct($output)
    {
        $this->output = $output;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }
}
