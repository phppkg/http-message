<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:44
 */

namespace PhpPkg\Http\Message\Traits;

use PhpPkg\Http\Message\Headers;
use PhpPkg\Http\Message\Stream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Trait MessageTrait
 * @package PhpPkg\Http\Message\Traits
 */
trait MessageTrait
{
    /**
     * protocol/schema
     * @var string
     */
    protected string $protocol;

    /**
     * @var string
     */
    protected string $protocolVersion;

    /**
     * @var Headers
     */
    protected Headers $headers;

    /**
     * Body object
     *
     * @var StreamInterface
     */
    protected StreamInterface $body;

    /**
     * A map of valid protocol versions
     * @var array
     */
    protected static array $validProtocolVersions = [
        '1.0' => true,
        '1.1' => true,
        '2.0' => true,
    ];

    /**
     * BaseMessage constructor.
     *
     * @param string                          $protocol
     * @param string                          $protocolVersion
     * @param array|Headers|null $headers
     * @param string|StreamInterface $body
     *
     * @throws \InvalidArgumentException
     */
    public function initialize(
        string $protocol = 'http',
        string $protocolVersion = '1.1',
        array|Headers $headers = null,
        StreamInterface|string $body = 'php://memory'
    ): void {
        $this->protocol        = $protocol ?: 'http';
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
    public function setProtocol(string $protocol): void
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
    public function setProtocolVersion(string $protocolVersion): void
    {
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @param $version
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withProtocolVersion($version): static
    {
        if (!isset(self::$validProtocolVersions[$version])) {
            throw new \InvalidArgumentException(
                'Invalid HTTP version. Must be one of: '
                . implode(', ', array_keys(self::$validProtocolVersions))
            );
        }

        $clone                  = clone $this;
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
    public function hasHeader($name): bool
    {
        return $this->headers->has($name);
    }

    /**
     * @param string $name
     * @return \string[]
     */
    public function getHeader($name): array
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
     * @param string $name
     * @param        $value
     * @return $this
     */
    public function setHeader(string $name, $value): self
    {
        $this->headers->set($name, $value);

        return $this;
    }

    /**
     * PSR 7 method
     * @param string $name
     * @param        $value
     * @return self
     */
    public function withHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers->set($name, $value);

        return $clone;
    }

    /**
     * PSR 7 method
     * @param string $name
     * @return self
     */
    public function withoutHeader($name): self
    {
        $clone = clone $this;
        $clone->headers->remove($name);

        return $clone;
    }

    /**
     * PSR 7 method
     * @param string $name
     * @param        $value
     * @return self
     */
    public function withAddedHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers->add($name, $value);

        return $clone;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers->all();
    }

    /**
     * @return Headers
     */
    public function getHeadersObject(): Headers
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->headers->sets($headers);

        return $this;
    }

    /*******************************************************************************
     * Body
     ******************************************************************************/

    /**
     * @param bool|string|StreamInterface $body
     * @param string                               $mode
     *
     * @return StreamInterface
     * @throws \InvalidArgumentException
     */
    protected function createBodyStream(StreamInterface|bool|string $body, string $mode = 'rb'): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        if (!\is_string($body) && !\is_resource($body)) {
            throw new \InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );
        }

        if (\is_string($body)) {
            $error = null;

            \set_error_handler(function ($e) use (&$error) {
                $error = $e;
            }, E_WARNING);
            $body = \fopen($body, $mode);
            \restore_error_handler();

            if ($error) {
                throw new \InvalidArgumentException('Invalid stream reference provided');
            }
        }

        return new Stream($body);
    }

    /**
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @param StreamInterface $body
     * @return $this
     */
    public function setBody(StreamInterface $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param StreamInterface $body
     * @return $this|MessageInterface|ResponseInterface
     */
    public function withBody(StreamInterface $body): MessageInterface|ResponseInterface|static
    {
        // TODO: Test for invalid body?
        $clone       = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * @param string $content
     * @return $this
     * @throws \RuntimeException
     */
    public function addContent(string $content): self
    {
        $this->body->write($content);
        return $this;
    }

    /**
     * @param string $content
     * @return $this
     * @throws \RuntimeException
     */
    public function write(string $content): self
    {
        $this->body->write($content);
        return $this;
    }
}
