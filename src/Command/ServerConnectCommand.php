<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Console\Command;

use Splash\Console\Helper\Graphics;
use Splash\Console\Models\AbstractCommand;
use Splash\Core\Client\Splash;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test Connect to Splash Server
 */
class ServerConnectCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected string $title = "Test Connect of Splash Server";

    /**
     * Configure Symfony Command
     */
    protected function configure(): void
    {
        $this
            ->setName('splash:server:connect')
            ->setDescription('[Splash] Perform Connect Test to Splash Server')
            ->configureManagerOptions()
        ;
    }

    /**
     * Execute Symfony Command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //====================================================================//
        // Init & Splash Screen
        $this->init($input, $output);
        $this->renderTitle();
        //====================================================================//
        // Notice internal routines we are in server request mode
        if (!defined("SPLASH_SERVER_MODE")) {
            define("SPLASH_SERVER_MODE", true);
        }
        //====================================================================//
        // Execute Splash Self-Tests
        $selfTests = $this->isManagerMode()
                ? $this->getConnector()->selfTest()
                : Splash::selfTest()
        ;
        //====================================================================//
        // Execute Splash Server Ping
        $connect = false;
        if ($selfTests) {
            $connect = $this->isManagerMode()
                ? $this->getConnector()->connect()
                : Splash::connect()
            ;
        }
        //====================================================================//
        // Render Splash Logs
        $output->writeln(Splash::log()->getConsoleLog());
        Splash::log()->getConsoleLog();
        //====================================================================//
        // Render Result Icon
        Graphics::renderResult($output, $connect, "Connect to Splash Server");

        return 0;
    }
}
