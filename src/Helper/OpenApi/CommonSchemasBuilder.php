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

/**
 * Build the Splash Common Sub-Schemas (Price, File, Image)
 *
 * Structures mirror the phpcore helpers encoding (PricesHelper, FilesHelper,
 * ImagesHelper): referenced by field properties of types price / file /
 * stream / img instead of opaque objects.
 */
class CommonSchemasBuilder
{
    /**
     * Splash Common Schemas Names
     *
     * @var string
     */
    const PRICE = 'SplashPrice';

    /** @var string */
    const FILE = 'SplashFile';

    /** @var string */
    const IMAGE = 'SplashImage';

    /**
     * Build All Common Schemas
     *
     * @return array<string, array>
     */
    public static function build(): array
    {
        return array(
            self::PRICE => self::buildPrice(),
            self::FILE => self::buildFile(),
            self::IMAGE => self::buildImage(),
        );
    }

    //==============================================================================
    // Private Methods
    //==============================================================================

    /**
     * Build the Splash Price Schema (cf. PricesHelper::encode)
     */
    private static function buildPrice(): array
    {
        return array(
            'type' => 'object',
            'title' => 'Splash Price',
            'description' => 'Price structure with tax details and currency',
            'properties' => array(
                'ht' => array('type' => 'number', 'description' => 'Tax excluded amount'),
                'ttc' => array('type' => 'number', 'description' => 'Tax included amount'),
                'tax' => array('type' => 'number', 'description' => 'Tax amount'),
                'vat' => array('type' => 'number', 'description' => 'VAT rate (percent)'),
                'base' => array(
                    'type' => 'integer', 'enum' => array(0, 1),
                    'description' => 'Price base: 0 = tax excluded, 1 = tax included',
                ),
                'code' => array('type' => 'string', 'description' => 'Currency ISO code'),
                'symbol' => array('type' => 'string', 'description' => 'Currency symbol'),
                'name' => array('type' => 'string', 'description' => 'Currency name'),
            ),
        );
    }

    /**
     * Build the Splash File Schema (cf. FilesHelper::encode)
     */
    private static function buildFile(): array
    {
        return array(
            'type' => 'object',
            'title' => 'Splash File',
            'description' => 'File structure with checksum for synchronization',
            'properties' => array(
                'name' => array('type' => 'string', 'description' => 'Human readable file name'),
                'filename' => array('type' => 'string', 'description' => 'File name with extension'),
                'path' => array('type' => 'string', 'description' => 'File path or identifier on server'),
                'url' => array('type' => 'string', 'format' => 'uri', 'description' => 'Public url, if available'),
                'md5' => array('type' => 'string', 'description' => 'File contents checksum'),
                'size' => array('type' => 'integer', 'description' => 'File size in bytes'),
            ),
        );
    }

    /**
     * Build the Splash Image Schema (cf. ImagesHelper::encode)
     */
    private static function buildImage(): array
    {
        return array(
            'title' => 'Splash Image',
            'description' => 'Image structure: a Splash File with dimensions',
            'allOf' => array(
                array('$ref' => '#/components/schemas/'.self::FILE),
                array(
                    'type' => 'object',
                    'properties' => array(
                        'width' => array('type' => 'integer', 'description' => 'Image width in pixels'),
                        'height' => array('type' => 'integer', 'description' => 'Image height in pixels'),
                    ),
                ),
            ),
        );
    }
}
