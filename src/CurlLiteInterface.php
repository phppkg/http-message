<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-09
 * Time: 13:14
 */

namespace inhere\http;

/**
 * Class CurlLiteInterface
 * @package inhere\library\http
 */
interface CurlLiteInterface
{
    // request method list
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';
    const HEAD = 'HEAD';
    const OPTIONS = 'OPTIONS';
    const TRACE = 'TRACE';
    const SEARCH = 'SEARCH';

    /**
     * GET
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function get($url, $data = null, array $headers = [], array $options = []);

    /**
     * POST
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function post($url, $data = null, array $headers = [], array $options = []);

    /**
     * PUT
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function put($url, $data = null, array $headers = [], array $options = []);

    /**
     * PUT
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function patch($url, $data = null, array $headers = [], array $options = []);

    /**
     * PUT
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function delete($url, $data = null, array $headers = [], array $options = []);

    /**
     * OPTIONS
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function options($url, $data = null, array $headers = [], array $options = []);

    /**
     * HEAD
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function head($url, $data = [], array $headers = [], array $options = []);

    /**
     * TRACE
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function trace($url, $data = [], array $headers = [], array $options = []);

    /**
     * Send request
     * @param $url
     * @param array $data
     * @param string $method
     * @param array $headers
     * @param array $options
     * @return self
     */
    public function request($url, $data = null, $method = self::GET, array $headers = [], array $options = []);

    /**
     * @return bool
     */
    public function isOk();

    /**
     * @return bool
     */
    public function isFail();
}
