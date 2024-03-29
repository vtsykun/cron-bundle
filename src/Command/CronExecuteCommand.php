<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Command;

use Okvpn\Bundle\CronBundle\Runner\ScheduleRunnerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand('okvpn:cron:execute-job', description: 'INTERNAL!!!. Execute cron command from file.', hidden: true)]
class CronExecuteCommand extends Command
{
    private $scheduleRunner;

    public function __construct(ScheduleRunnerInterface $scheduleRunner)
    {
        $this->scheduleRunner = $scheduleRunner;

        parent::__construct(null);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('okvpn:cron:execute-job')
            ->addArgument('filename', InputArgument::REQUIRED, 'PHP serialized cron job')
            ->setDescription('INTERNAL!!!. Execute cron command from file.')
            ->setHidden(true);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileContent = \file_get_contents($input->getArgument('filename'));
        $envelope = \unserialize($fileContent);

        try {
            $this->scheduleRunner->execute($envelope);
        } finally {
            @\unlink($input->getArgument('filename'));
        }

        return 0;
    }
}
