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

use ArrayObject;
use Splash\Core\Client\Splash;

/**
 * Build the Complete OpenAPI Document from Splash Server Data
 *
 * Self-contained entry point: collects everything from the running Splash
 * Server (informations, objects, fields) and returns the assembled document
 * as array (toArray) or raw json (toJson) — usable anywhere without any
 * extra configuration. Null on collect failure, details in Splash logs.
 *
 * Schemas are exposed via tags (Redoc SchemaDefinition embeds): the document
 * has NO rest endpoints — paths only carries the Splash tasks operations.
 */
class DocumentBuilder
{
    /**
     * OpenAPI Specification Version
     *
     * @var string
     */
    const OPENAPI_VERSION = '3.0.3';

    //==============================================================================
    // Public Methods
    //==============================================================================

    /**
     * Build the OpenAPI Document as Array
     *
     * @return null|array Null on collect failure, details in Splash logs
     */
    public static function toArray(): ?array
    {
        //====================================================================//
        // Read Server Informations
        $informations = Splash::informations();
        if (empty($informations->count())) {
            return Splash::log()->errNull("No Server Informations Found!!");
        }
        //====================================================================//
        // Read Available Objects Types
        $objectsTypes = Splash::objects();
        if (empty($objectsTypes)) {
            return Splash::log()->errNull("No Objects Types Found!!");
        }
        //====================================================================//
        // Register Splash Common Sub-Schemas (Price, File, Image)
        $schemas = CommonSchemasBuilder::build();
        //====================================================================//
        // Walk on Objects Types => Build Tags, Schemas & Paths
        $tags = $paths = array();
        foreach ($objectsTypes as $objectType) {
            //====================================================================//
            // Read Object Description
            $description = Splash::object($objectType)->description();
            if (empty($description)) {
                return Splash::log()->errNull("Object ".$objectType." has no Description.");
            }
            //====================================================================//
            // Read Object Fields
            $fields = Splash::object($objectType)->fields();
            if (empty($fields)) {
                return Splash::log()->errNull("Object ".$objectType." has no Fields Defined.");
            }
            $tags[] = TagBuilder::build($objectType, (array) $description);
            //====================================================================//
            // Full Schema + Compact List Schema (IN_LIST fields only)
            $schemas[$objectType] = ObjectSchemaBuilder::build($objectType, (array) $description, $fields);
            $schemas[$objectType.ObjectListSchemaBuilder::SUFFIX]
                = ObjectListSchemaBuilder::build($objectType, (array) $description, $fields);
            //====================================================================//
            // Splash Tasks Operations (list / get / set / delete)
            $paths = array_merge($paths, PathsBuilder::build($objectType, (array) $description));
        }

        //====================================================================//
        // Assemble Final Document
        return self::assemble(
            InfosBuilder::build((array) $informations),
            $tags,
            $schemas,
            $paths
        );
    }

    /**
     * Build the OpenAPI Document as Raw Json
     *
     * @return null|string Null on collect or encoding failure
     */
    public static function toJson(): ?string
    {
        $document = self::toArray();
        if (null === $document) {
            return null;
        }
        //====================================================================//
        // Encode Document to Pretty Json
        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : null;
    }

    //==============================================================================
    // Private Methods
    //==============================================================================

    /**
     * Assemble the Final OpenAPI Document
     *
     * @param array $infos   Info block (from InfosBuilder)
     * @param array $tags    Objects tags (from TagBuilder)
     * @param array $schemas Objects schemas (from ObjectSchemaBuilder)
     * @param array $paths   Objects operations paths (from PathsBuilder)
     */
    private static function assemble(array $infos, array $tags, array $schemas, array $paths): array
    {
        return array(
            'openapi' => self::OPENAPI_VERSION,
            'info' => $infos,
            'tags' => $tags,
            //====================================================================//
            // Without endpoints: empty object (ArrayObject encodes to {} in json)
            'paths' => empty($paths) ? new ArrayObject() : $paths,
            'components' => array('schemas' => $schemas),
        );
    }
}
