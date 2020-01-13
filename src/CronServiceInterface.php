<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle;

/**
 * Only for simplify DI.
 *
 * Used for autoconfigure, not required for implementation
 */
interface CronServiceInterface
{
    /**
     * Place custom logic to process your job
     *
     * @param array $arguments
     * @return mixed
     */
    public function __invoke(array $arguments = []);
}
