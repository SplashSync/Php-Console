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

/**
 * Group Splash Fields by Lists
 *
 * Splash list fields are flat with composite ids: `variant_sku@variants` is
 * the `variant_sku` item of the `variants` list. This helper splits a fields
 * set into singles and lists, so schema builders can render each list as an
 * array of item objects instead of flat properties.
 */
class FieldsGrouper
{
    use ScalarExtractorTrait;

    /**
     * List Marker in Fields Ids
     *
     * @var string
     */
    const LIST_SPLITTER = '@';

    /**
     * Split Fields Set into Singles & Lists
     *
     * Returns [0] singles as fieldId => field, [1] lists as listName => (itemId => field)
     *
     * @param iterable $fields Object Fields Definitions
     *
     * @return array{0: array<string, array>, 1: array<string, array<string, array>>}
     */
    public static function group(iterable $fields): array
    {
        $singles = $lists = array();
        //====================================================================//
        // Walk on Object Fields
        foreach ($fields as $field) {
            $field = (array) $field;
            $fieldId = self::toString($field, SplFieldProps::ID);
            if (empty($fieldId)) {
                continue;
            }
            //====================================================================//
            // List Item Field => Group by List Name
            if (str_contains($fieldId, self::LIST_SPLITTER)) {
                list($itemId, $listName) = explode(self::LIST_SPLITTER, $fieldId, 2);
                $lists[$listName][$itemId] = $field;

                continue;
            }
            //====================================================================//
            // Single Field
            $singles[$fieldId] = $field;
        }

        return array($singles, $lists);
    }
}
