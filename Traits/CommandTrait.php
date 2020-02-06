<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method LoggerInterface getLogger()
 */
trait CommandTrait
{
    /**
     * @param Command         $command
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function outputCommandHelp(Command $command, InputInterface $input, OutputInterface $output)
    {
        $help = new HelpCommand();
        $help->setCommand($command);

        try {
            $help->run($input, $output);
        } catch (Exception $e) {
            if (method_exists($this, 'getLogger')) {
                $this->getLogger()->error("Help command failed: ".$e->getMessage());
            }
        }
    }

    protected function logMemoryUsage()
    {
        if (method_exists($this, 'getLogger')) {
            $this->getLogger()->info('<info>MEMORY USAGE:</info> <fg=white;options=bold>'.$this->getMemoryUsage().'</>');
        }
    }

    protected function getMemoryUsage()
    {
        return round((memory_get_usage() / 1024 / 1024), 2).'Mb';
    }
}
