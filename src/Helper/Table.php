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

use ArrayObject;
use Splash\Components\FieldsManager;
use Symfony\Component\Console\Helper\Table as baseTable;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Splash Console Table
 */
class Table extends baseTable
{
    /**
     * Class Constructor
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        parent::__construct($output);

        // Setup Style
        $this->setStyle('borderless');
    }

    /**
     * Render Table of Objects Data
     *
     * @param array $fields
     * @param array $data
     */
    public function renderObjectsList(array $fields, array $data): void
    {
        //====================================================================//
        // Prepare Table Header
        $header = array("ID");
        foreach ($fields as $field) {
            $header[] = $field->name;
        }
        $this->setHeaders($header);
        //====================================================================//
        // Detect Metadata
        if (isset($data["meta"])) {
            $meta = $data["meta"];
            unset($data["meta"]);
        }
        //====================================================================//
        // Walk on List Objects
        foreach ($data as $objectData) {
            $this->renderObjectsListRow($fields, $objectData);
        }
        //====================================================================//
        // Detect Metadatas
        if (isset($meta) && method_exists($this, "setFooterTitle")) {
            $this->setFooterTitle($meta["current"]." objects of ".$meta["total"]);
        }
        //====================================================================//
        // Render Data Table
        $this->render();
    }

    /**
     * Render Table with Data of A Given Object
     *
     * @param array $fields
     * @param array $object
     */
    public function renderObjectData(array $fields, array $object): void
    {
        //====================================================================//
        // Prepare Table Header
        $this->setHeaders(array("Field ID", "Name", "Value"));

        //====================================================================//
        // Walk on List Objects
        /** @var ArrayObject $field */
        foreach ($fields as $field) {
            $lineData = array();
            //====================================================================//
            // Field Id
            $lineData[] = "<info>".$field->id."</info>";
            //====================================================================//
            // Field Name
            $lineData[] = $field->name;
            //====================================================================//
            // Field Data
            $lineData[] = $this->getObjectDataString(
                $field,
                FieldsManager::extractRawData($object, $field->id)
            );
            //====================================================================//
            // Add Object Row
            $this->addRow($lineData);
        }

        //====================================================================//
        // Render Data Table
        $this->render();
    }

    /**
     * Render Table of Objects Data
     *
     * @param array $fields
     * @param array $objectData
     */
    public function renderObjectsListRow(array $fields, array $objectData): void
    {
        $lineData = array();
        //====================================================================//
        // Read Object Id
        $lineData[] = "<info>".(isset($objectData["id"]) ? $objectData["id"] : "-?-")."</info>";
        //====================================================================//
        // Read Object Fields
        /** @var ArrayObject $field */
        foreach ($fields as $field) {
            if (!isset($objectData[$field->id]) || !is_scalar($objectData[$field->id])) {
                $lineData[] = "<comment>-null-</comment>";

                continue;
            }
            $lineData[] = $objectData[$field->id];
        }
        //====================================================================//
        // Add Object Row
        $this->addRow($lineData);
    }

    /**
     * Render Table with Data of A Given Object
     *
     * @param null|ArrayObject $field
     * @param mixed            $fieldData
     *
     * @return string
     */
    private function getObjectDataString(?ArrayObject $field, $fieldData): string
    {
        //====================================================================//
        // Lists Field Data
        if ($field && FieldsManager::isListField($field->id) && is_iterable($fieldData)) {
            $result = "";
            foreach ($fieldData as $itemData) {
                $result .= " - ".$this->getObjectDataString(null, $itemData);
            }

            return $result;
        }

        //====================================================================//
        // Simple Field Data
        if (is_array($fieldData)) {
            return "[".implode(", ", $fieldData)."]";
        }

        return (string) is_scalar($fieldData) ? $fieldData : print_r($fieldData, true);
    }
}
