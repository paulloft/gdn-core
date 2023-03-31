<?php

namespace Garden\Traits;

trait Singleton
{
    use Instance;

    private function __clone()
    {
    }

    private function __construct()
    {
    }
}