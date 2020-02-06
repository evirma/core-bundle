<?php

namespace Evirma\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;
use Evirma\Bundle\CoreBundle\Filter\Rule\Slug;
use Evirma\Bundle\CoreBundle\Traits\CacheTrait;
use Evirma\Bundle\CoreBundle\Traits\CommandTrait;
use Evirma\Bundle\CoreBundle\Traits\DbTrait;
use Evirma\Bundle\CoreBundle\Traits\LoggerTrait;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

abstract class AbstractCoreCommand extends Command implements ServiceSubscriberInterface
{
    use CommandTrait;
    use CacheTrait;
    use DbTrait;
    use LoggerTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public static function getSubscribedServices()
    {
        return [
            'router' => '?'.RouterInterface::class,
            'request_stack' => '?'.RequestStack::class,
            'http_kernel' => '?'.HttpKernelInterface::class,
            'session' => '?'.SessionInterface::class,
            'security.authorization_checker' => '?'.AuthorizationCheckerInterface::class,
            'twig' => '?'.Environment::class,
            'doctrine' => '?'.ManagerRegistry::class,
            'form.factory' => '?'.FormFactoryInterface::class,
            'security.token_storage' => '?'.TokenStorageInterface::class,
            'parameter_bag' => '?'.ContainerBagInterface::class,
        ];
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @required
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function outputHelp(InputInterface $input, OutputInterface $output)
    {
        $help = new HelpCommand();
        $help->setCommand($this);

        try {
            $help->run($input, $output);
        } catch (Exception $e) {
            $this->getLogger()->error("Help command failed: ".$e->getMessage());
        }
    }

    /**
     * @param $command
     */
    protected function checkRunning($command)
    {
//        $process = new Process();
        $process = Process::fromShellCommandline('ps aux | grep "console '.$command.' " | grep -v grep | grep -v "\/bin\/sh" | wc -l');
        $process->start();
        while ($process->isRunning()) {
            usleep(100);
            // waiting for process to finish
        }
        if (intval($process->getOutput()) > 1) {
            $this->getLogger()->critical($command.' is already running');
            exit;
        }
    }

    /**
     * @param string          $commandName
     * @param array           $arguments
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function runCommand($commandName, array $arguments, OutputInterface $output)
    {
        $command = $this->getApplication()->find($commandName);
        $this->getLogger()->info('Running command: '.$commandName);
        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);
        $this->getLogger()->info('Finished command: '.$commandName);
    }

    protected function saveReport($type, array $report = [])
    {
        $reportsDir = rtrim($this->getStorageDir().'/reports/', '/').'/';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }


        $type = preg_replace('#^x:#', '', $type);
        $type = FilterStatic::filterValue($type, Slug::class);

        $file = $reportsDir.$type.'.json';
        file_put_contents($file, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return mixed
     */
    protected function getStorageDir()
    {
        return $this->getParameter('storage_directory');
    }

    /**
     * Gets a container parameter by its name.
     *
     * @param string $name
     * @return mixed
     */
    protected function getParameter(string $name)
    {
        if (!$this->container->has('parameter_bag')) {
            throw new ServiceNotFoundException(
                'parameter_bag',
                null,
                null,
                [],
                sprintf(
                    'The "%s::getParameter()" method is missing a parameter bag to work properly. Did you forget to register your controller as a service subscriber? This can be fixed either by using autoconfiguration or by manually wiring a "parameter_bag" in the service locator passed to the controller.',
                    get_class($this)
                )
            );
        }

        return $this->container->get('parameter_bag')->get($name);
    }

}