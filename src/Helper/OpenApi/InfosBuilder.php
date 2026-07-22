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
 * Build the OpenAPI Info Block from Splash Server Informations
 */
class InfosBuilder
{
    use ScalarExtractorTrait;

    /**
     * Server informations keys excluded from the public document
     *
     * @var array
     */
    const SERVER_PRIVATE = array(
        'company', 'address', 'zip', 'town', 'country',
        'www', 'email', 'phone', 'icoraw', 'logoraw'
    );

    /**
     * Build the Info Block from Server Informations
     *
     * Private keys are filtered out; everything else travels under the
     * x-splash-server extension so nothing is lost for future tooling.
     */
    public static function build(array $informations): array
    {
        //====================================================================//
        // Remove Private Informations
        foreach (self::SERVER_PRIVATE as $key) {
            unset($informations[$key]);
        }

        //====================================================================//
        // Assemble Info Block
        return array(
            'title' => self::toString($informations, 'shortdesc', 'Splash Server'),
            'description' => self::toString($informations, 'longdesc'),
            'version' => self::toString($informations, 'moduleversion', '1.0.0'),
            'x-splash-server' => $informations,
        );
    }
}
