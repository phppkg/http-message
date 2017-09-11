<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/29 0029
 * Time: 00:19
 * @from Slim 3
 */

namespace Inhere\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 * response for handshake
 * @package Inhere\Http
 * @property int $status
 * @property string $statusMsg
 * @property array $body
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/MessageInterface.php
 * @link https://github.com/php-fig/http-message/blob/master/src/ResponseInterface.php
 */
class Response extends BaseMessage implements ResponseInterface
{
    /**
     * eg: 404
     * @var int
     */
    private $status;

    /**
     * eg: 'OK'
     * @var string
     */
    private $reasonPhrase;

    /**
     * Status codes and reason phrases
     * @var array
     */
    protected static $messages = [
        //Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        //Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    public static function make(
        int $status = 200, array $headers = [], array $cookies = [], StreamInterface $body = null,
        string $protocol = 'HTTP', string $protocolVersion = '1.1'
    )
    {
        return new self($status, $headers, $cookies, $body, $protocol, $protocolVersion);
    }

    /**
     * Request constructor.
     * @param int $status
     * @param array $headers
     * @param array $cookies
     * @param StreamInterface $body
     * @param string $protocol
     * @param string $protocolVersion
     */
    public function __construct(
        int $status = 200, array $headers = [], array $cookies = [], StreamInterface $body = null,
        string $protocol = 'HTTP', string $protocolVersion = '1.1'
    ) {
        $this->status = $this->filterStatus($status);
        $this->headers = new Headers($headers);
        $this->body = $body ? : new Body(fopen('php://temp', 'rb+'));

        parent::__construct($protocol, $protocolVersion, $headers, $cookies);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
    }

    /**
     * @return string
     */
    protected function buildFirstLine()
    {
        // `GET /path HTTP/1.1`
        return sprintf(
            '%s/%s %s %s',
            $this->getProtocol(),
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
    }

    /**
     * build response data
     * @return string
     */
    public function toString()
    {
        // first line
        $output = $this->buildFirstLine() . self::EOL;

        // add headers
        $output .= $this->headers->toHeaderLines(1);

        // set cookies
        foreach ($this->cookies->toHeaders() as $value) {
            $output .= "Set-Cookie: $value" . self::EOL;
        }

        $output .= self::EOL;

        return $output . $this->getBody();
    }

    /*******************************************************************************
     * Status
     ******************************************************************************/

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $code = $this->filterStatus($code);

        if (!is_string($reasonPhrase) && !method_exists($reasonPhrase, '__toString')) {
            throw new \InvalidArgumentException('ReasonPhrase must be a string');
        }

        $clone = clone $this;
        $clone->status = $code;

        if ($reasonPhrase === '' && isset(static::$messages[$code])) {
            $reasonPhrase = static::$messages[$code];
        }

        if ($reasonPhrase === '') {
            throw new \InvalidArgumentException('ReasonPhrase must be supplied for this code');
        }

        $clone->reasonPhrase = $reasonPhrase;

        return $clone;
    }

    /**
     * @param $code
     * @param string $reasonPhrase
     * @return Response
     */
    public function setStatus($code, $reasonPhrase = '')
    {
        $code = $this->filterStatus($code);

        if (!is_string($reasonPhrase) && !method_exists($reasonPhrase, '__toString')) {
            throw new \InvalidArgumentException('ReasonPhrase must be a string');
        }

        $this->status = $code;
        if ($reasonPhrase === '' && isset(static::$messages[$code])) {
            $reasonPhrase = static::$messages[$code];
        }

        if ($reasonPhrase === '') {
            throw new \InvalidArgumentException('ReasonPhrase must be supplied for this code');
        }

        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }

    /**
     * Filter HTTP status code.
     * @param  int $status HTTP status code.
     * @return int
     * @throws \InvalidArgumentException If an invalid HTTP status code is provided.
     */
    protected function filterStatus($status)
    {
        if (!is_int($status) || $status < 100 || $status > 599) {
            throw new \InvalidArgumentException('Invalid HTTP status code');
        }

        return $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status ?: 200;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status ?: 200;
    }

    /**
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * @return array
     */
    public static function getMessages(): array
    {
        return self::$messages;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function addContent(string $content)
    {
        if ($this->body === null) {
            $this->body = [];
        }

        $this->body[] = $content;

        return $this;
    }
}
