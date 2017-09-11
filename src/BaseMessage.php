<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-30
 * Time: 13:12
 */

namespace Inhere\Http;

use inhere\library\traits\PropertyAccessByGetterSetterTrait;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class BaseRequestResponse
 * @package Sws\parts
 *
 * @property string $protocol
 * @property string $protocolVersion
 *
 * @property Headers $headers
 * @property Cookies $cookies
 * @property StreamInterface $body
 *
 */
class BaseMessage implements MessageInterface
{
    use PropertyAccessByGetterSetterTrait;

    /**
     * the connection header line data end char
     */
    const EOL = "\r\n";

    /**
     * protocol/schema
     * @var string
     */
    protected $protocol;

    /**
     * @var string
     */
    protected $protocolVersion;

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * Body object
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $body;

    /**
     * @var Cookies
     */
    private $cookies;

    /**
     * A map of valid protocol versions
     * @var array
     */
    protected static $validProtocolVersions = [
        '1.0' => true,
        '1.1' => true,
        '2.0' => true,
    ];

    /**
     * BaseMessage constructor.
     * @param string $protocol
     * @param string $protocolVersion
     * @param array $headers
     * @param array $cookies
     */
    public function __construct(string $protocol = 'HTTP', string $protocolVersion = '1.1', array $headers = [], array $cookies = [])
    {
        $this->protocol = $protocol ?: 'HTTP';
        $this->protocolVersion = $protocolVersion ?: '1.1';
        $this->headers = new Headers($headers);
        $this->cookies = new Cookies($cookies);
    }

    /*******************************************************************************
     * Protocol
     ******************************************************************************/

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        if (!$this->protocol) {
            $this->protocol = 'HTTP';
        }

        return $this->protocol;
    }

    /**
     * @param string $protocol
     */
    public function setProtocol(string $protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * @return string
     */
    public function getProtocolVersion(): string
    {
        if (!$this->protocolVersion) {
            $this->protocolVersion = '1.1';
        }

        return $this->protocolVersion;
    }

    /**
     * @param string $protocolVersion
     */
    public function setProtocolVersion(string $protocolVersion)
    {
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @param $version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        if (!isset(self::$validProtocolVersions[$version])) {
            throw new \InvalidArgumentException(
                'Invalid HTTP version. Must be one of: '
                . implode(', ', array_keys(self::$validProtocolVersions))
            );
        }

        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /*******************************************************************************
     * Headers
     ******************************************************************************/

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    /**
     * @param string $name
     * @return \string[]
     */
    public function getHeader($name)
    {
        return $this->headers->get($name, []);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name): string
    {
        return implode(',', $this->headers->get($name, []));
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers->set($name, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->set($name, $value);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        $clone = clone $this;
        $clone->headers->remove($name);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->add($name, $value);

        return $clone;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * @return Headers
     */
    public function getHeadersObject()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers->sets($headers);

        return $this;
    }

    /*******************************************************************************
     * Cookies
     ******************************************************************************/

    /**
     * @param string $name
     * @param string|array $value
     * @return $this
     */
    public function setCookie(string $name, $value)
    {
        $this->cookies->set($name, $value);

        return $this;
    }

    /**
     * @return Cookies
     */
    public function getCookies(): Cookies
    {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     * @return $this
     */
    public function setCookies(array $cookies)
    {
        if (!$this->cookies) {
            $this->cookies = new Cookies($cookies);
        } else {
            $this->cookies->sets($cookies);
        }

        return $this;
    }

    /*******************************************************************************
     * Body
     ******************************************************************************/

    /**
     * @return StreamInterface
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param StreamInterface $body
     * @return $this
     */
    public function setBody(StreamInterface $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setBodyContent($content)
    {
        $this->body->write($content);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        // TODO: Test for invalid body?
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }
}
