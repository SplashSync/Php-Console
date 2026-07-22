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

/**
 * Assemble the Final OpenAPI Document
 *
 * Schemas are exposed via tags (Redoc SchemaDefinition embeds): the document
 * has NO endpoint — paths stays an empty object, required by the OpenAPI spec.
 */
class DocumentBuilder
{
    /**
     * OpenAPI Specification Version
     *
     * @var string
     */
    const OPENAPI_VERSION = '3.0.3';

    /**
     * Assemble the Complete OpenAPI Document
     *
     * @param array $infos   Info block (from InfosBuilder)
     * @param array $tags    Objects tags (from TagBuilder)
     * @param array $schemas Objects schemas (from ObjectSchemaBuilder)
     * @param array $paths   Objects operations paths (from PathsBuilder)
     */
    public static function build(array $infos, array $tags, array $schemas, array $paths = array()): array
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
