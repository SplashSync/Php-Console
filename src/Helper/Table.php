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

namespace Splash\Console\Helper;

use Symfony\Component\Console\Helper\Table as baseTable;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Splash Console Table
 */
class Table extends baseTable
{
    /**
     * Class Constructor
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        parent::__construct($output);

        // Setup Style
        $this->setStyle('borderless');
    }
}
