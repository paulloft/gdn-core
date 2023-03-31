<?php

namespace Garden\Interfaces;

use Garden\Response;

interface Renderer
{
    /**
     * Content render
     *
     * @param Response $response current response
     * @return string
     */
    public function fetch(Response $response): string;
}
