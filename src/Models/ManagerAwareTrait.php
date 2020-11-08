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

namespace Splash\Console\Models;

use Psr\Log\LoggerInterface;
use Splash\Bundle\Connectors\NullConnector;
use Splash\Bundle\Events\IdentifyServerEvent;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Client\Splash;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Make Console Commands Aware of Connector Manager
 */
trait ManagerAwareTrait
{
    /**
     * Current Connector for Action
     *
     * @var AbstractConnector
     */
    protected $connector;

    /**
     * @var bool
     */
    private $isManagerMode = false;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var null|string
     */
    private $defaultServerId;

    /**
     * Setup Sf Command for Using Splash Connector Manager
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface          $logger
     * @param array                    $config
     */
    public function setManager(EventDispatcherInterface $eventDispatcher, LoggerInterface $logger, array $config): void
    {
        $this->isManagerMode = true;
        //====================================================================//
        // Init Dispatcher & Logger
        $this->setEventDispatcher($eventDispatcher);
        $this->setLogger($logger);
        //====================================================================//
        // Create Null Connector for Identification
        $this->connector = new NullConnector($eventDispatcher, $logger);
        //====================================================================//
        // Detect First Configured Server
        $this->detectFirstServer($config);
    }

    /**
     * Get Event Dispatcher
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Add Splash Connector Manager Options.
     *
     * @return $this
     */
    protected function configureManagerOptions(): self
    {
        $this
            ->addOption('ws', null, InputOption::VALUE_OPTIONAL, 'WebService Id of the Connector Server', 'auto')
        ;

        return $this;
    }

    /**
     * Identify of Server in Memory & Set it as Default Connector.
     *
     * @param InputInterface $input
     *
     * @throws LogicException           if no HelperSet is defined
     * @throws InvalidArgumentException When the Webservice Id is invalid
     */
    protected function identifyServer(InputInterface $input): void
    {
        //==============================================================================
        // Safety Checks - We are in Connector Manager Mode
        if (!$this->isManagerMode) {
            return;
        }
        //==============================================================================
        // Detect Server Id
        $webserviceId = ("auto" == $input->getOption("ws"))
                ? $this->defaultServerId
                : $input->getOption("ws");
        //==============================================================================
        // Safety Checks
        if (empty($webserviceId) || !is_string($webserviceId)) {
            throw new InvalidArgumentException('Webservice Id is Empty or invalid.');
        }
        //==============================================================================
        // Use Sf Event to Identify Server
        /** @var IdentifyServerEvent $event */
        /** @phpstan-ignore-next-line */
        $event = $this->getEventDispatcher()->dispatch(
            IdentifyServerEvent::NAME,
            new IdentifyServerEvent($this->connector, $webserviceId)
        );
        //==============================================================================
        // Ensure Identify Server was Ok
        if (!$event->isIdentified()) {
            throw new LogicException(
                sprintf('Unable to Identify connector server %s. Is this the right Server?', $webserviceId)
            );
        }
        //==============================================================================
        // If Connection Was Rejected
        if ($event->isRejected()) {
            throw new LogicException(
                sprintf('Connection to connector server %s was Rejected. Is this Server Active?', $webserviceId)
            );
        }
        //====================================================================//
        // Server Found => Use Identified Connector Service
        $this->connector = $event->getConnector();
        Splash::log()->msg("Detected Splash Connector: ".$webserviceId);
    }

    /**
     * Check if we are in Connector Manager Mode
     *
     * @return bool
     */
    protected function isManagerMode()
    {
        return $this->isManagerMode && ($this->connector instanceof AbstractConnector);
    }

    /**
     * Get Current Splash Connector
     *
     * @return AbstractConnector
     */
    protected function getConnector(): AbstractConnector
    {
        if ($this->connector instanceof AbstractConnector) {
            return $this->connector;
        }

//        throw new LogicException('Current connector is NOT an AbstractConnector...');
    }

    /**
     * Get Event Dispatcher
     *
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Setup for Connector Manager Mode
     *
     * @return $this
     */
    private function setManagerMode()
    {
        $this->isManagerMode = true;

        return $this;
    }

    /**
     * Set Event Dispatcher
     *
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return $this
     */
    private function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Set Event Dispatcher
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    private function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Detect First Configured Server
     *
     * @param array $config
     */
    private function detectFirstServer(array $config): void
    {
        //====================================================================//
        // Safety Check - Verify Configured Servers List is Not Empty
        if (!isset($config["connections"]) || empty($config["connections"])) {
            return;
        }
        //====================================================================//
        // Detect First Configured Server
        $firstServer = array_shift($config["connections"]);
        if (!isset($firstServer["id"]) || empty($firstServer["id"])) {
            return;
        }

        $this->defaultServerId = $firstServer["id"];
    }
}
