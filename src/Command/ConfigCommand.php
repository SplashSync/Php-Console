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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show Module's Configuration
 */
class ConfigCommand extends Command
{
    /**
     * Configure Symfony Command
     */
    protected function configure()
    {
        $this
            ->setName('config')
            ->setDescription('Splash : Show Your Splash Module Configuration')
        ;
    }

    /**
     * Execute Symfony Command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //====================================================================//
        // Splash Screen
        Graphics::renderSplashScreen($output);
        Graphics::renderTitle($output, "Your Splash Module Configuration");
        //====================================================================//
        // Notice internal routines we are in server request mode
        define("SPLASH_SERVER_MODE", true);
        //====================================================================//
        // Show Module Configuration
        $config = Splash::configuration()->getArrayCopy();
        print_r($config);
        //====================================================================//
        // Validate Module Configuration
        $validate = Splash::validate()->isValidLocalParameterArray($config);
        //====================================================================//
        // Render Splash Logs
        $output->writeln(Splash::log()->getConsoleLog());
        //====================================================================//
        // Render Result Icon
        Graphics::renderResult($output, $validate, "Splash Module Configuration Validation");
    }
}
