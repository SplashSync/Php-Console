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

use Splash\Core\Dictionary\Objects\SplObjectProps;

/**
 * Build the Markdown Description of a Splash Object Type
 *
 * OpenAPI descriptions are CommonMark: rendered natively by Redoc at the top
 * of the object section. Composed of three blocks:
 * - a properties list (type, icon, disabled warning)
 * - the object description text
 * - a configuration table (allow / push / pull per CRUD action)
 */
class ObjectDescriptionBuilder
{
    use ScalarExtractorTrait;

    /**
     * CRUD Actions => Object Description Flags (Allowed / Push / Pull)
     *
     * @var array<string, array<int, string>>
     */
    const CONFIG_ROWS = array(
        'Create' => array(
            SplObjectProps::ALLOW_CREATE,
            SplObjectProps::PUSH_CREATED,
            SplObjectProps::PULL_CREATED,
        ),
        'Update' => array(
            SplObjectProps::ALLOW_UPDATE,
            SplObjectProps::PUSH_UPDATED,
            SplObjectProps::PULL_UPDATED,
        ),
        'Delete' => array(
            SplObjectProps::ALLOW_DELETE,
            SplObjectProps::PUSH_DELETED,
            SplObjectProps::PULL_DELETED,
        ),
    );

    //==============================================================================
    // Public Methods
    //==============================================================================

    /**
     * Build the Full Markdown Description of an Object Type
     *
     * @param array $description Object Description
     */
    public static function build(array $description): string
    {
        return implode("\n\n", array_filter(array(
            self::buildProperties($description),
            self::toString($description, SplObjectProps::DESC),
            self::buildConfiguration($description),
        )));
    }

    //==============================================================================
    // Private Methods
    //==============================================================================

    /**
     * Build the Object Properties List (type, icon, disabled)
     */
    private static function buildProperties(array $description): string
    {
        $lines = array();
        //====================================================================//
        // Object Type Code
        $lines[] = sprintf('- **Type:** `%s`', self::toString($description, SplObjectProps::TYPE));
        //====================================================================//
        // Object Icon (fontawesome)
        $icon = self::toString($description, SplObjectProps::ICON);
        if (!empty($icon)) {
            $lines[] = sprintf('- **Icon:** `%s`', $icon);
        }
        //====================================================================//
        // Disabled Objects => Bold Warning Item
        if (!empty($description[SplObjectProps::DISABLED])) {
            $lines[] = '- **⚠️ This Object Type is Disabled**';
        }

        return implode("\n", $lines);
    }

    /**
     * Build the Object Configuration Table (allow / push / pull per action)
     */
    private static function buildConfiguration(array $description): string
    {
        $lines = array(
            '### Configuration',
            '',
            '| Action | Allowed | Push | Pull |',
            '|--------|:-------:|:----:|:----:|',
        );
        //====================================================================//
        // Walk on CRUD Actions => Render Flags Row
        foreach (self::CONFIG_ROWS as $action => $keys) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s |',
                $action,
                self::toFlag($description, $keys[0]),
                self::toFlag($description, $keys[1]),
                self::toFlag($description, $keys[2])
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Render an Object Description Flag as a Markdown Check Mark
     */
    private static function toFlag(array $description, string $key): string
    {
        return empty($description[$key]) ? '🚫' : '✅';
    }
}
