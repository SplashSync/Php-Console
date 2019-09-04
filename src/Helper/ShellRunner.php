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

namespace Splash\Console\Helper;

use Splash\Core\SplashCore as Splash;

/**
 * Execute Shell Command Line Actions
 */
class ShellRunner
{
    /**
     * Execute a Shell Action
     *
     * @param string $command
     *
     * @return bool
     */
    public static function run(string $command): bool
    {
        //====================================================================//
        // Prepare Returns Variables
        $outputs = array();
        $return = 0;
        //====================================================================//
        // Execute Shell Operation
        exec($command, $outputs, $return);
        //====================================================================//
        // Failed => Push Outputs toi Splash Log
        if ($return) {
            foreach ($outputs as $output) {
                Splash::log()->err(sprintf(__CLASS__.": %s", $output));
            }
        }

        return (0 == $return);
    }
}
