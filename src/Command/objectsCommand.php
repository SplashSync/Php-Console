<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2018 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;

class objectsCommand extends Command
{
    
    protected function configure()
    {
        $this
            ->setName('objects')
            ->setDescription('Splash: List Available Objects')
        ;
    }

    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        $Output->writeln("Hello World :-)");
        
        $table = new Table($Output);
        $table
                ->addRow(array("col 1", "col 2", "col 3"))
                ->addRow(array("col 1", "col 2", "col 3", new TableCell('<error>This value spans 3 columns</error>.')))
                ->addRow(array("col 1", "col 2", "col 3", new TableCell('<question>This value spans 3 columns</question>.')))
                ->addRow(array("col 1", "col 2", "col 3"))
                ->setStyle('box')
->setStyle('borderless')                
                ->render();
    }
}
