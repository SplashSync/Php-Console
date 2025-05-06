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

namespace Splash\Console\Command;

use Splash\Bundle\Interfaces\Connectors\TrackingInterface;
use Splash\Console\Helper\Graphics;
use Splash\Console\Models\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Track Objects Changes on a Given Node.
 */
class ObjectsTrackingCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected string $title = "Track & Commit Objects Changes";

    /**
     * Configure repair Command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('splash:objects:track')
            ->setDescription('[Splash] Track & Commit Objects Changes for a Given Node')
            ->configureManagerOptions()
        ;
    }

    /**
     * Execute Console Command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //====================================================================//
        // Init & Splash Screen
        $this->init($input, $output);
        $this->renderTitle();
        //====================================================================//
        // Safety Check => Verify Selftest Pass
        if (!$this->connector->selfTest()) {
            $this->renderLogs();
            Graphics::renderResult($output, false, $this->title);

            return 1;
        }
        //====================================================================//
        // Safety Check => Verify This is a Tracking Connector
        if (!$this->connector->isTrackingConnector() || !is_subclass_of($this->connector, TrackingInterface::class)) {
            $output->writeln("This Connector is Not Tracking Object Changes");

            return 1;
        }
        //==============================================================================
        // Walk on Connector Objects
        $output->writeln('<info>------------------------------------------------------</info>');
        foreach ($this->connector->getAvailableObjects() as $objectType) {
            //==============================================================================
            // Commit Changes
            $commited = $this->connector->doObjectChangesTracking($objectType);
            $output->writeln('  '.$objectType.': '.$commited.' Change(s) Commited.');
        }
        $output->writeln('<info>------------------------------------------------------</info>');
        //====================================================================//
        // Render Splash Logs
        $this->renderLogs();
        Graphics::renderResult($output, true, $this->title);

        return 0;
    }
}
