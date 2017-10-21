<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午2:04
 */

namespace Inhere\Http;

/**
 * Class Collection
 * @package Inhere\Http
 */
class Collection extends \ArrayObject
{
    public function sets(array $values)
    {
        $this->replace($values);
    }

    /**
     * @param array $items
     */
    public function replace(array $items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        return $this[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed|null
     */
    public function add($name, $value)
    {
        if (isset($this[$name])) {
            return null;
        }

        $this[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed|null
     */
    public function set($name, $value)
    {
        return $this[$name] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function all()
    {
        return $this->getArrayCopy();
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($key)
    {
        if (isset($this[$key])) {
            $val = $this[$key];
            unset($this[$key]);

            return $val;
        }

        return null;
    }

    /**
     * clear all data
     */
    public function clear()
    {
        foreach ($this as $key) {
            unset($this[$key]);
        }
    }
}
