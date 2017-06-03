<?php

namespace inhere\http;

/**
 * Class SimpleRequest
 * @package inhere\library\http
 */
class SimpleRequest
{
    protected $parsedBody = false;

    /**
     * @param null $name
     * @param mixed $default
     * @return mixed
     */
    public function param($name=null, $default = null)
    {
        if (null === $name) {
            return $_GET + $this->getParsedBody();
        }

        return $_GET[$name] ?? $this->post($name, $default);
    }

    /**
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name=null, $default = null)
    {
        if (null === $name) {
            return $_GET;
        }

        return $_GET[$name] ?? $default;
    }

    /**
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function post($name=null, $default = null)
    {
        $body = $this->getParsedBody();

        if (null === $name) {
            return $body;
        }

        return $body[$name] ?? $default;
    }

    /**
     * @return array
     */
    public function getParsedBody()
    {
        if ($this->parsedBody === false) {
            // post data is json
            if (
                !isset($_SERVER['HTTP_CONTENT_TYPE']) || !($type = $_SERVER['HTTP_CONTENT_TYPE']) || strpos($type, '/json') <= 0
            ) {
                $this->parsedBody = &$_POST;
            } else {
                $this->parsedBody = json_decode(file_get_contents('php://input'), true);
            }
        }

        return $this->parsedBody;
    }

    /**
     * Get multi value - 获取多个, 可以设置默认值
     * 如果默认值是 int 或者 string, 获取到的值也会格式化为相应的格式
     * @param array $needKeys
     * $needKeys = [
     *     'name',
     *     'password',
     *     'status' => 1
     * ]
     * @param bool $onlyValue If true, only return values. you can use 'list' received.
     *
     * ```
     * list($name, $password) = $this->getMulti($needKeys);
     * ```
     * @return array
     */
    public function getMulti(array $needKeys=[], $onlyValue = false)
    {
        $needed = [];

        foreach ($needKeys as $key => $val) {
            if ( is_int($key) ) {
                $needed[$val] = $this->param($val);
            } else {
                $type = gettype($val);
                $value = $this->param($key, $val);

                if( $type === 'integer' ) {
                    $value = (int)$value;
                } elseif ($type === 'string') {
                    $value = trim($value);
                }

                $needed[$key] = $value;
            }
        }

        return $onlyValue ? array_values($needed) : $needed;
    }
}
