<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Console\Command;

use Splash\Client\Splash;
use Splash\Console\Helper\Graphics;
use Splash\Console\Models\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Build Splash Server Data Manifest
 */
class ServerManifestCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected $title = "Build Splash Server Data Manifest";

    /**
     * @var array
     */
    private $data;

    /**
     * Configure Symfony Command
     */
    protected function configure()
    {
        $this
            ->setName('splash:server:manifest')
            ->setDescription('[Splash] Splash Server Data Manifest (splash.yml & splash.json)')
            ->configureManagerOptions()
        ;
    }

    /**
     * Execute Symfony Command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
                : Splash::selfTest();
        //====================================================================//
        // Collect Server Data
        if ($selfTests) {
            //====================================================================//
            // Collect Server Data
            $result = $this->collectData();
            //====================================================================//
            // Build Manifest File
            if ($result) {
                $result = $this->buildYmlManifest();
                $this->buildJsonManifest();
            }
        }
        //====================================================================//
        // Render Splash Logs
        $this->renderLogs();
        //====================================================================//
        // Render Result Icon
        Graphics::renderResult($output, $result, $this->title);
    }

    /**
     * Collect Server Contents
     *
     * @return bool
     */
    private function collectData(): bool
    {
        //====================================================================//
        // Init Data Object
        $this->data = array();

        //====================================================================//
        // Read Server Informations
        if (!$this->collectServerData()) {
            return false;
        }
        //====================================================================//
        // Read Objects Informations
        if (!$this->collectObjectsData()) {
            return false;
        }
        //====================================================================//
        // Read Widgets Informations
        if (!$this->collectWidgetsData()) {
            return false;
        }

        return true;
    }

    /**
     * Collect Server Main Data
     *
     * @return bool
     */
    private function collectServerData(): bool
    {
        //====================================================================//
        // Init Data Object
        $this->data["server"] = array();
        //====================================================================//
        // Read Server Informations
        $informations = Splash::informations();
        if (empty($informations)) {
            return Splash::log()->errTrace("No Server Informations Found!!");
        }
        $this->data["server"] = (array) $informations;

        return true;
    }

    /**
     * Collect Server Objects Data
     *
     * @return bool
     */
    private function collectObjectsData(): bool
    {
        //====================================================================//
        // Init Data Object
        $this->data["objectTypes"] = array();
        $this->data["objects"] = array();
        //====================================================================//
        // Read Available Objects Types
        $objectsTypes = Splash::objects();
        if (empty($objectsTypes)) {
            return Splash::log()->errTrace("No Objects Types Found!!");
        }
        //====================================================================//
        // Read Objects Fields Data
        $this->data["objectTypes"] = $objectsTypes;
        foreach ($objectsTypes as $objectsType) {
            //====================================================================//
            // Init Data Object
            $object = array();
            $object["type"] = $objectsType;

            //====================================================================//
            // Read Object Description
            $desc = Splash::object($objectsType)->description();
            if (empty($desc)) {
                return Splash::log()->errTrace("Object ".$objectsType." has no Description.");
            }
            $object["description"] = (array) $desc;

            //====================================================================//
            // Read Object Fields
            $fields = Splash::object($objectsType)->fields();
            if (empty($fields)) {
                return Splash::log()->errTrace("Object ".$objectsType." has no Fields Defined.");
            }
            foreach ($fields as &$field) {
                $field = (array) $field;
            }
            $object["fields"] = (array) $fields;

            $this->data["objects"][$objectsType] = $object;
        }

        return true;
    }

    /**
     * Collect Server Widget Data
     *
     * @return bool
     */
    private function collectWidgetsData(): bool
    {
        //====================================================================//
        // Init Data Object
        $this->data["widgetsTypes"] = array();
        $this->data["widgets"] = array();
        //====================================================================//
        // Read Available Widgets Types
        $widgetsTypes = Splash::widgets();
        if (empty($widgetsTypes)) {
            return Splash::log()->warTrace("No Widgets Types Found!!");
        }
        //====================================================================//
        // Read Widgets Fields Data
        $this->data["widgetsTypes"] = $widgetsTypes;
        foreach ($widgetsTypes as $widgetsType) {
            //====================================================================//
            // Init Data Object
            $widget = array();
            $widget["type"] = $widgetsType;

            //====================================================================//
            // Read Widgets Description
            $desc = Splash::widget($widgetsType)->description();
            if (empty($desc)) {
                return Splash::log()->errTrace("Widget ".$widgetsType." has no Description.");
            }
            $widget["description"] = (array) $desc;

            $this->data["widgets"][$widgetsType] = $widget;
        }

        return true;
    }

    /**
     * Build Server Manifest Yml Version
     *
     * @return bool
     */
    private function buildYmlManifest(): bool
    {
        file_put_contents(getcwd().'/splash.yml', Yaml::dump($this->data, 4));

        return true;
    }

    /**
     * Build Server Manifest Json Version
     *
     * @return bool
     */
    private function buildJsonManifest(): bool
    {
        file_put_contents(getcwd().'/splash.json', json_encode($this->data));

        return true;
    }
}
