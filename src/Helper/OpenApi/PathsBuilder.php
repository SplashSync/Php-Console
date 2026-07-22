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
 * Build the OpenAPI Paths of a Splash Object Type
 *
 * Splash is a TASKS protocol, not REST: the four operations (list, get, set,
 * delete) are modeled as POST requests whose JSON bodies mirror the REAL
 * Splash task params, and whose responses mirror the task data payload
 * (cf. phpcore Server/Router/Objects.php):
 *
 * - list   => params {filters, params{max, offset, sortfield, sortorder}}
 *             data = rows of IN_LIST fields (+ meta {total, current})
 * - get    => params {id, fields[]}          data = full object data
 * - set    => params {id|null, fields{...}}  data = object id
 * - delete => params {id}                    data = boolean
 */
class PathsBuilder
{
    use ScalarExtractorTrait;

    //==============================================================================
    // Public Methods
    //==============================================================================

    /**
     * Build the Four Operations Paths of an Object Type
     *
     * @param string $objectType  Splash Object Type
     * @param array  $description Object Description
     */
    public static function build(string $objectType, array $description): array
    {
        $tag = self::toString($description, SplObjectProps::NAME, $objectType);
        $base = '/'.strtolower($objectType);

        return array(
            $base.'/list' => array('post' => self::buildList($objectType, $tag)),
            $base.'/get' => array('post' => self::buildGet($objectType, $tag)),
            $base.'/set' => array('post' => self::buildSet($objectType, $tag)),
            $base.'/delete' => array('post' => self::buildDelete($objectType, $tag)),
        );
    }

    //==============================================================================
    // Private Methods — Operations
    //==============================================================================

    /**
     * Build the ObjectsList Operation
     */
    private static function buildList(string $objectType, string $tag): array
    {
        //====================================================================//
        // Request: filters + pagination params
        $request = array(
            'type' => 'object',
            'properties' => array(
                'filters' => array(
                    'type' => 'string', 'nullable' => true,
                    'description' => 'Text filter applied on searchable fields',
                ),
                'params' => array(
                    'type' => 'object',
                    'properties' => array(
                        'max' => array('type' => 'integer', 'description' => 'Maximum number of results'),
                        'offset' => array('type' => 'integer', 'description' => 'List start offset'),
                        'sortfield' => array('type' => 'string', 'description' => 'Field id used to sort the list'),
                        'sortorder' => array('type' => 'string', 'enum' => array('ASC', 'DESC')),
                    ),
                ),
            ),
        );
        //====================================================================//
        // Response: list items (IN_LIST fields only) — meta appended by server
        $response = array(
            'type' => 'array',
            'items' => self::toSchemaRef($objectType.ObjectListSchemaBuilder::SUFFIX),
        );

        return self::buildOperation(
            $tag,
            'List '.$tag,
            'list'.$objectType,
            "Read objects list — rows only carry fields flagged `In List`.\n\n"
            ."The server appends a `meta` entry to the result: `{ total, current }` counters.",
            $request,
            $response
        );
    }

    /**
     * Build the Get Operation
     */
    private static function buildGet(string $objectType, string $tag): array
    {
        $request = array(
            'type' => 'object',
            'required' => array('id'),
            'properties' => array(
                'id' => array('type' => 'string', 'description' => 'Object identifier'),
                'fields' => array(
                    'type' => 'array',
                    'items' => array('type' => 'string'),
                    'description' => 'Ids of the fields to read',
                ),
            ),
        );

        return self::buildOperation(
            $tag,
            'Read '.$tag,
            'get'.$objectType,
            'Read object data — only requested fields are returned.',
            $request,
            self::toSchemaRef($objectType)
        );
    }

    /**
     * Build the Set Operation
     */
    private static function buildSet(string $objectType, string $tag): array
    {
        $request = array(
            'type' => 'object',
            'required' => array('fields'),
            'properties' => array(
                'id' => array(
                    'type' => 'string', 'nullable' => true,
                    'description' => 'Object identifier — `null` to create a new object',
                ),
                'fields' => self::toSchemaRef($objectType),
            ),
        );
        $response = array(
            'type' => 'string',
            'description' => 'Identifier of the created or updated object',
        );
        //====================================================================//
        // Write Failure => HTTP 500 with null payload
        $writeResponses = array(
            '500' => array(
                'description' => 'Write failure — the server answers `null`. '
                    .'Check `Splash::log()` messages for error details.',
                'content' => array('application/json' => array('schema' => array(
                    'nullable' => true,
                    'description' => 'Always `null` on write failure',
                ))),
            ),
        );

        return self::buildOperation(
            $tag,
            'Write '.$tag,
            'set'.$objectType,
            'Create or update object data — read-only fields are not writable and hidden from this request.',
            $request,
            $response,
            $writeResponses
        );
    }

    /**
     * Build the Delete Operation
     */
    private static function buildDelete(string $objectType, string $tag): array
    {
        $request = array(
            'type' => 'object',
            'required' => array('id'),
            'properties' => array(
                'id' => array('type' => 'string', 'description' => 'Object identifier'),
            ),
        );
        $response = array(
            'type' => 'boolean',
            'description' => 'True when the object was deleted',
        );

        return self::buildOperation(
            $tag,
            'Delete '.$tag,
            'delete'.$objectType,
            'Delete an object.',
            $request,
            $response
        );
    }

    //==============================================================================
    // Private Methods — Low Level
    //==============================================================================

    /**
     * Assemble a JSON Operation (request body + 200 response + extra responses)
     *
     * @param array $extraResponses Additional responses, by http code (e.g. 500)
     */
    private static function buildOperation(
        string $tag,
        string $summary,
        string $operationId,
        string $description,
        array $request,
        array $response,
        array $extraResponses = array()
    ): array {
        return array(
            'tags' => array($tag),
            'summary' => $summary,
            'operationId' => $operationId,
            'description' => $description,
            'requestBody' => array(
                'required' => true,
                'content' => array('application/json' => array('schema' => $request)),
            ),
            //====================================================================//
            // 200 answers the task data payload directly. Extra codes (e.g.
            // 500 on writes) are appended per operation.
            // array_replace, NOT array_merge: http codes are numeric strings,
            // array_merge would renumber them and break the responses object
            'responses' => array_replace(
                array(
                    '200' => array(
                        'description' => 'Task data payload',
                        'content' => array('application/json' => array('schema' => $response)),
                    ),
                ),
                $extraResponses
            ),
        );
    }

    /**
     * Build a Reference to a Components Schema
     */
    private static function toSchemaRef(string $schema): array
    {
        return array('$ref' => '#/components/schemas/'.$schema);
    }
}
