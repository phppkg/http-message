<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 18:02
 * @ref Slim 3
 */

namespace PhpComp\Http\Message;

use PhpComp\Http\Message\Request\RequestBody;
use PhpComp\Http\Message\Traits\RequestHeadersTrait;
use PhpComp\Http\Message\Traits\RequestTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Request
 * @property string      $method
 * @property-read string $origin
 *
 * @method Body getBody()
 */
class Request implements RequestInterface
{
    use RequestTrait, RequestHeadersTrait;

    public const FAV_ICON = '/favicon.ico';

    /**
     * Request constructor.
     * @param string          $method
     * @param UriInterface    $uri
     * @param string          $protocol
     * @param string          $protocolVersion
     * @param array|Headers   $headers
     * @param StreamInterface $body
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $method = 'GET',
        UriInterface $uri = null,
        $headers = null,
        StreamInterface $body = null,
        string $protocol = 'HTTP',
        string $protocolVersion = '1.1'
    ) {
        $this->initialize($protocol, $protocolVersion, $headers, $body ?: new RequestBody());
        $this->initializeRequest($uri, $method);


        if (!$this->headers->has('Host') || $this->getUri()->getHost() !== '') {
            $this->headers->set('Host', $this->getUri()->getHost());
        }
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->body    = clone $this->body;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->toString();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * build response data
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
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
