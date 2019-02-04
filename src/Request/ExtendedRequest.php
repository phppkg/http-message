<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/11/28
 * Time: 下午11:43
 */

namespace PhpComp\Http\Message\Request;

use PhpComp\Http\Message\ServerRequest;
use PhpComp\Http\Message\Traits\ExtendedRequestTrait;

/**
 * Class ExtendedRequest
 * @package PhpComp\Http\Message\Request
 */
class ExtendedRequest extends ServerRequest
{
    use ExtendedRequestTrait;

    /**
     * @param string      $name
     * @param null|mixed  $default
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
     * @param mixed  $default
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
     * @param mixed  $default
     * @param string $filter
     * @return mixed
     * @throws \RuntimeException
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
     * @param mixed  $default
     * @param string $filter
     * @return mixed
     * @throws \RuntimeException
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
     * @param mixed  $default
     * @param string $filter
     * @return mixed
     */
    public function put($name = null, $default = null, $filter = null)
    {
        return $this->post($name, $default, $filter);
    }
}
