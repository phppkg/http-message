<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 14:06
 */

namespace Inhere\Http\Extra;

use Inhere\Http\Body;
use Psr\Http\Message\UriInterface;

/**
 * Class Response
 * @package Inhere\Http\Extra
 */
class Response extends \Inhere\Http\Response
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
        $response = $this->withBody(new Body(fopen('php://temp', 'rb+')));
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

    /**
     * Is this response empty?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->getStatusCode(), [204, 205, 304], true);
    }

    /**
     * Is this response informational?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isInformational()
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Is this response OK?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isOk()
    {
        return $this->getStatusCode() === 200;
    }

    /**
     * Is this response successful?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Is this response a redirect?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isRedirect()
    {
        return in_array($this->getStatusCode(), [301, 302, 303, 307], true);
    }

    /**
     * Is this response a redirection?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Is this response forbidden?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     * @api
     */
    public function isForbidden()
    {
        return $this->getStatusCode() === 403;
    }

    /**
     * Is this response not Found?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isNotFound()
    {
        return $this->getStatusCode() === 404;
    }

    /**
     * Is this response a client error?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Is this response a server error?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }
}
