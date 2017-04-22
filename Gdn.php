<?php
namespace Garden;

/**
 * Framework superobject
 *
 */
class Gdn {

    /**
     * @param string $name
     * @param array|null $config
     * @return Db\Database
     */
    public static function database($name = null, array $config = null)
    {
        return Db\Database::instance($name, $config);
    }

    /**
     * @param string $name
     * @return DB\Structure
     */
    public static function structure($name = false)
    {
        return Db\Structure::instance($name);
    }

    /**
     * @return Application
     */
    public static function app()
    {
        return Application::instance();
    }

    /**
     * @param string $driver
     * @return Cache
     */
    public static function cache($driver = null)
    {
        return Cache::instance($driver);
    }

    /**
     * @return Cache\Dirty
     */
    public static function dirtyCache()
    {
        return Cache::instance('dirty');
    }

    /**
     * @return Session
     */
    public static function session()
    {
        return Session::instance();
    }

    public static function response()
    {
        return Response::current();
    }

    public static function request()
    {
        if (!Request::current()) {
            $request = new Request();
            Request::current($request);
        }
        return Request::current();
    }

    /**
     * @return \Addons\Dashboard\Models\Permission
     */
    public static function permission()
    {

        return self::getClass('Addons\\Dashboard\\Models\\Permission');
    }

    /**
     * @return \Addons\Dashboard\Models\Auth
     */
    public static function auth()
    {
        return self::getClass('Addons\\Dashboard\\Models\\Auth');
    }

    /**
     * @return \Addons\Dashboard\Models\Users
     */
    public static function users()
    {
        return self::getClass('Addons\\Dashboard\\Models\\Users');
    }

    /**
     * @param Traits\Instance $className
     * @return bool
     */
    private static function getClass($className)
    {
        return class_exists($className) ? $className::instance() : false;
    }

    /**
     * @return bool
     */
    public static function authLoaded()
    {
        return class_exists('Addons\\Dashboard\\Models\\Auth') ? \Addons\Dashboard\Models\Auth::loaded() : false;
    }

}