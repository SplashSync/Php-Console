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
use Splash\Console\Helper\OpenApi\DocumentBuilder;
use Splash\Console\Models\AbstractCommand;
use Splash\Core\Client\Splash;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build Splash Server OpenAPI (Swagger) Definition
 *
 * Same model as the Data Manifest: dump ONE standard OpenAPI file that
 * summarizes the whole server configuration — reusable anywhere to serve
 * more or less formatted documentation (Redoc, Swagger UI, generators...).
 * All the collect & assembly logic lives in OpenApi\DocumentBuilder.
 */
class ServerSwaggerCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected string $title = "Build Splash Server Swagger Definition";

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
        // Build & Write OpenAPI Document
        if ($selfTests) {
            $result = $this->writeDocument();
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
     * Build & Write OpenAPI Document to Target File
     *
     * @return bool
     */
    private function writeDocument(): bool
    {
        //====================================================================//
        // Build OpenAPI Document as Raw Json
        $json = DocumentBuilder::toJson();
        if (null === $json) {
            return false;
        }
        //====================================================================//
        // Resolve Target Path (relative to current dir)
        $output = $this->input->getOption('output');
        $output = is_string($output) && !empty($output) ? $output : 'swagger.json';
        $path = str_starts_with($output, '/') ? $output : getcwd().'/'.$output;
        //====================================================================//
        // Write Document
        if (!file_put_contents($path, $json)) {
            return Splash::log()->errTrace("Unable to write OpenAPI file: ".$path);
        }

        return Splash::log()->msg("OpenAPI definition written to ".$path);
    }
}
