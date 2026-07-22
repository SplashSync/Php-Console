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

namespace Splash\Console\Task;

use BadPixxel\PhpSdk\Helper\ShellRunner;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Config\ConfigOptionsResolver;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Splash\Core\Client\Splash;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * GrumPhp Task Model: Run a Splash Console Build Command
 *
 * Shared runner for build tasks (manifest, swagger...): each task declares
 * its console command and failure message through late static binding.
 */
abstract class AbstractBuilderTask extends AbstractExternalTask
{
    /**
     * Console Command Executed by this Task
     *
     * @var string
     */
    protected const COMMAND = "";

    /**
     * Failure Message on Build Errors
     *
     * @var string
     */
    protected const FAILURE = "Splash Build Failed!";

    /**
     * @var array
     */
    private array $options;

    /**
     * Get Default Task Configuration
     */
    public static function getConfigurableOptions(): ConfigOptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            array(
                'enabled' => true,
                'php' => "php",
                'console' => "bin/console",
                'command' => static::COMMAND,
                'options' => "",
                'config' => array(),
            )
        );

        $resolver->addAllowedTypes('enabled', array('bool'));
        $resolver->addAllowedTypes('php', array('string'));
        $resolver->addAllowedTypes('console', array('string'));
        $resolver->addAllowedTypes('command', array('string'));
        $resolver->addAllowedTypes('options', array('string'));
        $resolver->addAllowedTypes('config', array('array'));

        return ConfigOptionsResolver::fromClosure(
            static fn (array $options): array => $resolver->resolve($options)
        );
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
        $this->options = $this->getConfig()->getOptions();

        //====================================================================//
        // Build Disabled => Skip this Task
        if (!$this->options["enabled"]) {
            return TaskResult::createPassed($this, $context);
        }

        //====================================================================//
        // Execute Console Build Command
        if (!$this->runBuilderCommand()) {
            return TaskResult::createFailed($this, $context, Splash::log()->getConsoleLog());
        }

        return TaskResult::createPassed($this, $context);
    }

    /**
     * Execute the Console Build Command
     *
     * @return bool
     */
    private function runBuilderCommand(): bool
    {
        //====================================================================//
        // Build the Shell Command
        $command = $this->options["php"];
        $command .= " ".$this->options["console"];
        $command .= " ".$this->options["command"];
        $command .= " ".$this->options["options"];

        //====================================================================//
        // Execute Shell Command
        if (null !== ShellRunner::run($command)) {
            return Splash::log()->errTrace(static::FAILURE);
        }

        return true;
    }
}
