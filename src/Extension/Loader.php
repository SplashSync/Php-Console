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

namespace Splash\Console\Extension;

use GrumPHP\Extension\ExtensionInterface;
use Splash\Console\Task\ModuleBuilder;
use Splash\Console\Task\NullTask;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class Loader implements ExtensionInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function load(ContainerBuilder $container)
    {
        //====================================================================//
        // Ensure PHP Versions
        if (PHP_VERSION_ID < 70100) {
            $this->addTask($container, NullTask::class, 'build');

            return;
        }

        $this->addTask($container, ModuleBuilder::class, 'build');
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $className
     * @param string           $taskName
     */
    private function addTask(ContainerBuilder $container, $className, $taskName)
    {
        $container->register('task.build', $className)
            ->addArgument(new Reference('config'))
            ->addArgument(new Reference('process_builder'))
            ->addArgument(new Reference('formatter.raw_process'))
            ->addTag('grumphp.task', array('config' => $taskName));
    }
}
