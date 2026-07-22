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

use Splash\Core\Dictionary\Fields\SplFieldConstraints;
use Splash\Core\Dictionary\Fields\SplFieldProps;
use Splash\Core\Dictionary\Fields\SplSyncMode;

/**
 * Build the Markdown Details List of a Splash Field
 *
 * Bullet list appended to the field description, collecting a maximum of
 * metadata: preferred sync mode, source template (ready to copy), microdata
 * mapping, extra field options — and the active flags badges as last bullet.
 */
class FieldDetailsBuilder
{
    use ScalarExtractorTrait;

    /**
     * Preferred Sync Modes => Markdown Comment (BOTH is default, not shown)
     *
     * @var array<string, string>
     */
    const SYNC_COMMENTS = array(
        SplSyncMode::READ => '**Prefer Read** — field should only be read & exported to other servers',
        SplSyncMode::WRITE => '**Prefer Write** — field should only be written & imported from other servers',
        SplSyncMode::NONE => '**No Sync** — field prefers being left unchanged, without any synchronization',
    );

    /**
     * Boolean Field Flags => Markdown Badges (rendered as last bullet)
     *
     * Required / read / write are NOT badged: natively rendered by OpenAPI
     * (required list, readOnly / writeOnly).
     *
     * @var array<string, string>
     */
    const FLAGS_BADGES = array(
        SplFieldProps::PRIMARY => '`🔑 Primary`',
        SplFieldProps::INDEX => '`🔎 Indexed`',
        SplFieldProps::IN_LIST => '`📋 In List`',
        SplFieldProps::HIDDEN_IN_LIST => '`🫥 Hidden in List`',
        SplFieldProps::LOG => '`📜 Logged`',
        SplFieldProps::NO_TEST => '`🧪 No Tests`',
    );

    //==============================================================================
    // Public Methods
    //==============================================================================

    /**
     * Build the Field Details Bullet List
     */
    public static function build(array $field): string
    {
        $optionsKey = SplFieldProps::OPTIONS;
        $options = (isset($field[$optionsKey]) && is_array($field[$optionsKey])) ? $field[$optionsKey] : array();

        //====================================================================//
        // Collect Details Bullets — Flags Badges always last
        $bullets = array_filter(array(
            self::buildSyncMode($field),
            self::buildTemplate($options),
            self::buildMicrodata($field),
            self::buildOptions($options),
            self::buildBadges($field),
        ));

        return implode("\n", $bullets);
    }

    //==============================================================================
    // Private Methods
    //==============================================================================

    /**
     * Build the Preferred Sync Mode Bullet (silent when BOTH / absent)
     */
    private static function buildSyncMode(array $field): string
    {
        $syncMode = self::toString($field, SplFieldProps::SYNC_MODE);
        $comment = self::SYNC_COMMENTS[$syncMode] ?? null;

        return $comment ? '- '.$comment : '';
    }

    /**
     * Build the Source Template Bullet, Ready to Copy
     *
     * Template travels in field options in dotted notation
     * (Splash.Templates.Xxx): rendered as a copy-paste ready class constant.
     */
    private static function buildTemplate(array $options): string
    {
        $template = self::toString($options, SplFieldConstraints::TEMPLATE);
        if (empty($template)) {
            return '';
        }

        return sprintf('- **Template:** `%s::class`', str_replace('.', '\\', $template));
    }

    /**
     * Build the Microdata Bullet (itemtype + itemprop)
     */
    private static function buildMicrodata(array $field): string
    {
        $itemType = self::toString($field, SplFieldProps::MICRODATA_URL);
        if (empty($itemType)) {
            return '';
        }

        return sprintf(
            '- **Microdata:** `%s` → `%s`',
            $itemType,
            self::toString($field, SplFieldProps::MICRODATA_PROP)
        );
    }

    /**
     * Build the Extra Options Bullet with Nested Sub-List (template excluded)
     */
    private static function buildOptions(array $options): string
    {
        //====================================================================//
        // Remove Template: rendered by its own bullet
        unset($options[SplFieldConstraints::TEMPLATE]);

        $subItems = array();
        //====================================================================//
        // Walk on Options => One Sub-List Item per Key
        foreach ($options as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $subItems[] = sprintf(
                '    - **%s** => %s',
                $key,
                is_scalar($value) ? (string) $value : (string) json_encode($value)
            );
        }
        if (empty($subItems)) {
            return '';
        }

        return "- **Options:**\n".implode("\n", $subItems);
    }

    /**
     * Build the Active Flags Badges Bullet (always last)
     */
    private static function buildBadges(array $field): string
    {
        $badges = array();
        //====================================================================//
        // Collect Badges of Active Flags
        foreach (self::FLAGS_BADGES as $flag => $badge) {
            if (!empty($field[$flag])) {
                $badges[] = $badge;
            }
        }

        return empty($badges) ? '' : '- '.implode(' ', $badges);
    }
}
