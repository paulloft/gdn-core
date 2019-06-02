<?php

namespace Garden\Traits;

trait DataSetGet {

    protected $_data = [];

    /**
     * Set the data key value
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setData(string $key, $value): self
    {
        $this->_data[$key] = $value;

        return $this;
    }

    /**
     * Set the data array
     *
     * @param array $data
     * @param bool $replace
     * @return self
     */
    public function setDataArray(array $data, $replace = false): self
    {
        $this->_data = $replace ? $data : array_merge($this->_data, $data);

        return $this;
    }

    /**
     * Get the data key value
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getData(string $key, $default = null): array
    {
        return $this->_data[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function getDataArray(): array
    {
        return $this->_data;
    }
}