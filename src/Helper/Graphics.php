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

use Splash\Components\Logger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Splash Console Graphical Outputs
 *
 * @author SplashSync <contact@splashsync.com>
 */
class Graphics
{
    const   SPLIT = "   ----------------------------------------------------------------";

    const   SPLASH1 = "    ______     ______   __         ______     ______     __  __    ";
    const   SPLASH2 = "   /\\  ___\\   /\\  == \\ /\\ \\       /\\  __ \\   /\\  ___\\   /\\ \\_\\ \\   ";
    const   SPLASH3 = "   \\ \\___  \\  \\ \\  _-/ \\ \\ \\____  \\ \\  __ \\  \\ \\___  \\  \\ \\  __ \\  ";
    const   SPLASH4 = "    \\/\\_____\\  \\ \\_\\    \\ \\_____\\  \\ \\_\\ \\_\\  \\/\\_____\\  \\ \\_\\ \\_\\ ";
    const   SPLASH5 = "     \\/_____/   \\/_/     \\/_____/   \\/_/\\/_/   \\/_____/   \\/_/\\/_/ ";
    const   SPLASH6 = "                                                                ";

    /**
     * Display SPLASH SCREEN
     */
    public static function renderSplashScreen(OutputInterface $output)
    {
        $output->write(Logger::getConsoleLine("", self::SPLIT, Logger::CMD_COLOR_MSG));
        $output->write(Logger::getConsoleLine("", self::SPLASH1, Logger::CMD_COLOR_WAR));
        $output->write(Logger::getConsoleLine("", self::SPLASH2, Logger::CMD_COLOR_WAR));
        $output->write(Logger::getConsoleLine("", self::SPLASH3, Logger::CMD_COLOR_WAR));
        $output->write(Logger::getConsoleLine("", self::SPLASH4, Logger::CMD_COLOR_WAR));
        $output->write(Logger::getConsoleLine("", self::SPLASH5, Logger::CMD_COLOR_WAR));
        $output->write(Logger::getConsoleLine("", self::SPLASH6, Logger::CMD_COLOR_WAR));
        $output->write(Logger::getConsoleLine("", self::SPLIT, Logger::CMD_COLOR_MSG));
        $output->writeln("");
    }

}
