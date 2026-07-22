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
 * Build the OpenAPI Tag of a Splash Object Type
 *
 * Fields are exposed WITHOUT any pseudo endpoint: the tag description embeds
 * a Redoc SchemaDefinition, so each object type appears in the sidebar with
 * the full list of its fields rendered from components/schemas.
 */
class TagBuilder
{
    use ScalarExtractorTrait;

    /**
     * Build the Tag of an Object Type, Schema embedded
     *
     * @param string $objectType  Splash Object Type
     * @param array  $description Object Description
     */
    public static function build(string $objectType, array $description): array
    {
        //====================================================================//
        // Tag Description: object markdown description + embedded schema
        $markdown = sprintf(
            "%s\n\n<SchemaDefinition schemaRef=\"#/components/schemas/%s\" />",
            ObjectDescriptionBuilder::build($description),
            $objectType
        );

        return array(
            'name' => self::toString($description, SplObjectProps::NAME, $objectType),
            'description' => $markdown,
        );
    }
}
