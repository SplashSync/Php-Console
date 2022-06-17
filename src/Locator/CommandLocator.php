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

namespace Splash\Console\Locator;

/**
 * Splash Console Command Locator
 *
 * Scan Commands Folder and List Available commands
 */
class CommandLocator
{
    /**
     * @var array
     */
    const FILTERS = array('..', '.', 'index.php', 'index.html');

    /**
     * Available Commands List
     *
     * @var null|array
     */
    private static $commands;

    /**
     * Build & return list of Available Commands
     *
     * @return array
     */
    public static function commands()
    {
        //====================================================================//
        // Check if List Already Generated
        if (isset(self::$commands)) {
            return self::$commands;
        }
        //====================================================================//
        // Init List
        self::$commands = array();
        //====================================================================//
        // Scan Local Objects Folder
        $scan = scandir(dirname(__DIR__)."/Command", 1);
        if (false !== $scan) {
            //====================================================================//
            // Scan Each File in Folder
            $files = array_diff($scan, self::FILTERS);
            //====================================================================//
            // Walk on Command Files
            foreach ($files as $filename) {
                //====================================================================//
                // Extract Class Base Name
                $commandName = substr($filename, 0, (int) strpos($filename, "Command.php"));
                $className = "Splash\\Console\\Command\\".$commandName.'Command';
                //====================================================================//
                // Verify ClassName is a Valid
                if (class_exists($className, true)) {
                    self::$commands[$commandName] = $className;
                }
            }
        }

        return self::$commands;
    }
}
