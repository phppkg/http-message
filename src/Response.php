<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/29 0029
 * Time: 00:19
 * @from Slim 3
 */

namespace PhpPkg\Http\Message;

use InvalidArgumentException;
use PhpPkg\Http\Message\Traits\CookiesTrait;
use PhpPkg\Http\Message\Traits\MessageTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use function in_array;
use function is_string;
use function json_encode;

/**
 * Class Response
 * response for handshake
 *
 * @package PhpPkg\Http\Message
 * @property int    $status
 * @property string $statusMsg
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/MessageInterface.php
 * @link https://github.com/php-fig/http-message/blob/master/src/ResponseInterface.php
 */
class Response implements ResponseInterface
{
    use CookiesTrait, MessageTrait;

    /**
     * the connection header line data end char
     */
    public const EOL = "\r\n";

    /**
     * eg: 404
     *
     * @var int
     */
    private int $status;

    /**
     * eg: 'OK'
     *
     * @var string
     */
    private string $reasonPhrase = '';

    /**
     * Status codes and reason phrases
     * @var array
     */
    protected static array $messages = [
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

    /**
     * @param int                  $status
     * @param null                 $headers
     * @param array                $cookies
     * @param StreamInterface|null $body
     * @param string               $protocol
     * @param string               $protocolVersion
     * @return Response
     */
    public static function make(
        int $status = 200,
        $headers = null,
        array $cookies = [],
        StreamInterface $body = null,
        string $protocol = 'HTTP',
        string $protocolVersion = '1.1'
    ): Response {
        return new static($status, $headers, $cookies, $body, $protocol, $protocolVersion);
    }

    /**
     * Request constructor.
     *
     * @param int             $status
     * @param array|Headers|null   $headers
     * @param array           $cookies
     * @param StreamInterface $body
     * @param string          $protocol
     * @param string          $protocolVersion
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        int $status = 200,
        array|Headers $headers = null,
        array $cookies = [],
        StreamInterface $body = null,
        string $protocol = 'HTTP',
        string $protocolVersion = '1.1'
    ) {
        $this->setCookies($cookies);
        $this->initialize($protocol, $protocolVersion, $headers, $body ?: new Body());

        $this->status = $this->filterStatus($status);
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
    protected function buildFirstLine(): string
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
    public function toString(): string
    {
        // first line
        $output = $this->buildFirstLine() . self::EOL;

        // add headers
        $output .= $this->headers->toHeaderLines(true);

        // set cookies
        foreach ($this->cookies->toHeaders() as $value) {
            $output .= "Set-Cookie: $value" . self::EOL;
        }

        $output .= self::EOL;

        return $output . $this->getBody();
    }

    /**
     * response Json.
     * Note: This method is not part of the PSR-7 standard.
     * This method prepares the response object to return an HTTP Json response to the client.
     * @param  mixed $data The data
     * @param  int|null   $status The HTTP status code.
     * @param  int   $encodingOptions Json encoding options
     * @throws InvalidArgumentException
     * @return static
     */
    public function json(mixed $data, int $status = null, int $encodingOptions = 0): static
    {
        $this->setBody(new Body());
        $this->write(json_encode($data, JSON_THROW_ON_ERROR | $encodingOptions));

        /** @var self $response */
        $response = $this->withHeader('Content-Type', 'application/json;charset=UTF-8');

        if (null !== $status) {
            return $response->withStatus($status);
        }

        return $response;
    }

    /**
     * @param string $url
     * @param int $status
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public function redirect(string $url, int $status = 302): static
    {
        return $this
            ->withStatus($status)
            ->withHeader('Location', $url);
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
     * @param int    $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = ''): Response|static
    {
        $code = $this->filterStatus($code);

        if (!is_string($reasonPhrase) && !method_exists($reasonPhrase, '__toString')) {
            throw new InvalidArgumentException('ReasonPhrase must be a string');
        }

        $clone         = clone $this;
        $clone->status = $code;

        if ($reasonPhrase === '' && isset(static::$messages[$code])) {
            $reasonPhrase = static::$messages[$code];
        }

        if ($reasonPhrase === '') {
            throw new InvalidArgumentException('ReasonPhrase must be supplied for this code');
        }

        $clone->reasonPhrase = $reasonPhrase;

        return $clone;
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     * @return Response
     * @throws InvalidArgumentException
     */
    public function setStatus(int $code, string $reasonPhrase = ''): Response
    {
        $code = $this->filterStatus($code);

        $this->status = $code;
        if ($reasonPhrase === '' && isset(static::$messages[$code])) {
            $reasonPhrase = static::$messages[$code];
        }

        if ($reasonPhrase === '') {
            throw new InvalidArgumentException('ReasonPhrase must be supplied for this code');
        }

        $this->reasonPhrase = $reasonPhrase;

        return $this;
    }

    /**
     * Filter HTTP status code.
     * @param  int $status HTTP status code.
     * @return int
     * @throws InvalidArgumentException If an invalid HTTP status code is provided.
     */
    protected function filterStatus(int $status): int
    {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('Invalid HTTP status code');
        }

        return $status;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status ?: 200;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status ?: 200;
    }

    /**
     * @return string
     */
    public function getReasonPhrase(): string
    {
        if ($this->reasonPhrase === '' && ($code = $this->status)) {
            $this->reasonPhrase = self::$messages[$code] ?? '';
        }

        return $this->reasonPhrase;
    }

    /**
     * @return array
     */
    public static function getMessages(): array
    {
        return self::$messages;
    }

    /*******************************************************************************
     * Status check
     ******************************************************************************/

    /**
     * Is this response empty?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isEmpty(): bool
    {
        return in_array($this->getStatusCode(), [204, 205, 304], true);
    }

    /**
     * Is this response informational?
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Is this response OK?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->getStatusCode() === 200;
    }

    /**
     * Is this response successful?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Is this response a redirect?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isRedirect(): bool
    {
        return in_array($this->getStatusCode(), [301, 302, 303, 307], true);
    }

    /**
     * Is this response a redirection?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Is this response forbidden?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     * @api
     */
    public function isForbidden(): bool
    {
        return $this->getStatusCode() === 403;
    }

    /**
     * Is this response not Found?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->getStatusCode() === 404;
    }

    /**
     * Is this response a client error?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Is this response a server error?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }
}
