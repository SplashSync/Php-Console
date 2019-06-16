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

namespace Splash\Console\Task;

use Composer\Console\Application as ComposerApp;
use Exception;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ZipArchive;

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
     * @return string
     */
    public function getName(): string
    {
        return 'build';
    }

    /**
     * @return OptionsResolver
     */
    public function getConfigurableOptions(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'source_folder' => "/",
                'target_folder' => '/splash-console',
                'build_folder' => '',
                'build_file' => 'my-module.x.y.z',
                'composer_file' => "composer.json",
                'composer_options' => array("--no-dev" => true),
            )
        );

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
        // Init Module Build Directory
        $init = $this->initDirectory();
        if (null !== $init) {
            return TaskResult::createFailed($this, $context, $init);
        }

        //====================================================================//
        // Copy Module Contents to Build Directory
        $copy = $this->copyContents();
        if (null !== $copy) {
            return TaskResult::createFailed($this, $context, $copy);
        }

        //====================================================================//
        // Execute Composer
        $composer = $this->runComposer();
        if (null !== $composer) {
            return TaskResult::createFailed($this, $context, $composer);
        }

        //====================================================================//
        // Build Module Archive
        $build = $this->buildModule();
        if (null !== $build) {
            return TaskResult::createFailed($this, $context, $build);
        }

        return TaskResult::createPassed($this, $context);
    }

    /**
     * Init Temp Build Directory
     *
     * @return null|string
     */
    private function initDirectory(): ?string
    {
        $filesystem = new Filesystem();
        //====================================================================//
        // Init Module Build Directory
        try {
            $filesystem->remove($this->getTempDirectory());
            $filesystem->mkdir($this->getTempDirectory());
        } catch (IOExceptionInterface $exception) {
            return "An error occurred while creating your directory at ".$exception->getPath();
        }

        return null;
    }

    /**
     * Copy Module Contents to Temp Build Directory
     *
     * @return null|string
     */
    private function copyContents(): ?string
    {
        $filesystem = new Filesystem();

        //====================================================================//
        // Copy Module Contents
        try {
            $filesystem->mirror($this->getModuleDirectory(), $this->getModuleTempDirectory());
        } catch (IOExceptionInterface $exception) {
            return "An error occurred while module contents copy at ".$exception->getPath();
        }

        //====================================================================//
        // Copy Module Composer JSON to Build Directory
        if (!empty($this->config["composer_file"])) {
            $composerPath = $this->grumPHP->getGitDir()."/".$this->config["composer_file"];
            if (!$filesystem->exists($composerPath)) {
                return "Unable to find composer.json at ".$composerPath;
            }
            //====================================================================//
            // Copy Module Contents
            try {
                $filesystem->copy($composerPath, $this->getTempDirectory()."/composer.json");
            } catch (IOExceptionInterface $exception) {
                return "An error occurred while copy composer.json contents to ".$exception->getPath();
            }
        }

        return null;
    }

    /**
     * Execute Module Composer
     *
     * @return null|string
     */
    private function runComposer(): ?string
    {
        //====================================================================//
        // Check if Composer JSON is Required
        if (empty($this->config["composer_file"])) {
            return null;
        }

        //====================================================================//
        // Prepare Composer Input Options
        $baseOptions = array(
            'command' => 'install',
            "--working-dir" => $this->getTempDirectory(),
            "--quiet" => true,
            "--no-interaction" => true,
        );
        $input = new ArrayInput(
            (array) array_replace_recursive($baseOptions, $this->config["composer_options"])
        );

        //====================================================================//
        // Execute Composer Build
        try {
            $composer = new ComposerApp();
            $composer->setAutoExit(false);
            $composer->run($input);
        } catch (Exception $exception) {
            return "Composer Update Failled ".$exception->getMessage();
        }

        return null;
    }

    /**
     * Build Module Archive to Build Directory
     *
     * @return null|string
     */
    private function buildModule(): ?string
    {
        $filesystem = new Filesystem();

        //====================================================================//
        // Ensure Module Final Build Directory Exists
        if (!$filesystem->exists($this->getBuildDirectory())) {
            try {
                $filesystem->mkdir($this->getBuildDirectory());
            } catch (IOExceptionInterface $exception) {
                return "An error occurred while creating your directory at ".$exception->getPath();
            }
        }

        //====================================================================//
        // Verify Module Final Build Directory Exists
        if (!$filesystem->exists($this->getBuildDirectory())) {
            return "Final Module Build Dir doesn't Exists!";
        }

        //====================================================================//
        // Verify Module Final Build Directory Exists
        if (!$filesystem->exists($this->getBuildPath())) {
            $filesystem->remove($this->getBuildPath());
        }

        //====================================================================//
        // Verify Zip Extention is Loaded
        if (!extension_loaded("zip")) {
            return 'PHP : Zip PHP Extension is required to use Splash PHP Module.';
        }

        //====================================================================//
        // List Files to Add on Zip
        $finder = new Finder();
        $finder->files()->in($this->getModuleTempDirectory());
        // check if there are any search results
        if (!$finder->hasResults()) {
            return "No files found to generate Final Module!";
        }

        //====================================================================//
        // Create the archive
        $zip = new ZipArchive();
        if (true !== $zip->open($this->getBuildPath(), ZIPARCHIVE::CREATE)) {
            return "Unable to Create Final Module zip Archive";
        }
        //====================================================================//
        // Add the files
        foreach ($finder as $file) {
            $zip->addFile(
                (string) $file->getRealPath(),
                $this->config["build_folder"].$file->getRelativePathname()
            );
        }
        //====================================================================//
        // Close the zip -- done!
        $zip->close();

        return null;
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
        return $this->grumPHP->getGitDir().$this->config["source_folder"];
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
     * Get Build Directory Path
     *
     * @return string
     */
    private function getBuildDirectory(): string
    {
        return $this->grumPHP->getGitDir()."/build/";
    }

    /**
     * Get Build File Path
     *
     * @return string
     */
    private function getBuildPath(): string
    {
        return $this->grumPHP->getGitDir()."/build/".$this->config["build_file"].".zip";
    }
}
