<?php

namespace Garden\Interfaces;


interface Cache
{
    /**
     * Retrieve a cached value entry by id.
     *
     * @param   string $id id of cache to entry
     * @param   string $default default value to return if cache miss
     * @return  mixed
     */
    public function get($id, $default = null);

    /**
     * Set a value to cache with id and lifetime
     *
     * @param   string $id id of cache entry
     * @param   mixed $data data to set to cache
     * @param   integer $lifetime lifetime in seconds
     * @return  boolean
     */
    public function set($id, $data, $lifetime = 3600);

    /**
     * Add a value to cache if a key doesn`t exists
     *
     * @param   string $id id of cache entry
     * @param   mixed $data data to set to cache
     * @param   integer $lifetime lifetime in seconds
     * @return  boolean
     */
    public function add($id, $data, $lifetime = 3600);

    /**
     * Check exists cache id
     *
     * @param   string $id id of cache entry
     * @return  boolean
     */
    public function exists($id);

    /**
     * Delete a cache entry based on id
     *
     * @param   string $id id to remove from cache
     * @return  boolean
     */
    public function delete($id);

    /**
     * Delete all cache entries.
     *
     * Beware of using this method when
     * using shared memory cache systems, as it will wipe every
     * entry within the system for all clients.
     *
     *
     * @return  boolean
     */
    public function deleteAll();
}