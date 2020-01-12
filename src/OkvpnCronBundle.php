<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle;

use Okvpn\Bundle\CronBundle\DependencyInjection\CompilerPass\CronPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OkvpnCronBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CronPass());
    }
}
