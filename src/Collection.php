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
    /**
     * @param array|null $items
     * @return static
     */
    public static function make($items = null)
    {
        return new static((array)$items);
    }

    /**
     * Create new collection
     * @param array $items Pre-populate collection with this key-value array
     */
    public function __construct(array $items = [])
    {
        parent::__construct();

        $this->replace($items);
    }

    /**
     * @param array $values
     */
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

    public function all()
    {
        return $this->getArrayCopy();
    }

    public function has(string $key)
    {
        return isset($this[$key]);
    }

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
