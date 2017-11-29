<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/11/28
 * Time: 下午11:43
 */

namespace Inhere\Http;

use Inhere\Http\Traits\ExtendedRequestTrait;

/**
 * Class ExtendedRequest
 * @package Inhere\Http
 */
class ExtendedRequest extends ServerRequest
{
    use ExtendedRequestTrait;

    /**
     * @param string $name
     * @param null|mixed $default
     * @param null|string $filter
     * @return mixed
     */
    public function getQuery($name, $default = null, $filter = null)
    {
        $value = $this->getQueryParam($name, $default);

        return $this->filtering($value, $filter);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @param string $filter
     * @return mixed
     */
    public function get($name = null, $default = null, $filter = null)
    {
        if ($name === null) {
            return $this->getQueryParams();
        }

        return $this->filtering(parent::get($name, $default), $filter);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @param string $filter
     * @return mixed
     */
    public function post($name = null, $default = null, $filter = null)
    {
        if ($name === null) {
            return $this->getParsedBody();
        }

        return $this->filtering(parent::post($name, $default), $filter);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @param string $filter
     * @return mixed
     */
    public function json($name = null, $default = null, $filter = null)
    {
        if ($name === null) {
            return $this->getParsedBody();
        }

        return $this->filtering(parent::post($name, $default), $filter);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @param string $filter
     * @return mixed
     */
    public function put($name = null, $default = null, $filter = null)
    {
        return $this->post($name, $default, $filter);
    }
}
