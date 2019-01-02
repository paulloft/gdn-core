<?php

function smarty_function_event($Params) {
    $eventName = val('name', $Params);
    Garden\Event::fire($eventName);
}