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

namespace Splash\Console\Command;

use Splash\Console\Helper\Graphics;
use Splash\Console\Helper\OpenApi\CommonSchemasBuilder;
use Splash\Console\Helper\OpenApi\DocumentBuilder;
use Splash\Console\Helper\OpenApi\InfosBuilder;
use Splash\Console\Helper\OpenApi\ObjectListSchemaBuilder;
use Splash\Console\Helper\OpenApi\ObjectSchemaBuilder;
use Splash\Console\Helper\OpenApi\PathsBuilder;
use Splash\Console\Helper\OpenApi\TagBuilder;
use Splash\Console\Models\AbstractCommand;
use Splash\Core\Client\Splash;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build Splash Server OpenAPI (Swagger) Definition
 *
 * Same model as the Data Manifest: collect everything exposed by the Splash
 * Server (informations, objects, fields) and dump ONE standard OpenAPI file
 * that summarizes the whole server configuration — reusable anywhere to serve
 * more or less formatted documentation (Redoc, Swagger UI, generators...).
 */
class ServerSwaggerCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected string $title = "Build Splash Server Swagger Definition";

    /**
     * Collected OpenAPI Document
     *
     * @var array
     */
    private array $document = array();

    /**
     * Configure Symfony Command
     */
    protected function configure(): void
    {
        $this
            ->setName('splash:server:swagger')
            ->setDescription('[Splash] Build Splash Server OpenAPI Definition (swagger.json)')
            ->addOption(
                'output',
                null,
                InputOption::VALUE_REQUIRED,
                'Target file path for the OpenAPI definition',
                'swagger.json'
            )
            ->configureManagerOptions()
        ;
    }

    /**
     * Execute Symfony Command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = false;
        //====================================================================//
        // Init & Splash Screen
        $this->init($input, $output);
        $this->renderTitle();
        //====================================================================//
        // Notice internal routines we are in server request mode
        if (!defined("SPLASH_SERVER_MODE")) {
            define("SPLASH_SERVER_MODE", true);
        }
        //====================================================================//
        // Execute Splash Self-Tests
        $selfTests = $this->isManagerMode()
                ? $this->getConnector()->selfTest()
                : Splash::selfTest()
        ;
        //====================================================================//
        // Collect Server Data & Build OpenAPI Document
        if ($selfTests) {
            $result = $this->buildDocument();
            //====================================================================//
            // Write OpenAPI File
            if ($result) {
                $result = $this->writeDocument();
            }
        }
        //====================================================================//
        // Render Splash Logs
        $this->renderLogs();
        //====================================================================//
        // Render Result Icon
        Graphics::renderResult($output, $result, $this->title);

        return 0;
    }

    /**
     * Build OpenAPI Document from Server Data
     *
     * @return bool
     */
    private function buildDocument(): bool
    {
        //====================================================================//
        // Read Server Informations
        $informations = Splash::informations();
        if (empty($informations->count())) {
            return Splash::log()->errTrace("No Server Informations Found!!");
        }
        //====================================================================//
        // Read Available Objects Types
        $objectsTypes = Splash::objects();
        if (empty($objectsTypes)) {
            return Splash::log()->errTrace("No Objects Types Found!!");
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
                return Splash::log()->errTrace("Object ".$objectType." has no Description.");
            }
            //====================================================================//
            // Read Object Fields
            $fields = Splash::object($objectType)->fields();
            if (empty($fields)) {
                return Splash::log()->errTrace("Object ".$objectType." has no Fields Defined.");
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
        $this->document = DocumentBuilder::build(
            InfosBuilder::build((array) $informations),
            $tags,
            $schemas,
            $paths
        );

        return true;
    }

    /**
     * Write OpenAPI Document to Target File
     *
     * @return bool
     */
    private function writeDocument(): bool
    {
        //====================================================================//
        // Resolve Target Path (relative to current dir)
        $output = $this->input->getOption('output');
        $output = is_string($output) && !empty($output) ? $output : 'swagger.json';
        $path = str_starts_with($output, '/') ? $output : getcwd().'/'.$output;

        //====================================================================//
        // Encode & Write Document
        $json = json_encode($this->document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || !file_put_contents($path, $json)) {
            return Splash::log()->errTrace("Unable to write OpenAPI file: ".$path);
        }

        return Splash::log()->msg("OpenAPI definition written to ".$path);
    }
}
