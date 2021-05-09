<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Console\Command;

use Splash\Console\Helper\Table;
use Splash\Console\Models\AbstractListingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List Available Objects on Splash Client
 */
class ObjectsListCommand extends AbstractListingCommand
{
    /**
     * @var string
     */
    protected $title = "Read Objects List";

    /**
     * Configure Symfony Command
     */
    protected function configure(): void
    {
        $this
            ->setName('splash:objects:list')
            ->setDescription('[Splash] List Data for a Given Object Type')
            ->configureManagerOptions()
        ;

        parent::configure();
    }

    /**
     * Execute Symfony Command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        //====================================================================//
        // Init & Splash Screen
        $this->init($input, $output);
        $this->renderTitle();

        //====================================================================//
        // Read Objects Listed & Readable Fields
        $fields = $this->getFields(true, true, false);

        //====================================================================//
        // Read & Render Objects List
        $table = new Table($output);
        $table->renderObjectsList($fields, $this->getObjects());

        //====================================================================//
        // Render Splash Logs
        $this->renderLogs();

        return 0;
    }
}
