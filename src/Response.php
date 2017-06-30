<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 14:06
 */

namespace inhere\http;

/**
 * Class Response
 * @package inhere\library\http
 */
class Response extends \Slim\Http\Response
{
    /**
     * @param mixed $data
     * @param int $status
     * @param int $encodingOptions
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function withRawJson($data, $status = 200, $encodingOptions = 0)
    {
        return parent::withJson($data, $status, $encodingOptions);
    }

    /**
     * @param mixed $data
     * @param int $status
     * @param int $encodingOptions
     * @return static
     */
    public function withGoBack($fallbackUrl = '/', $status = 301)
    {
        $backTo = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl;

        return $this->withRedirect($backTo, $status);
    }
}
