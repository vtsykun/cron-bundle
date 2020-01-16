<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Command\CronExecuteCommand;
use Okvpn\Bundle\CronBundle\Model\AsyncStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Symfony\Component\Process\PhpExecutableFinder;

final class AsyncProcessEngine implements MiddlewareEngineInterface
{
    private $tempDir = null;

    public function __construct(string $sysTempDir = null)
    {
        $this->tempDir = $sysTempDir ?: sys_get_temp_dir();
    }

    /**
     * Run command async. without exit.
     *
     * {@inheritdoc}
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        if (false === $envelope->has(AsyncStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        $envelope = $envelope->without(AsyncStamp::class);
        $phpFinder = new PhpExecutableFinder();
        $phpPath   = $phpFinder->find();

        $filename = $this->tempDir . DIRECTORY_SEPARATOR . 'okvpn-cron-' . md5(random_bytes(10)) . '.txt';
        file_put_contents($filename, serialize($envelope));

        // create command string
        $runCommand = sprintf(
            '%s %s %s %s',
            $phpPath,
            $_SERVER['argv'][0],
            CronExecuteCommand::$defaultName,
            $filename
        );

        // workaround for Windows
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $wsh = new \COM('WScript.shell');
            $wsh->Run($runCommand, 0, false);
        } else {
            // run command
            shell_exec(sprintf('%s > /dev/null 2>&1 & echo $!', $runCommand));
        }

        return $stack->end()->handle($envelope, $stack);
    }
}
