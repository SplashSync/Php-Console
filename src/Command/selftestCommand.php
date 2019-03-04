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
use Splash\Server\SplashServer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Splash\Console\Helper\Table;

use Splash\Console\Helper\Graphics;

class selftestCommand extends Command
{
    
    protected function configure()
    {
        $this
            ->setName('selftest')
            ->setDescription('Splash: Execute Module Self-Test')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //====================================================================//
        // Splash Screen        
        Graphics::renderSplashScreen($output);
        $output->writeln("Results of Local Module Self-Tests");
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
        Splash::selfTest();            
        //====================================================================//
        // Render Splash Logs
        $output->writeln(Splash::log()->getConsoleLog());
        Splash::log()->getConsoleLog();        
    } 
}
