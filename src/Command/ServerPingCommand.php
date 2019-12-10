<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Console\Command;

use Splash\Client\Splash;
use Splash\Console\Helper\Graphics;
use Splash\Console\Models\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test Ping of Splash Server
 */
class ServerPingCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected $title = "Test Ping of Splash Server";

    /**
     * Configure Symfony Command
     */
    protected function configure(): void
    {
        $this
            ->setName('splash:server:ping')
            ->setDescription('[Splash] Perform Ping Test to Splash Server')
            ->configureManagerOptions()
        ;
    }

    /**
     * Execute Symfony Command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null|int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->init($input, $output);
        //====================================================================//
        // Splash Screen
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
                : Splash::selfTest();
        //====================================================================//
        // Execute Splash Server Ping
        $ping = false;
        if ($selfTests) {
            $ping = $this->isManagerMode()
                ? $this->getConnector()->ping()
                : Splash::ping();
        }
        //====================================================================//
        // Render Splash Logs
        $this->renderLogs();
        //====================================================================//
        // Render Result Icon
        Graphics::renderResult($output, $ping, "Ping of Splash Server");

        return 0;
    }
}
