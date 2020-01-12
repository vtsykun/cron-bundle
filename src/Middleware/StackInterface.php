<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

interface StackInterface
{
    /**
     * Returns the next engine to process a command.
     */
    public function next(): MiddlewareEngineInterface;

    /**
     * Returns the end engine to process a command.
     */
    public function end(): MiddlewareEngineInterface;
}
