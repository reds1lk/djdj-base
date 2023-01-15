<?php

namespace Djdj\Base\command;

use Djdj\Base\rpc\RpcServer;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\swoole\PidManager;
use think\swoole\rpc\Manager;

class Rpc extends Command
{
    public function configure()
    {
        $this->setName('rpc')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload", 'start')
            ->setDescription('Swoole RPC Server for ThinkPHP');
    }

    protected function initialize(Input $input, Output $output)
    {
        $this->app->bind(PidManager::class, function () {
            return new PidManager($this->app->config->get("consul.client.pid_file"));
        });
    }

    public function handle()
    {
        $this->checkEnvironment();

        $action = $this->input->getArgument('action');

        if (in_array($action, ['start', 'stop', 'reload', 'restart'])) {
            $this->app->invokeMethod([$this, $action], [], true);
        } else {
            $this->output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload .</error>");
        }
    }

    /**
     * 检查环境
     */
    protected function checkEnvironment()
    {
        if (!extension_loaded('swoole')) {
            $this->output->error('Can\'t detect Swoole extension installed.');

            exit(1);
        }

        if (!version_compare(swoole_version(), '4.3.1', 'ge')) {
            $this->output->error('Your Swoole version must be higher than `4.3.1`.');

            exit(1);
        }
    }

    /**
     * 启动server
     * @access protected
     * @param Manager $manager
     * @param PidManager $pidManager
     * @return void
     */
    protected function start(PidManager $pidManager)
    {
        if ($pidManager->isRunning()) {
            $this->output->writeln('<error>swoole rpc server process is already running.</error>');
            return;
        }

        $this->output->writeln('Starting swoole rpc server...');

        $name = $this->app->config->get('consul.client.name');
        $ip = $this->app->config->get('consul.client.ip');
        $port = $this->app->config->get('consul.client.port');
        $this->output->writeln("{$name} started: <tcp://{$ip}:{$port}>");

        $this->createSwooleServer();
    }

    /**
     * 柔性重启server
     * @access protected
     * @param PidManager $manager
     * @return void
     */
    protected function reload(PidManager $manager)
    {
        if (!$manager->isRunning()) {
            $this->output->writeln('<error>no swoole rpc server process running.</error>');
            return;
        }

        $this->output->writeln('Reloading swoole rpc server...');

        if (!$manager->killProcess(SIGUSR1)) {
            $this->output->error('> failure');

            return;
        }

        $this->output->writeln('> success');
    }

    /**
     * 停止server
     * @access protected
     * @param PidManager $manager
     * @return void
     */
    protected function stop(PidManager $manager)
    {
        if (!$manager->isRunning()) {
            $this->output->writeln('<error>no swoole rpc server process running.</error>');
            return;
        }

        $this->output->writeln('Stopping swoole rpc server...');

        $isRunning = $manager->killProcess(SIGTERM, 15);

        if ($isRunning) {
            $this->output->error('Unable to stop the rpc process.');
            return;
        }

        $this->output->writeln('> success');
    }

    /**
     * 重启server
     * @access protected
     * @param Manager $manager
     * @param PidManager $pidManager
     * @return void
     */
    protected function restart(PidManager $pidManager)
    {
        if ($pidManager->isRunning()) {
            $this->stop($pidManager);
        }

        $this->start($pidManager);
    }

    /**
     * Create swoole server.
     */
    protected function createSwooleServer()
    {
        return new RpcServer();
    }
}
