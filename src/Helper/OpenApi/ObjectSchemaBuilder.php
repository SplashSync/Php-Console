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

namespace Splash\Console\Helper\OpenApi;

use Splash\Core\Dictionary\Fields\SplFieldProps;
use Splash\Core\Dictionary\Objects\SplObjectProps;

/**
 * Build the OpenAPI Schema of a Splash Object Type from its Fields
 */
class ObjectSchemaBuilder
{
    use ScalarExtractorTrait;

    /**
     * Build the Schema of an Object Type
     *
     * @param string   $objectType  Splash Object Type
     * @param array    $description Object Description
     * @param iterable $fields      Object Fields Definitions
     */
    public static function build(string $objectType, array $description, iterable $fields): array
    {
        list($singles, $lists) = FieldsGrouper::group($fields);

        $properties = array();
        $required = array();
        //====================================================================//
        // Walk on Single Fields
        foreach ($singles as $fieldId => $field) {
            $properties[$fieldId] = FieldPropertyBuilder::build($field);
            if (!empty($field[SplFieldProps::REQUIRED])) {
                $required[] = $fieldId;
            }
        }
        //====================================================================//
        // Walk on Fields Lists => One Array Property per List
        foreach ($lists as $listName => $items) {
            $properties[$listName] = self::buildListProperty((string) $listName, $items);
        }

        //====================================================================//
        // Assemble Object Schema
        $schema = array(
            'type' => 'object',
            'title' => self::toString($description, SplObjectProps::NAME, $objectType),
            'description' => self::toString($description, SplObjectProps::DESC),
            'x-splash-type' => $objectType,
            'properties' => $properties,
        );
        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    //==============================================================================
    // Private Methods
    //==============================================================================

    /**
     * Build the Array Property of a Fields List (items grouped by list name)
     *
     * @param array<string, array> $items List Items Fields, by Item Id
     */
    private static function buildListProperty(string $listName, array $items): array
    {
        $properties = array();
        $required = array();
        //====================================================================//
        // Walk on List Items Fields
        foreach ($items as $itemId => $field) {
            $properties[$itemId] = FieldPropertyBuilder::build($field);
            if (!empty($field[SplFieldProps::REQUIRED])) {
                $required[] = $itemId;
            }
        }
        //====================================================================//
        // Assemble List Items Schema
        $itemsSchema = array('type' => 'object', 'properties' => $properties);
        if (!empty($required)) {
            $itemsSchema['required'] = $required;
        }

        return array(
            'type' => 'array',
            'title' => ucfirst($listName),
            'x-splash-list' => $listName,
            'items' => $itemsSchema,
        );
    }
}
