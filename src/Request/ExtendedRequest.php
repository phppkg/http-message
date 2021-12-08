<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/11/28
 * Time: 下午11:43
 */

namespace PhpPkg\Http\Message\Request;

use PhpPkg\Http\Message\ServerRequest;
use PhpPkg\Http\Message\Traits\ExtendedRequestTrait;

/**
 * Class ExtendedRequest
 *
 * @package PhpPkg\Http\Message\Request
 */
class ExtendedRequest extends ServerRequest
{
    use ExtendedRequestTrait;

    /**
     * @param string $name
     * @param mixed|null $default
     * @param string|null $filter
     *
     * @return mixed
     */
    public function getQuery(string $name, mixed $default = null, string $filter = null): mixed
    {
        $value = $this->getQueryParam($name, $default);

        return $this->filtering($value, $filter);
    }

    /**
     * @param string|null $name
     * @param mixed|null $default
     * @param string|null $filter
     *
     * @return mixed
     */
    public function get(string $name = null, mixed $default = null, string $filter = null): mixed
    {
        if ($name === null) {
            return $this->getQueryParams();
        }

        return $this->filtering(parent::get($name, $default), $filter);
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @param string|null $filter
     *
     * @return mixed
     */
    public function post(string $name = null, mixed $default = null, string $filter = null): mixed
    {
        if ($name === null) {
            return $this->getParsedBody();
        }

        return $this->filtering(parent::post($name, $default), $filter);
    }

    /**
     * @param string|null $name
     * @param mixed|null $default
     * @param string|null $filter
     *
     * @return mixed
     * @throws \RuntimeException
     */
    public function json(string $name = null, mixed $default = null, string $filter = null): mixed
    {
        if ($name === null) {
            return $this->getParsedBody();
        }

        return $this->filtering(parent::post($name, $default), $filter);
    }

    /**
     * @param string|null $name
     * @param mixed|null $default
     * @param string|null $filter
     *
     * @return mixed
     */
    public function put(string $name = null, mixed $default = null, string $filter = null): mixed
    {
        return $this->post($name, $default, $filter);
    }
}
