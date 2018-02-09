<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 18:02
 * @ref Slim 3
 */

namespace Inhere\Http;

use Inhere\Http\Request\RequestBody;
use Inhere\Http\Traits\RequestHeadersTrait;
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
    use RequestTrait, RequestHeadersTrait;

    const FAV_ICON = '/favicon.ico';

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
    public function toString(): string
    {
        // first line
        $output = $this->buildFirstLine() . "\r\n";

        // add headers
        $output .= $this->headers->toHeaderLines(true);

        // append cookies
        // if ($cookie = $this->cookies->toRequestHeader()) {
        //     $output .= "Cookie: $cookie\r\n";
        // }

        $output .= "\r\n";

        return $output . $this->getBody();
    }

}
