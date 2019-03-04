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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Splash\Console\Helper\Table;
use Splash\Console\Helper\Graphics;

/**
 * List Available Objects on Splash Client
 */
class ObjectsCommand extends Command
{
    /**
     * Configure Symfony Command
     */
    protected function configure()
    {
        $this
            ->setName('objects')
            ->setDescription('Splash: List Available Objects Types')
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
        Graphics::renderTitle($output, "Listed of Available Objects");
        
        $table = new Table($output);

        //====================================================================//
        // Prepare Table Header
        $table->setHeaders(array("Type", "Name", "Import", "Export", "Description"));
        
        //====================================================================//
        // Walk on Objects Types
        foreach (Splash::objects() as $objectType) {
            //====================================================================//
            // Read Object Description
            $desc = Splash::object($objectType)->description();
            //====================================================================//
            // Add Object Row
            $table->addRow(array(
                $desc["type"],
                $desc["name"],
                self::getImportCfg($desc),
                self::getExportCfg($desc),
                $desc["description"],
            ));
        }
        
        //====================================================================//
        // Render Data Table
        $table->render();
        
        //====================================================================//
        // Render Splash Logs
        $output->writeln(Splash::log()->getConsoleLog());
        Splash::log()->getConsoleLog();        
    }
    
    /**
     * Get Import Configuration
     * 
     * @param array $desc
     * @return string
     */
    private static function getImportCfg(array $desc) : string
    {
        $result = "";
        //====================================================================//
        // Import Create ? 
        $result .= $desc["enable_pull_created"] ? "<info>C</>" : "<error>C</>";
        $result .= "|";
        //====================================================================//
        // Import Update ? 
        $result .= $desc["enable_pull_updated"] ? "<info>U</>" : "<error>U</>";
        $result .= "|";
        //====================================================================//
        // Import Delete ? 
        $result .= $desc["enable_pull_deleted"] ? "<info>D</>" : "<error>D</>";
            
        return $result;
    }    
    
    /**
     * Get Export Configuration
     * 
     * @param array $desc
     * @return string
     */
    private static function getExportCfg(array $desc) : string
    {
        $result = "";
        //====================================================================//
        // Export Create ? 
        $result .= ($desc["allow_push_created"] && $desc["enable_push_created"]) ? "<info>C</>" : "<error>C</>";
        $result .= "|";
        //====================================================================//
        // Export Update ? 
        $result .= ($desc["allow_push_updated"] && $desc["enable_push_updated"])? "<info>U</>" : "<error>U</>";
        $result .= "|";
        //====================================================================//
        // Export Delete ? 
        $result .= ($desc["allow_push_deleted"] && $desc["enable_push_deleted"]) ? "<info>D</>" : "<error>D</>";
            
        return $result;
    }        
}
