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

namespace Splash\Console\Command;

use Splash\Client\Splash;
use Splash\Console\Helper\Table;
use Splash\Console\Models\AbstractListingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Commit Objects from Splash Client
 */
class ObjectsCommitCommand extends AbstractListingCommand
{
    /**
     * @var Table
     */
    private $table;

    /**
     * Number of Objects in A Batch
     *
     * @var int
     */
    private $batchSize;

    /**
     * Delay in Second between Two Batch
     *
     * @var int
     */
    private $batchDelay;

    /**
     * Current Objects Counter
     *
     * @var int
     */
    private $current = 0;

    /**
     * Total Objects Counter
     *
     * @var int
     */
    private $total;

    /**
     * Commit All Option
     *
     * @var bool
     */
    private $all;

    /**
     * Do Commit or Dry run
     *
     * @var bool
     */
    private $force;

    /**
     * Configure Symfony Command
     */
    protected function configure()
    {
        $this
            ->setName('objects:commit')
            ->setDescription('Splash: Commit Objects Changes for a Given Object Type')
            ->addOption('delay', "d", InputOption::VALUE_OPTIONAL, 'Pause in Seconds between two Commits', 1)
            ->addOption('all', "a", InputOption::VALUE_NONE, 'Commit Changes for All Objects', null)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Do Object Changes Commit (Else >> dry run)', null)
        ;

        parent::configure();
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
        // Init & Splash Screen
        $this->init($input, $output);
        $this->loadInputs($input);
        $this->renderTitle("Commit Objects Changes");
        //====================================================================//
        // Count Total Number of Objects
        if ($this->total <= 0) {
            Splash::log()->war("No Objects found for Commit");
            $this->renderLogs();

            return;
        }
        //====================================================================//
        // Init Results Table
        $this->renderTableHead();
        //====================================================================//
        // Walk on All Objects
        while ($this->current < $this->total) {
            //====================================================================//
            // Setup Paging
            $this->setPaging($this->current, $this->batchSize);
            //====================================================================//
            // Load Objects
            $objects = $this->getObjects(true);
            $objectIds = $this->extractIds($objects);
            //====================================================================//
            // Execute Objects Commit
            $status = $this->commitObjectIds($objectIds);
            //====================================================================//
            // Update Status Table
            $this->renderTableRow($objectIds, $status);
            //====================================================================//
            // Inc Current Counter
            $this->current += $this->batchSize;
            //====================================================================//
            // Pause between Two Batch
            sleep($this->batchDelay);
        }
        //====================================================================//
        // Render Splash Logs
        $this->renderLogs();
    }

    /**
     * Execute Commits
     */
    private function commitObjectIds(array $objectIds): string
    {
        //====================================================================//
        // No Force Option >> Skip
        if (!$this->force) {
            return "<comment>Skipped</comment>";
        }
        //====================================================================//
        // Execute Objects Commits
        Splash::commit(
            $this->getObjectType(),
            $objectIds,
            SPL_A_UPDATE,
            "Splash Console",
            "Mass Commit from Splash Console"
        );
        //====================================================================//
        // Clean Messages if OK
        Splash::log()->msg = array();

        return "<info>Ok</info>";
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
        $this->all = (bool) $input->getOption("all");
        $this->force = (bool) $input->getOption("force");
        $batchSize = $input->getOption("limit");
        $this->batchSize = $this->total = (int) (is_scalar($batchSize) ? $batchSize : 10);
        $batchDelay = $input->getOption("delay");
        $this->batchDelay = (int) (is_scalar($batchDelay) ? $batchDelay : 1);

        //====================================================================//
        // All ? Pre-Setup Paging for Title
        if ($this->all) {
            $this->total = $this->getObjectsTotal();
            $this->setPaging(0, $this->total);
        }
    }

    /**
     * Extract Objecst IDs from List
     */
    private function extractIds(array $objectsList): array
    {
        $result = array();

        //====================================================================//
        // Walk on Batch Objects
        foreach ($objectsList as $objectsData) {
            //====================================================================//
            // Safety Check
            if (!isset($objectsData["id"]) || empty($objectsData["id"])) {
                continue;
            }
            $result[] = $objectsData["id"];
        }

        return $result;
    }

    /**
     * Render List Table Head to Console
     */
    private function renderTableHead(): void
    {
        //====================================================================//
        // SF >= 4.1 Append Row to Table
        if (method_exists($this->table, "appendRow")) {
            //====================================================================//
            // Init Results Table
            $this->table = new Table($this->output);
            $this->table->setHeaders(array("Date", "Type", "Result", "IDs",));
            $this->table->render();
        }
    }

    /**
     * Append a Row to Tracking Table
     */
    private function renderTableRow(array $objectIds, string $status): void
    {
        $date = (new \DateTime())->format(SPL_T_DATETIMECAST);
        $objectType = $this->getObjectType();
        $objectsStr = implode(', ', $objectIds);

        //====================================================================//
        // SF >= 4.1 Append Row to Table
        if (method_exists($this->table, "appendRow")) {
            $this->table->appendRow(array($date,$objectType, $status, $objectsStr));

            return;
        }
        //====================================================================//
        // SF < 4.1 Append Raw Console Line
        $this->output->writeln(implode(" | ", array($date,$objectType, $status, $objectsStr)));
    }
}
