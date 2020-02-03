<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
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
 * Execute SelfTest of Splash Client
 */
class ServerSelftestCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected $title = "Results of Local Module Self-Tests";

    /**
     * Configure Symfony Command
     */
    protected function configure(): void
    {
        $this
            ->setName('splash:server:selftest')
            ->setDescription('[Splash] Execute Module Self-Test')
            ->configureManagerOptions()
        ;
    }

    /**
     * Execute Symfony Command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
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
        // Verify PHP Version
        Splash::validate()->isValidPHPVersion();
        //====================================================================//
        // Verify PHP Extensions
        Splash::validate()->isValidPHPExtensions();
        //====================================================================//
        // Verify SOAP Method
        Splash::validate()->isValidSOAPMethod();
        //====================================================================//
        // Execute Splash Self-Tests
        $result = $this->isManagerMode()
                ? $this->getConnector()->selfTest()
                : Splash::selfTest();
        //====================================================================//
        // Render Splash Logs
        $output->writeln(Splash::log()->getConsoleLog());
        Splash::log()->getConsoleLog();
        //====================================================================//
        // Render Result Icon
        Graphics::renderResult($output, $result, "Module Self-Tests");

        return 0;
    }
}
