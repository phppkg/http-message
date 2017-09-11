<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 14:06
 */

namespace Inhere\Http\Extra;

/**
 * Class Response
 * @package Inhere\Http\Extra
 */
class Response extends \Inhere\Http\Response
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
     * @param string $fallbackUrl
     * @param int $status
     * @return static
     */
    public function withGoBack($fallbackUrl = '/', $status = 301)
    {
        $backTo = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl;

        return $this->withRedirect($backTo, $status);
    }
}
