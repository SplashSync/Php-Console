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

namespace Splash\Console\Models;

use Splash\Console\Helper\Graphics;
use Splash\Core\Client\Splash;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract Command for Splash Actions
 */
abstract class AbstractCommand extends Command
{
    use ManagerAwareTrait;

    /**
     * @var InputInterface
     */
    protected InputInterface $input;

    /**
     * @var OutputInterface
     */
    protected OutputInterface $output;

    /**
     * @var string
     */
    protected string $title = __CLASS__;

    /**
     * Init Listing Command
     */
    protected function init(InputInterface $input, OutputInterface $output): void
    {
        //====================================================================//
        // Store I/O Objects
        $this->input = $input;
        $this->output = $output;
        //====================================================================//
        // Detect Server if we are in Connector Manager Mode
        $this->identifyServer($input);
    }

    /**
     * Render Command Title
     */
    protected function renderTitle(): void
    {
        Graphics::renderSplashScreen($this->output);
        Graphics::renderTitle($this->output, $this->title);
    }

    /**
     * Render Command Logs
     */
    protected function renderLogs(): void
    {
        //====================================================================//
        // Render Splash Logs
        $this->output->writeln(Splash::log()->getConsoleLog());
    }
}
