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
use Splash\Core\Interfaces\Fields\FieldTemplateInterface;

/**
 * Build an OpenAPI Property from a Splash Field Definition
 *
 * Maps Splash field types to OpenAPI types & formats; Splash specifics
 * (microdata, access, raw type) travel as x-splash-* / x-schema-org-*
 * vendor extensions so nothing is lost for future tooling.
 */
class FieldPropertyBuilder
{
    use ScalarExtractorTrait;

    /**
     * Splash Field Types => OpenAPI Types & Formats
     *
     * @var array<string, array<string, string>>
     */
    /**
     * Boolean Field Flags => Markdown Badges
     *
     * Appended at the bottom of the property description when the flag is
     * active. Required / read / write are NOT badged: natively rendered by
     * OpenAPI (required list, readOnly / writeOnly).
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

    const TYPES_MAP = array(
        'bool' => array('type' => 'boolean'),
        'int' => array('type' => 'integer'),
        'double' => array('type' => 'number'),
        'varchar' => array('type' => 'string'),
        'text' => array('type' => 'string'),
        'inline' => array('type' => 'string'),
        'email' => array('type' => 'string', 'format' => 'email'),
        'url' => array('type' => 'string', 'format' => 'uri'),
        'date' => array('type' => 'string', 'format' => 'date'),
        'datetime' => array('type' => 'string', 'format' => 'date-time'),
        //====================================================================//
        // Splash types without a standard OpenAPI format: the format keyword
        // is an OPEN string per spec (unknown formats must be ignored), so it
        // legally carries the Splash type — Redoc shows it next to the type.
        'phone' => array('type' => 'string', 'format' => 'phone'),
        'lang' => array('type' => 'string', 'format' => 'lang'),
        'country' => array('type' => 'string', 'format' => 'country'),
        'state' => array('type' => 'string', 'format' => 'state'),
        'currency' => array('type' => 'string', 'format' => 'currency'),
        'mvarchar' => array('type' => 'object', 'format' => 'mvarchar'),
        'mtext' => array('type' => 'object', 'format' => 'mtext'),
        'objectid' => array('type' => 'string', 'format' => 'objectid'),
    );

    /**
     * Splash Structured Field Types => Common Sub-Schemas References
     *
     * @var array<string, string>
     */
    const REFS_MAP = array(
        'price' => CommonSchemasBuilder::PRICE,
        'file' => CommonSchemasBuilder::FILE,
        'stream' => CommonSchemasBuilder::FILE,
        'img' => CommonSchemasBuilder::IMAGE,
    );

    //==============================================================================
    // Public Methods
    //==============================================================================

    /**
     * Build an OpenAPI Property from a Splash Field Definition
     */
    public static function build(array $field): array
    {
        //====================================================================//
        // Resolve Base Type: strip list marker & object id target
        $splashType = self::toString($field, SplFieldProps::TYPE, 'varchar');
        $baseType = self::toBaseType($splashType);

        //====================================================================//
        // Map to OpenAPI Type + Field Labels
        // Structured types (price, file, img...) reference a common sub-schema
        // via allOf: OpenAPI 3.0 ignores siblings of a bare $ref.
        $property = array_merge(
            self::toBaseProperty($baseType),
            array(
                'title' => self::toString($field, SplFieldProps::NAME),
                'description' => self::buildDescription($field),
                'x-splash-type' => $splashType,
            )
        );

        //====================================================================//
        // Format Carries the RAW Splash Type (varchar, price@list,
        // objectid::Product...): open string per spec, displayed by Redoc
        // right next to the mapped OpenAPI type.
        $property['format'] = $splashType;
        //====================================================================//
        // Complete with Splash Specifics
        $property = array_merge($property, self::buildExtras($field));
        //====================================================================//
        // Add Template Technical Description (AI agents & tooling)
        $techDescription = self::resolveTechnicalDescription($field);
        if (!empty($techDescription)) {
            $property['x-technical-description'] = $techDescription;
        }
        //====================================================================//
        // Add Valid Example Value (dates, phones, prices, object ids...)
        $example = self::buildExample($baseType, $splashType);
        if (null !== $example) {
            $property['example'] = $example;
        }

        return $property;
    }

    /**
     * Build a COMPACT OpenAPI Property from a Splash Field Definition
     *
     * Minimal detail version used inside requests & responses payloads:
     * mapped type + title only — full details live in the object schema.
     */
    public static function buildCompact(array $field): array
    {
        //====================================================================//
        // Resolve Base Type: strip list marker & object id target
        $splashType = self::toString($field, SplFieldProps::TYPE, 'varchar');

        return array_merge(
            self::toBaseProperty(self::toBaseType($splashType)),
            array('title' => self::toString($field, SplFieldProps::NAME))
        );
    }

    //==============================================================================
    // Private Methods
    //==============================================================================

    /**
     * Build the Property Description: description + details list
     *
     * Descriptions are CommonMark: field metadata (sync mode, template,
     * microdata, options, flags badges) renders as a bullet list at the
     * bottom of the field description in Redoc. The raw Splash type is NOT
     * repeated here: it travels in the format keyword, displayed by Redoc.
     */
    private static function buildDescription(array $field): string
    {
        return implode("\n\n", array_filter(array(
            self::toString($field, SplFieldProps::DESC),
            FieldDetailsBuilder::build($field),
        )));
    }

    /**
     * Build a Valid Example Value for a Splash Base Type
     *
     * Phones use ISO format, dates & datetimes use the Splash formats,
     * prices get a random tax-excluded amount in Euros, object links use
     * the Splash ObjectsHelper encoding: {id}::{ObjectType}.
     *
     * @return null|array|string
     */
    private static function buildExample(string $baseType, string $splashType)
    {
        switch ($baseType) {
            case 'phone':
                return '+33612345678';
            case 'date':
                return date('Y-m-d');
            case 'datetime':
                return date('Y-m-d H:i:s');
            case 'price':
                return self::buildPriceExample();
            case 'objectid':
                return self::buildObjectIdExample($splashType);
        }

        return null;
    }

    /**
     * Build a Valid Splash Object Link Example ({id}::{ObjectType})
     */
    private static function buildObjectIdExample(string $splashType): ?string
    {
        //====================================================================//
        // Extract Target Object Type (objectid::Product => Product)
        $target = (string) substr(
            str_replace('@list', '', $splashType),
            strlen('objectid::')
        );
        if (empty($target)) {
            return null;
        }

        return sprintf('%d::%s', mt_rand(1, 999), $target);
    }

    /**
     * Resolve the Technical Description from the Field Source Template
     *
     * The wire field format does not carry it: when the field declares its
     * template (options.tmpl, dotted notation), and the template class is
     * available in the running server, read getTechnicalDescription() on a
     * fresh instance. Best-effort: any failure means no extension.
     */
    private static function resolveTechnicalDescription(array $field): string
    {
        //====================================================================//
        // Read Template Class from Field Options
        $optionsKey = SplFieldProps::OPTIONS;
        $options = (isset($field[$optionsKey]) && is_array($field[$optionsKey])) ? $field[$optionsKey] : array();
        $template = self::toString($options, SplFieldConstraints::TEMPLATE);
        if (empty($template)) {
            return '';
        }

        //====================================================================//
        // Resolve Template Class & Read Technical Description
        $class = str_replace('.', '\\', $template);
        if (!class_exists($class) || !is_subclass_of($class, FieldTemplateInterface::class)) {
            return '';
        }

        try {
            return (new $class())->getTechnicalDescription();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Build a Valid Splash Price Example: random amount in Euros, 20% VAT
     */
    private static function buildPriceExample(): array
    {
        $taxExcl = mt_rand(500, 19999) / 100;
        $vat = 20.0;

        return array(
            'base' => 0,
            'ht' => $taxExcl,
            'vat' => $vat,
            'tax' => round($taxExcl * ($vat / 100), 2),
            'ttc' => round($taxExcl * (1 + $vat / 100), 2),
            'code' => 'EUR',
            'symbol' => '€',
            'name' => 'Euro',
        );
    }

    /**
     * Build Splash Specific Property Extras (access, microdata, choices)
     */
    private static function buildExtras(array $field): array
    {
        $extras = array();
        //====================================================================//
        // Read / Write Access
        if (!empty($field[SplFieldProps::READ]) && empty($field[SplFieldProps::WRITE])) {
            $extras['readOnly'] = true;
        }
        if (empty($field[SplFieldProps::READ]) && !empty($field[SplFieldProps::WRITE])) {
            $extras['writeOnly'] = true;
        }
        //====================================================================//
        // Microdata (schema.org)
        if (!empty($field[SplFieldProps::MICRODATA_URL]) && is_string($field[SplFieldProps::MICRODATA_URL])) {
            $extras['x-schema-org-itemtype'] = $field[SplFieldProps::MICRODATA_URL];
            $extras['x-schema-org-itemprop'] = self::toString($field, SplFieldProps::MICRODATA_PROP);
        }
        //====================================================================//
        // Choices => Enum
        $enum = self::buildEnum($field);
        if (!empty($enum)) {
            $extras['enum'] = $enum;
        }

        return $extras;
    }

    /**
     * Build Enum Values from Splash Field Choices
     *
     * @return array<int, scalar>
     */
    private static function buildEnum(array $field): array
    {
        $choicesKey = SplFieldProps::CHOICES;
        $choices = (isset($field[$choicesKey]) && is_array($field[$choicesKey])) ? $field[$choicesKey] : array();
        $enum = array();
        foreach ($choices as $choice) {
            $choice = (array) $choice;
            if (isset($choice['key']) && is_scalar($choice['key'])) {
                $enum[] = $choice['key'];
            }
        }

        return $enum;
    }

    /**
     * Resolve the Splash Base Type: strip list marker & object id target
     */
    private static function toBaseType(string $splashType): string
    {
        return (string) strtok(str_replace('@list', '', $splashType), ':');
    }

    /**
     * Map a Splash Base Type to its OpenAPI Property Base
     *
     * Structured types return an allOf sub-schema reference, scalar types
     * return their mapped type + format.
     */
    private static function toBaseProperty(string $baseType): array
    {
        //====================================================================//
        // Structured Types => Common Sub-Schema Reference
        $ref = self::REFS_MAP[$baseType] ?? null;
        if (null !== $ref) {
            return array('allOf' => array(
                array('$ref' => '#/components/schemas/'.$ref),
            ));
        }

        return self::TYPES_MAP[$baseType] ?? array('type' => 'string');
    }
}
