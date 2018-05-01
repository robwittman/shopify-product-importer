<?php

namespace App\Pipeline;

class MatrixLoader
{
    protected $rootDir;

    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function load($path)
    {
        // Load
    }
}