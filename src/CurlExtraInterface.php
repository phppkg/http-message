<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-09
 * Time: 13:14
 */

namespace inhere\http;

/**
 * Class CurlExtraInterface
 * @package inhere\library\http
 */
interface CurlExtraInterface
{
    // ssl auth type
    const SSL_TYPE_CERT = 'cert';
    const SSL_TYPE_KEY = 'key';

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

    public function reset();

    /**
     * Reset response data
     * @return self
     */
    public function resetResponse();

    /**
     * set Headers
     *
     * [
     *  'Content-Type' => 'application/json'
     * ]
     *
     * @param array $headers
     * @param bool $override Override exists
     * @return $this
     */
    public function setHeaders(array $headers, $override = false);

    public function getHeaders($onlyValues = false);

    /**
     * Set curl options
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options);

    public function getOptions();

    /**
     * Set config for self
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config);

    /**
     * Get config data
     * @param null|string $name
     * @param mixed $default
     * @return array
     */
    public function getConfig($name = null, $default = null);
}
