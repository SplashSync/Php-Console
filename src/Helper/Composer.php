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

namespace Splash\Console\Helper;

use Composer\Console\Application as ComposerApp;
use Exception;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Execute Composer Updates During GrumPhp Tasks
 */
class Composer
{
    /**
     * Execute Module Composer Update
     *
     * @param string $workingDir
     * @param array  $options
     *
     * @return bool
     */
    public static function update(string $workingDir, array $options = array()): bool
    {
        //====================================================================//
        // Prepare Command Input Array
        $input = new ArrayInput(
            self::buildUpdateOptions($workingDir, $options)
        );

        //====================================================================//
        // Execute Composer Build
        try {
            $composer = new ComposerApp();
            $composer->setAutoExit(false);
            $composer->run($input);
        } catch (Exception $exception) {
            return Splash::log()->errTrace("Composer Update Failled ".$exception->getMessage());
        }

        return true;
    }

    /**
     * Build Options Array for Composer
     *
     * @param string $workingDir
     * @param array  $options
     *
     * @return array
     */
    private static function buildUpdateOptions(string $workingDir, array $options = array()): array
    {
        //====================================================================//
        // Prepare Base Composer Options
        $baseOptions = array(
            'command' => 'update',
            "--working-dir" => $workingDir,
            "--quiet" => true,
            "--no-interaction" => true,
        );
        //====================================================================//
        // Merge with User Options
        return (array) array_replace_recursive($baseOptions, $options);
    }
}
