<?php

namespace Garden;

/**
 * Framework superobject
 *
 */
class Gdn {

    /**
     * @param string $driver
     * @return Cache
     */
    public static function cache($driver = null): Cache
    {
        return Cache::instance($driver);
    }

    /**
     * @return Cache
     */
    public static function dirtyCache(): Cache
    {
        return Cache::instance('dirty');
    }

    /**
     * @return Response
     */
    public static function response(): Response
    {
        return Response::current();
    }

    /**
     * @return Request
     */
    public static function request(): Request
    {
        return Request::current();
    }

}