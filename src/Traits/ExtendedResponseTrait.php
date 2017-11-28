<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 14:06
 */

namespace Inhere\Http\Traits;

use Inhere\Http\Body;
use Psr\Http\Message\UriInterface;

/**
 * trait ExtendedResponseTrait
 *
 * ```php
 * use Inhere\Http\Response;
 *
 * class MyResponse extends Response {
 *   use ExtendedResponseTrait;
 * }
 * ```
 *
 * @package Inhere\Http\Traits
 */
trait ExtendedResponseTrait
{
    /*******************************************************************************
     * Response Helpers
     ******************************************************************************/

    /**
     * Redirect.
     * Note: This method is not part of the PSR-7 standard.
     * This method prepares the response object to return an HTTP Redirect
     * response to the client.
     * @param  string|UriInterface $url The redirect destination.
     * @param  int|null $status The redirect HTTP status code.
     * @return static
     */
    public function withRedirect($url, $status = null)
    {
        $responseWithRedirect = $this->withHeader('Location', (string)$url);

        if (null === $status && $this->getStatusCode() === 200) {
            $status = 302;
        }

        if (null !== $status) {
            return $responseWithRedirect->withStatus($status);
        }

        return $responseWithRedirect;
    }

    /**
     * Json.
     * Note: This method is not part of the PSR-7 standard.
     * This method prepares the response object to return an HTTP Json
     * response to the client.
     * @param  mixed $data The data
     * @param  int $status The HTTP status code.
     * @param  int $encodingOptions Json encoding options
     * @throws \RuntimeException
     * @return static
     */
    public function withJson($data, $status = null, $encodingOptions = 0)
    {
        $response = $this->withBody(new Body());
        $response->body->write($json = json_encode($data, $encodingOptions));

        // Ensure that the json encoding passed successfully
        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }

        $responseWithJson = $response->withHeader('Content-Type', 'application/json;charset=utf-8');

        if (null === $status) {
            return $responseWithJson->withStatus($status);
        }

        return $responseWithJson;
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
