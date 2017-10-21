<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:44
 */

namespace Inhere\Http;
use Psr\Http\Message\StreamInterface;

/**
 * Trait MessageTrait
 * @package Inhere\Http
 */
trait MessageTrait
{
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
     * @var StreamInterface
     */
    protected $body;

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
     * @param array|Headers $headers
     * @param string|resource|StreamInterface $body
     */
    public function initialize(string $protocol = 'http', string $protocolVersion = '1.1', $headers = null, $body = 'php://memory')
    {
        $this->protocol = $protocol ?: 'http';
        $this->protocolVersion = $protocolVersion ?: '1.1';

        if ($headers) {
            $this->headers = $headers instanceof Headers ? $headers : new Headers($headers);
        } else {
            $this->headers = new Headers();
        }

        $this->body = $this->createBodyStream($body);
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
     * Body
     ******************************************************************************/

    /**
     * @param string|resource|StreamInterface|bool $body
     * @param string $mode
     * @return StreamInterface
     */
    protected function createBodyStream($body, $mode = 'r')
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        if (!is_string($body) && !is_resource($body)) {
            throw new \InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );
        }

        if (is_string($body)) {
            $error = null;

            set_error_handler(function ($e) use (&$error) {
                $error = $e;
            }, E_WARNING);
            $body = fopen($body, $mode);
            restore_error_handler();

            if ($error) {
                throw new \InvalidArgumentException('Invalid stream reference provided');
            }
        }

        return new Stream($body);
    }

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
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        // TODO: Test for invalid body?
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function addContent($content)
    {
        $this->body->write($content);

        return $this;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function write($content)
    {
        $this->body->write($content);

        return $this;
    }
}
