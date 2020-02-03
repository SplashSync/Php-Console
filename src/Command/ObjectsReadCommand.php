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

namespace Splash\Console\Command;

use Splash\Client\Splash;
use Splash\Console\Helper\Table;
use Splash\Console\Models\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List Available Objects on Splash Client
 */
class ObjectsReadCommand extends AbstractCommand
{
    /**
     * Current Object Type to Read
     *
     * @var string
     */
    private $objectType;

    /**
     * Current Object Type to Read
     *
     * @var string
     */
    private $objectId;

    /**
     * Object Fields to Read & Display
     *
     * @var array
     */
    private $fields;

    /**
     * Configure Symfony Command
     */
    protected function configure(): void
    {
        $this
            ->setName('splash:objects:read')
            ->setDescription('[Splash] Read Data for a Given Object with ID')
            ->addArgument('objectType', InputArgument::REQUIRED, 'Object Type for Reading')
            ->addArgument('objectId', InputArgument::REQUIRED, 'Object Id to Read')
            ->addOption('fields', "f", InputOption::VALUE_OPTIONAL, 'Comma separated List of fields to Display', null)
            ->configureManagerOptions()
        ;
    }

    /**
     * Execute Symfony Command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null|int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        //====================================================================//
        // Init & Splash Screen
        $this->init($input, $output);
        $this->loadInputs($input);

        //====================================================================//
        // Verify Object if Available
        if (!in_array($this->objectType, Splash::objects(), true)) {
            Splash::log()->err("Object of Type ".$this->objectType." not Found");

            return 1;
        }

        //====================================================================//
        // Read Objects Readable Fields
        $fields = Splash::object($this->objectType)->fields();
        $reducedFields = self::reduceFieldList($fields, true);
        $filteredFields = self::filterFieldList($fields, $reducedFields);
        if ($this->fields) {
            $filteredFields = self::filterFieldList($filteredFields, $this->fields);
        }

        //====================================================================//
        // Read Object Data
        $objectData = Splash::object($this->objectType)->get($this->objectId, $reducedFields);

        //====================================================================//
        // Render Object Data
        if (is_array($objectData)) {
            $table = new Table($output);
            $table->renderObjectData($filteredFields, $objectData);
        }

        //====================================================================//
        // Render Splash Logs
        $output->writeln(Splash::log()->getConsoleLog());
        Splash::log()->getConsoleLog();

        return 0;
    }

    /**
     * Load Symfony Command Inputs
     *
     * @param InputInterface $input
     */
    private function loadInputs(InputInterface $input): void
    {
        //====================================================================//
        // Fetch Command Configuration
        $objectType = $input->getArgument("objectType");
        $this->objectType = (string) (is_scalar($objectType) ? $objectType : "");
        $objectId = $input->getArgument("objectId");
        $this->objectId = (string) (is_scalar($objectId) ? $objectId : "");
        $userFields = $input->getOption("fields");
        if (!empty($userFields) && is_scalar($userFields) && !empty(explode(',', (string) $userFields))) {
            $this->fields = array_map('trim', explode(',', (string) $userFields));
        }
        //====================================================================//
        // Render Command Title
        $this->title = "Read Object ".$this->objectId."@".$this->objectType;
        $this->renderTitle();
    }
}
