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

namespace Splash\Console\Models;

use Splash\Client\Splash;
use Splash\Console\Helper\Graphics;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract Command for Splash Actions
 */
abstract class AbstractCommand extends Command
{
    use \Splash\Models\Fields\FieldsManagerTrait;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $title = __CLASS__;

    /**
     * Init Listing Command
     *
     * @param InputInterface $input
     */
    protected function init(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
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
