<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2018 Splash Sync  <www.splashsync.com>
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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Splash\Console\Helper\Graphics;

/**
 * Execute SelfTest of Splash Client
 */
class SelftestCommand extends Command
{
    /**
     * Configure Symfony Command
     */
    protected function configure()
    {
        $this
            ->setName('selftest')
            ->setDescription('Splash: Execute Module Self-Test')
        ;
    }

    /**
     * Execute Symfony Command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //====================================================================//
        // Splash Screen        
        Graphics::renderSplashScreen($output);
        Graphics::renderTitle($output, "Results of Local Module Self-Tests");
        //====================================================================//
        // Notice internal routines we are in server request mode        
        define("SPLASH_SERVER_MODE", true);
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
        $result = Splash::selfTest();            
        //====================================================================//
        // Render Splash Logs
        $output->writeln(Splash::log()->getConsoleLog());
        Splash::log()->getConsoleLog();        
        //====================================================================//
        // Render Result Icon
        Graphics::renderResult($output, $result, "Module Self-Tests");
    } 
}
