<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 18:02
 * @ref Slim 3
 */

namespace Inhere\Http;

use Inhere\Http\Traits\RequestTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Request
 * @property string $method
 * @property-read string $origin
 *
 * @method Body getBody()
 */
class Request implements RequestInterface
{
    use RequestTrait;

    /**
     * Request constructor.
     * @param string $method
     * @param UriInterface $uri
     * @param string $protocol
     * @param string $protocolVersion
     * @param array|Headers $headers
     * @param StreamInterface $body
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $method = 'GET', UriInterface $uri = null, $headers = null,
        StreamInterface $body = null, string $protocol = 'HTTP', string $protocolVersion = '1.1'
    )
    {
        $this->initialize($protocol, $protocolVersion, $headers, $body ?: new RequestBody());
        $this->initializeRequest($uri, $method);


        if (!$this->headers->has('Host') || $this->getUri()->getHost() !== '') {
            $this->headers->set('Host', $this->getUri()->getHost());
        }
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->body = clone $this->body;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * build response data
     * @return string
     */
    public function toString()
    {
        // first line
        $output = $this->buildFirstLine() . "\r\n";

        // add headers
        $output .= $this->headers->toHeaderLines(1);

        // append cookies
//        if ($cookie = $this->cookies->toRequestHeader()) {
//            $output .= "Cookie: $cookie\r\n";
//        }

        $output .= "\r\n";

        return $output . $this->getBody();
    }

    /**
     * @return bool
     */
    public function isWebSocket()
    {
        $val = $this->getHeaderLine('upgrade');

        return strtolower($val) === 'websocket';
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->isXhr();
    }

    /**
     * Is this an XHR request?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isXhr()
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * `Origin: http://foo.example`
     * @return string
     */
    public function getOrigin()
    {
        return $this->getHeaderLine('Origin');
    }

    /**
     * Get request content type.
     * Note: This method is not part of the PSR-7 standard.
     * @return string|null The request content type, if known
     */
    public function getContentType()
    {
        $result = $this->getHeader('Content-Type');

        return $result ? $result[0] : null;
    }

    /**
     * Get request media type, if known.
     * Note: This method is not part of the PSR-7 standard.
     * @return string|null The request media type, minus content-type params
     */
    public function getMediaType()
    {
        $contentType = $this->getContentType();

        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * Get request media type params, if known.
     * Note: This method is not part of the PSR-7 standard.
     * @return array
     */
    public function getMediaTypeParams()
    {
        $contentType = $this->getContentType();
        $contentTypeParams = [];

        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            $contentTypePartsLength = \count($contentTypeParts);

            for ($i = 1; $i < $contentTypePartsLength; $i++) {
                $paramParts = explode('=', $contentTypeParts[$i]);
                $contentTypeParams[strtolower($paramParts[0])] = $paramParts[1];
            }
        }

        return $contentTypeParams;
    }

    /**
     * Get request content character set, if known.
     * Note: This method is not part of the PSR-7 standard.
     * @return string|null
     */
    public function getContentCharset()
    {
        $mediaTypeParams = $this->getMediaTypeParams();
        if (isset($mediaTypeParams['charset'])) {
            return $mediaTypeParams['charset'];
        }

        return null;
    }

    /**
     * Get request content length, if known.
     * Note: This method is not part of the PSR-7 standard.
     * @return int|null
     */
    public function getContentLength()
    {
        $result = $this->headers->get('Content-Length');

        return $result ? (int)$result[0] : null;
    }
}
