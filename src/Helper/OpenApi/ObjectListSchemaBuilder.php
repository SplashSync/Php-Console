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
 * Build the LIST Schema of a Splash Object Type
 *
 * Mirror of the real ObjectsList response rows: ONLY fields flagged IN_LIST,
 * rendered in compact form (type + title) — full details live in the main
 * object schema.
 */
class ObjectListSchemaBuilder
{
    use ScalarExtractorTrait;

    /**
     * Naming Suffix for List Schemas
     *
     * @var string
     */
    const SUFFIX = 'ListItem';

    /**
     * Build the List Item Schema of an Object Type
     *
     * @param string   $objectType  Splash Object Type
     * @param array    $description Object Description
     * @param iterable $fields      Object Fields Definitions
     */
    public static function build(string $objectType, array $description, iterable $fields): array
    {
        list($singles, $lists) = FieldsGrouper::group($fields);

        $properties = array();
        //====================================================================//
        // Walk on Single Fields => Keep IN_LIST Fields Only, Compact Form
        foreach ($singles as $fieldId => $field) {
            if (empty($field[SplFieldProps::IN_LIST])) {
                continue;
            }
            $properties[$fieldId] = FieldPropertyBuilder::buildCompact($field);
        }
        //====================================================================//
        // Walk on Fields Lists => Array of IN_LIST Items, Compact Form
        foreach ($lists as $listName => $items) {
            $itemsProperties = array();
            foreach ($items as $itemId => $field) {
                if (empty($field[SplFieldProps::IN_LIST])) {
                    continue;
                }
                $itemsProperties[$itemId] = FieldPropertyBuilder::buildCompact($field);
            }
            if (empty($itemsProperties)) {
                continue;
            }
            $properties[$listName] = array(
                'type' => 'array',
                'title' => ucfirst((string) $listName),
                'items' => array('type' => 'object', 'properties' => $itemsProperties),
            );
        }

        //====================================================================//
        // Assemble List Item Schema
        return array(
            'type' => 'object',
            'title' => self::toString($description, SplObjectProps::NAME, $objectType).' (List Item)',
            'description' => 'Fields returned by objects list requests — fields flagged `In List` only.',
            'properties' => $properties,
        );
    }
}
