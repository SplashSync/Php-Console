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

use ArrayObject;
use Splash\Client\Splash;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract Command for Listing Splash Objects
 */
abstract class AbstractListingCommand extends AbstractCommand
{
    /**
     * Current Object Type to List
     *
     * @var string
     */
    private $objectType;

    /**
     * List Offset
     *
     * @var int
     */
    private $offset;

    /**
     * List Limit
     *
     * @var int
     */
    private $limit;

    /**
     * List Filter
     *
     * @var string
     */
    private $filter;

    /**
     * Configure Symfony Command
     */
    protected function configure(): void
    {
        $this
            ->addArgument('objectType', InputArgument::REQUIRED, 'Specific Object Type to List')
            ->addOption('offset', "o", InputOption::VALUE_OPTIONAL, 'Offeset to Start Listing Objects', 0)
            ->addOption('limit', "l", InputOption::VALUE_OPTIONAL, 'Maximum number of Objects to Show', 10)
            ->addOption('filter', "f", InputOption::VALUE_OPTIONAL, 'Filter Objects List with a String', null)
        ;
    }

    /**
     * Init Listing Command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function init(InputInterface $input, OutputInterface $output): void
    {
        parent::init($input, $output);

        $objectType = $input->getArgument("objectType");
        $this->objectType = (string) (is_scalar($objectType) ? $objectType : "");

        $offset = $input->getOption("offset");
        $this->offset = (int) (is_scalar($offset) ? $offset : 0);

        $limit = $input->getOption("limit");
        $this->limit = (int) (is_scalar($limit) ? $limit : 10);

        $filter = $input->getOption("filter");
        $this->filter = (string) (is_scalar($filter) ? $filter : null);
    }

    /**
     * Get Object Fields with Types Filters
     *
     * @param bool $isListed
     * @param bool $isRead
     * @param bool $isWrite
     *
     * @return array
     */
    protected function getFields(bool $isListed, bool $isRead, bool $isWrite): array
    {
        //====================================================================//
        // Verify Object if Available
        if (!in_array($this->objectType, Splash::objects(), true)) {
            Splash::log()->err("Object of Type ".$this->objectType." not Found");

            return array();
        }

        //====================================================================//
        // Get Objects List
        $fields = Splash::object($this->objectType)->fields();
        $result = array();

        /** @var ArrayObject $field */
        foreach ($fields as $field) {
            //==============================================================================
            //      Filter Non-Listed Fields
            if ($isListed && !$field->inlist) {
                continue;
            }
            //==============================================================================
            //      Filter Non-Readable Fields
            if ($isRead && !$field->read) {
                continue;
            }
            //==============================================================================
            //      Filter Non-Writable Fields
            if ($isWrite && !$field->write) {
                continue;
            }
            $result[] = $field;
        }

        return $result;
    }

    /**
     * Get Objects Listing
     *
     * @param mixed $removeMeta
     *
     * @return array
     */
    protected function getObjects($removeMeta = false): array
    {
        //====================================================================//
        // Verify Object if Available
        if (!in_array($this->objectType, Splash::objects(), true)) {
            Splash::log()->err("Object of Type ".$this->objectType." not Found");

            return array();
        }

        //====================================================================//
        // Get Objects List
        $result = Splash::object($this->objectType)->objectsList($this->filter, $this->getParameters());

        //====================================================================//
        // Remove Meta if Requested
        if ($removeMeta && isset($result["meta"])) {
            unset($result["meta"]);
        }

        return $result;
    }

    /**
     * Get Objects Total Count
     *
     * @return int
     */
    protected function getObjectsTotal(): int
    {
        //====================================================================//
        // Verify Object if Available
        if (!in_array($this->objectType, Splash::objects(), true)) {
            Splash::log()->err("Object of Type ".$this->objectType." not Found");

            return 0;
        }
        //====================================================================//
        // Get Objects List
        $result = Splash::object($this->objectType)->objectsList($this->filter, array(
            "offset" => 0,
            "max" => 5,
        ));
        //====================================================================//
        // Validate Objects List
        if (!$result || !isset($result["meta"]["total"])) {
            return 0;
        }

        //====================================================================//
        // Return Total Objects Count
        return (int) $result["meta"]["total"];
    }

    /**
     * Get Objects Listing Parameters
     *
     * @return array
     */
    protected function getParameters(): array
    {
        return array(
            "offset" => $this->offset,
            "max" => $this->limit,
        );
    }

    /**
     * Render Command Title
     *
     * @param string $prefix
     */
    protected function renderTitle(string $prefix = null): void
    {
        //====================================================================//
        // Splash Screen
        $this->title = is_null($prefix) ? "List Objects" : $prefix;
        $this->title .= " of Type ".$this->objectType;
        $this->title .= " from ".$this->offset;
        $this->title .= " to ".($this->offset + $this->limit);
        if (!empty($this->filter)) {
            $this->title .= " with filter '".$this->filter."'";
        }
        parent::renderTitle();
    }

    /**
     * Get Object Type
     *
     * @return string
     */
    protected function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * Set Listing Bounds
     *
     * @return $this
     */
    protected function setPaging(int $offset, int $limit): self
    {
        $this->offset = $offset;
        $this->limit = $limit;

        return $this;
    }
}
