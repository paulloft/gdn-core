<?php

namespace Garden\Renderers;

use Garden\Interfaces\Renderer;
use Garden\Response;
use Garden\Traits\DataSetGet;

class Json implements Renderer {

    use DataSetGet;

    public function fetch(Response $response): string
    {
        $response->setContentType('application/json');

        return json_encode($this->_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}