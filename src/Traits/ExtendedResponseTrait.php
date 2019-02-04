<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 14:06
 */

namespace PhpComp\Http\Message\Traits;

use PhpComp\Http\Message\Body;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * trait ExtendedResponseTrait
 *
 * ```php
 * use PhpComp\Http\Message\Response;
 *
 * class MyResponse extends Response {
 *   use ExtendedResponseTrait;
 * }
 * ```
 *
 * @package PhpComp\Http\Message\Traits
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
     * @param  int|null            $status The redirect HTTP status code.
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function withRedirect(string $url, int $status = null): ResponseInterface
    {
        /** @var ResponseInterface $responseWithRedirect */
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
     * @param  int   $status The HTTP status code.
     * @param  int   $encodingOptions Json encoding options
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return ResponseInterface
     */
    public function withJson($data, int $status = null, int $encodingOptions = 0): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = $this->withBody(new Body());
        $response->getBody()->write($json = \json_encode($data, $encodingOptions));

        // Ensure that the json encoding passed successfully
        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }

        /** @var ResponseInterface $responseWithJson */
        $responseWithJson = $response->withHeader('Content-Type', 'application/json;charset=utf-8');

        if (null === $status) {
            return $responseWithJson->withStatus($status);
        }

        return $responseWithJson;
    }

    /**
     * @param string $fallbackUrl
     * @param int    $status
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public function withGoBack(string $fallbackUrl = '/', int $status = 301): ResponseInterface
    {
        $backTo = $this->getServerParam('HTTP_REFERER') ?: $fallbackUrl;

        return $this->withRedirect($backTo, $status);
    }
}
