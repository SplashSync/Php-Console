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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * GrumPhp Task: Splash Manifest Builder
 *
 * Generate Splash Dat Manifest
 */
class ManifestBuilder extends AbstractExternalTask
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
        return 'build-manifest';
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
                'php' => "php",
                'console' => "tests/console",
                'command' => "splash:server:manifest",
                'options' => "",
            )
        );

        $resolver->addAllowedTypes('enabled', array('bool'));
        $resolver->addAllowedTypes('php', array('string'));
        $resolver->addAllowedTypes('console', array('string'));
        $resolver->addAllowedTypes('command', array('string'));
        $resolver->addAllowedTypes('options', array('string'));

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
        // Execute Manifest Command
        if (!$this->runManifestCommand()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }

        return TaskResult::createPassed($this, $context);
    }

    /**
     * Execute Manifest Command
     *
     * @return bool
     */
    private function runManifestCommand(): bool
    {
        //====================================================================//
        // Build the Shell Command
        $command = $this->config["php"];
        $command .= " ".$this->config["console"];
        $command .= " ".$this->config["command"];
        $command .= " ".$this->config["options"];

        var_dump($command);

        //====================================================================//
        // Execute Shell Command
        if (!ShellRunner::run($command)) {
            return Splash::log()->errTrace("Splash Manifest Build Failled!");
        }

        return true;
    }
}
