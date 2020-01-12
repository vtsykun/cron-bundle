<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronExecuteCommand extends Command
{
    public static $defaultName = 'okvpn:cron:execute';

    private $redis;
    private $cronEngine;

    public function __construct()
    {
        parent::__construct(null);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->addArgument('job', InputArgument::REQUIRED, 'Cron job id')
            ->setDescription('Execute cron command for job id');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->redis->get($input->getArgument('job'));
        if (empty($command)) {
            $output->writeln('Job not found');
        }
        $command = json_decode($command, true);

        $this->cronEngine->run($command['command'], $command['arguments'] ?? []);
    }
}
