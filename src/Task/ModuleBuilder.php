<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Console\Task;

use GrumPHP\Configuration\GrumPHP;
use GrumPHP\Formatter\ProcessFormatterInterface as Formater;
use GrumPHP\Process\ProcessBuilder;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use GrumPHP\Util\Paths;
use Splash\Console\Helper\Composer;
use Splash\Console\Helper\ZipBuilder;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * GrumPhp Task: Splash Module Builder
 *
 * Generate Installable Zip file for Splash Module
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ModuleBuilder extends AbstractExternalTask
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Paths
     */
    private $paths;

    /**
     * @param GrumPHP        $grumPHP
     * @param ProcessBuilder $processBuilder
     * @param Formater       $formatter
     * @param Paths          $path
     */
    public function __construct(GrumPHP $grumPHP, ProcessBuilder $processBuilder, Formater $formatter, Paths $path)
    {
        parent::__construct($grumPHP, $processBuilder, $formatter);

        $this->paths = $path;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'build-module';
    }

    /**
     * @return OptionsResolver
     */
    public function getConfigurableOptions(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'enabled' => true,
                'source_folder' => "/",
                'target_folder' => '/splash-console',
                'build_folder' => '',
                'build_file' => 'my-module.x.y.z',
                'composer_file' => "composer.json",
                'composer_options' => array("--no-dev" => true),
            )
        );

        $resolver->addAllowedTypes('enabled', array('boolean'));
        $resolver->addAllowedTypes('source_folder', array('string'));
        $resolver->addAllowedTypes('target_folder', array('string'));
        $resolver->addAllowedTypes('build_folder', array('string'));
        $resolver->addAllowedTypes('build_file', array('string'));
        $resolver->addAllowedTypes('composer_file', array('string'));
        $resolver->addAllowedTypes('composer_options', array('array'));

        return $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function canRunInContext(ContextInterface $context): bool
    {
        return ($context instanceof GitPreCommitContext || $context instanceof RunContext);
    }

    /**
     * {@inheritdoc}
     */
    public function run(ContextInterface $context): TaskResultInterface
    {
        //====================================================================//
        // Load Task Configuration
        $this->config = $this->getConfiguration();

        //====================================================================//
        // Build Disabled => Skip this Task
        if (!$this->config["enabled"]) {
            return TaskResult::createPassed($this, $context);
        }

        //====================================================================//
        // Load Splash as Empty Local Class (To Work without System Config)
        Splash::setLocalClass(new \Splash\Templates\Local\Local());
        Splash::translator()->load('local');
        Splash::log()->cleanLog();

        //====================================================================//
        // Init Module Build Directory
        if (!$this->initDirectory()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }

        //====================================================================//
        // Copy Module Contents to Build Directory
        if (!$this->copyContents()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }

        //====================================================================//
        // Execute Composer
        if (!$this->runComposer()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }

        //====================================================================//
        // Build Module Archive
        if (!$this->buildModule()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }

        return TaskResult::createPassed($this, $context);
    }

    /**
     * Init Temp Build Directory
     *
     * @return bool
     */
    private function initDirectory(): bool
    {
        $filesystem = new Filesystem();
        //====================================================================//
        // Init Module Build Directory
        try {
            $filesystem->remove($this->getTempDirectory());
            $filesystem->mkdir($this->getTempDirectory());
        } catch (IOExceptionInterface $exception) {
            return Splash::log()->errTrace(
                "An error occurred while creating your directory at ".$exception->getPath()
            );
        }

        return true;
    }

    /**
     * Copy Module Contents to Temp Build Directory
     *
     * @return bool
     */
    private function copyContents(): bool
    {
        $filesystem = new Filesystem();

        //====================================================================//
        // Copy Module Contents
        try {
            $filesystem->mirror($this->getModuleDirectory(), $this->getModuleTempDirectory());
        } catch (IOExceptionInterface $exception) {
            return Splash::log()->errTrace(
                "An error occurred while module contents copy at ".$exception->getPath()
            );
        }

        //====================================================================//
        // Copy Module Composer JSON to Build Directory
        if (!empty($this->config["composer_file"])) {
            $composerPath = $this->paths->getProjectDir()."/".$this->config["composer_file"];
            if (!$filesystem->exists($composerPath)) {
                return Splash::log()->errTrace(
                    "Unable to find composer.json at ".$composerPath
                );
            }
            //====================================================================//
            // Copy Module Contents
            try {
                $filesystem->copy($composerPath, $this->getTempDirectory()."/composer.json");
            } catch (IOExceptionInterface $exception) {
                return Splash::log()->errTrace(
                    "An error occurred while copy composer.json contents to ".$exception->getPath()
                );
            }
        }

        return true;
    }

    /**
     * Execute Module Composer
     *
     * @return bool
     */
    private function runComposer(): bool
    {
        //====================================================================//
        // Check if Composer JSON is Required
        if (empty($this->config["composer_file"])) {
            return true;
        }

        //====================================================================//
        // Execute Composer Update
        return Composer::update($this->getTempDirectory(), $this->config["composer_options"]);
    }

    /**
     * Build Module Archive to Build Directory
     *
     * @return bool
     */
    private function buildModule(): bool
    {
        return ZipBuilder::build($this->getBuildPath(), array(
            $this->config["build_folder"] => $this->getModuleTempDirectory()
        ));
    }

    /**
     * Get Temp Build Directory Path
     *
     * @return string
     */
    private function getTempDirectory(): string
    {
        return sys_get_temp_dir().$this->config["target_folder"];
    }

    /**
     * Get Module Directory Path
     *
     * @return string
     */
    private function getModuleDirectory(): string
    {
        return $this->paths->getProjectDir().$this->config["source_folder"];
    }

    /**
     * Get Module Temp Directory Path
     *
     * @return string
     */
    private function getModuleTempDirectory(): string
    {
        return sys_get_temp_dir().$this->config["target_folder"].$this->config["source_folder"];
    }

    /**
     * Get Build File Path
     *
     * @return string
     */
    private function getBuildPath(): string
    {
        return $this->paths->getProjectDir()."/build/".$this->config["build_file"].".zip";
    }
}
