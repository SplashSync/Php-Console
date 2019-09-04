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

use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Splash\Console\Helper\ShellRunner;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

/**
 * GrumPhp Task: Jekyll Documentation Builder
 *
 * Generate Static Documentation Website for Github Pages
 */
class DocumentationBuilder extends AbstractExternalTask
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
        return 'build-docs';
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
                // Path of Jekyll Base Site (Relative to Splash Console)
                'source_folder' => "/Resources/jekyll",
                // Path of Final Docs (Relative to Current Module)
                'target_folder' => '/docs',
                // Path of Modules Documentations Contents (Relative to Current Module)
                'local_folder' => '/src/Resources/docs',
                // Genric Contents Path (Relative to Splash Console)
                'generic_folder' => "/Resources/contents",
                // Genric Contents To Add
                'generic_contents' => array("module", "splash"),
                // Temp Folder for Buildingh the Site
                'build_folder' => '/.gh-pages',
            )
        );

        $resolver->addAllowedTypes('enabled', array('bool'));
        $resolver->addAllowedTypes('source_folder', array('string'));
        $resolver->addAllowedTypes('target_folder', array('string'));
        $resolver->addAllowedTypes('build_folder', array('string'));
        $resolver->addAllowedTypes('generic_folder', array('string'));
        $resolver->addAllowedTypes('generic_contents', array('array'));

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
        // Copy Documentation Site Contents to Build Directory
        if (!$this->copyContents()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }

        //====================================================================//
        // Execute Yarn
        if (!$this->runYarn()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }
        
        //====================================================================//
        // Build Jekyll Configuration File
        if (!$this->buildConfig()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }
        
        //====================================================================//
        // Execute Jekyll Bundler
        if (!$this->runBundler()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }
        
        //====================================================================//
        // Build Final Documentation Site
        if (!$this->buildSite()) {
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
     * Copy Documentation Contents to Docs Directory
     *
     * @return bool
     */
    private function copyContents(): bool
    {
        $filesystem = new Filesystem();

        //====================================================================//
        // Copy Jekyll Base Contents
        try {
            $filesystem->mirror($this->getJekyllSrcDirectory(), $this->getTempDirectory());
        } catch (IOExceptionInterface $exception) {
            return Splash::log()->errTrace(
                "An error occurred while Jekyll Base copy at ".$exception->getPath()
            );
        }

        //====================================================================//
        // Copy Generic Contents
        foreach ($this->config["generic_contents"] as $code) {
            $contentDir = $this->getGenericContentDirectory($code);
            if (!is_dir($contentDir)) {
                return Splash::log()->errTrace(
                    "Unable to find Generic Contents copy at ".$contentDir
                );
            }

            try {
                $filesystem->mirror($contentDir, $this->getTempDirectory());
            } catch (IOExceptionInterface $exception) {
                return Splash::log()->errTrace(
                    "An error occurred while Generic Contents copy at ".$exception->getPath()
                );
            }
        }

        //====================================================================//
        // Copy Local Contents
        try {
            $filesystem->mirror($this->getLocalContentsDirectory(), $this->getTempDirectory());
        } catch (IOExceptionInterface $exception) {
            return Splash::log()->errTrace(
                "An error occurred while Local Contents copy at ".$exception->getPath()
            );
        }

        return true;
    }

    /**
     * Execute Yarn Install
     *
     * @return bool
     */
    private function runYarn(): bool
    {
        //====================================================================//
        // Check if Yarn is Installed
        if (!ShellRunner::run("yarn --version")) {
            return Splash::log()->errTrace("Yarn is Not Installed!! But sorry it's required...");
        }
        //====================================================================//
        // Execute Yarn install
        $comamnd = "yarn --cwd ".$this->getTempDirectory();
        $comamnd .= " install";
        $comamnd .= " --silent --non-interactive";
        $comamnd .= ' --modules-folder="'.$this->getTempDirectory().'/assets/vendor" ';

        if (!ShellRunner::run($comamnd)) {
            return Splash::log()->errTrace("Yarn install failled!");
        }

        return true;
    }
    
    /**
     * Execute Jekyll Bundler Install
     *
     * @return bool
     */
    private function runBundler(): bool
    {
        //====================================================================//
        // Check if Gem Bundler is Installed
        if (!ShellRunner::run("bundle --version")) {
            return Splash::log()->errTrace("Gem Bundler is Not Installed!! But sorry it's required...");
        }
        //====================================================================//
        // Execute Gem Bundler install
        $comamnd = "cd ".$this->getTempDirectory();
        $comamnd.= " && bundle install ";
        $comamnd.= " && bundle exec jekyll build ";
        if (!ShellRunner::run($comamnd)) {
            return Splash::log()->errTrace("Bundler Jekyyl Build Failled!");
        }

        return true;
    }    

    /**
     * Build Site Configuration
     *
     * @return bool
     */
    private function buildConfig(): bool
    {
        //====================================================================//
        // Load Generic Configuration
        $coreConfig = Yaml::parseFile($this->getJekyllSrcDirectory().'/_config.yml');
        //====================================================================//
        // Load Local Configuration
        $localConfig = Yaml::parseFile($this->getLocalContentsDirectory().'/_config.yml');
        //====================================================================//
        // Build Final Configuration
        $finalConfig = array_replace_recursive($coreConfig, $localConfig);
        file_put_contents($this->getTempDirectory().'/_config.yml', Yaml::dump($finalConfig));

        return true;
    }
    
    /**
     * Copy Compiled Site to Docs Directory
     *
     * @return bool
     */
    private function buildSite(): bool
    {
        $filesystem = new Filesystem();

        //====================================================================//
        // Verify Final Contents are Here
        $siteDir = $this->getTempDirectory()."/_site";
        if (!is_dir($siteDir)) {
            return Splash::log()->errTrace(
                "Unable to find Final Site at ".$siteDir
            );
        }
        //====================================================================//
        // Copy Jekyll Base Contents
        try {
            $filesystem->remove($this->getDocsDirectory());
            $filesystem->mkdir($this->getDocsDirectory());
            $filesystem->mirror($siteDir, $this->getDocsDirectory());
//            $filesystem->remove($this->getTempDirectory());
        } catch (IOExceptionInterface $exception) {
            return Splash::log()->errTrace(
                "An error occurred while Jekyll Base copy at ".$exception->getPath()
            );
        }

        return true;
    }
    
    /**
     * Get Documentations Sources Directory Path
     *
     * @return string
     */
    private function getDocsDirectory(): string
    {
        return $this->grumPHP->getGitDir().$this->config["target_folder"];
    }

    /**
     * Get Jekyll Sources Directory Path
     *
     * @return string
     */
    private function getJekyllSrcDirectory(): string
    {
        return dirname(__DIR__).$this->config["source_folder"];
    }

    /**
     * Get Generic Contents Directory Path
     *
     * @param string $contentsDir
     *
     * @return string
     */
    private function getGenericContentDirectory(string $contentsDir): string
    {
        return dirname(__DIR__).$this->config["generic_folder"]."/".$contentsDir;
    }

    /**
     * Get Local Sources Directory Path
     *
     * @return string
     */
    private function getLocalContentsDirectory(): string
    {
        return $this->grumPHP->getGitDir().$this->config["local_folder"];
    }
    
    /**
     * Get Temp Build Directory Path
     *
     * @return string
     */
    private function getTempDirectory(): string
    {
        return $this->grumPHP->getGitDir().$this->config["build_folder"];
    }    
}
