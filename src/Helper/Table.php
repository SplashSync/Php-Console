<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Splash\Console\Helper;

use Symfony\Component\Console\Helper\Table as baseTable;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Splash Console Table
 */
class Table extends baseTable {
    
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
