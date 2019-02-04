<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 18:02
 * @ref Slim 3
 */

namespace PhpComp\Http\Message;

use PhpComp\Http\Message\Component\Collection;
use PhpComp\Http\Message\Request\RequestBody;
use PhpComp\Http\Message\Traits\CookiesTrait;
use PhpComp\Http\Message\Traits\RequestHeadersTrait;
use PhpComp\Http\Message\Traits\RequestTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class ServerRequest
 * @property-read string $origin
 */
class ServerRequest implements ServerRequestInterface
{
    use CookiesTrait, RequestTrait, RequestHeadersTrait;

    /**
     * the connection header line data end char
     */
    public const EOL = "\r\n";

    /**
     * @var array
     */
    private $uploadedFiles;

    /**
     * List of request body parsers (e.g., url-encoded, JSON, XML, multipart)
     * @var callable[]
     */
    private $bodyParsers = [];

    /** @var array */
    private $serverParams;

    /** @var Collection */
    private $attributes;

    /**
     * @param string $rawData
     * @return ServerRequestInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public static function makeByParseRawData(string $rawData): ServerRequestInterface
    {
        return HttpFactory::createServerRequestFromRaw($rawData);
    }

    /**
     * Request constructor.
     * @param string          $method
     * @param UriInterface    $uri
     * @param string          $protocol
     * @param string          $protocolVersion
     * @param array|Headers   $headers
     * @param array           $cookies
     * @param array           $serverParams
     * @param StreamInterface $body
     * @param array           $uploadedFiles
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $method = 'GET',
        UriInterface $uri = null,
        $headers = null,
        array $cookies = [],
        array $serverParams = [],
        StreamInterface $body = null,
        array $uploadedFiles = [],
        string $protocol = 'HTTP',
        string $protocolVersion = '1.1'
    ) {
        $this->setCookies($cookies);
        $this->initialize($protocol, $protocolVersion, $headers, $body ?: new RequestBody());
        $this->initializeRequest($uri, $method);

        $this->serverParams  = $serverParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->attributes    = new Collection();

        if (isset($serverParams['SERVER_PROTOCOL'])) {
            $this->protocolVersion = \str_replace('HTTP/', '', $serverParams['SERVER_PROTOCOL']);
        }

        if (!$this->headers->has('Host') || $this->uri->getHost() !== '') {
            $this->headers->set('Host', $this->uri->getHost());
        }

        $this->registerDataParsers();
    }

    public function __clone()
    {
        $this->headers    = clone $this->headers;
        $this->attributes = clone $this->attributes;
        $this->body       = clone $this->body;
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
     * registerDataParsers
     */
    protected function registerDataParsers(): void
    {
        $this->registerMediaTypeParser('application/json', function ($input) {
            $result = \json_decode($input, true);
            if (!\is_array($result)) {
                return null;
            }

            return $result;
        });

        $xmlParser = function ($input) {
            $backup        = \libxml_disable_entity_loader();
            $backup_errors = \libxml_use_internal_errors(true);
            $result        = \simplexml_load_string($input);
            \libxml_disable_entity_loader($backup);
            \libxml_clear_errors();
            \libxml_use_internal_errors($backup_errors);

            if ($result === false) {
                return null;
            }

            return $result;
        };

        $this->registerMediaTypeParser('text/xml', $xmlParser);
        $this->registerMediaTypeParser('application/xml', $xmlParser);

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function ($input) {
            \parse_str($input, $data);
            return $data;
        });
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
        $output = $this->buildFirstLine() . self::EOL;

        // add headers
        $output .= $this->headers->toHeaderLines(true);

        // append cookies
        if ($cookie = $this->cookies->toRequestHeader()) {
            $output .= "Cookie: $cookie" . self::EOL;
        }

        $output .= self::EOL;

        return $output . $this->getBody();
    }


    /**
     * @param string   $mediaType
     * @param callable $callable
     */
    public function registerMediaTypeParser(string $mediaType, callable $callable): void
    {
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }

        $this->bodyParsers[(string)$mediaType] = $callable;
    }

    /*******************************************************************************
     * Query Params
     ******************************************************************************/

    private $_queryParams;

    /**
     * Returns the request parameters given in the [[queryString]].
     * This method will return the contents of `$_GET` if params where not explicitly set.
     * @return array the request GET parameter values.
     * @see setQueryParams()
     */
    public function getQueryParams(): array
    {
        if ($this->_queryParams === null) {
            return $_GET;
        }

        return $this->_queryParams;
    }

    /**
     * Sets the request [[queryString]] parameters.
     * @param array $values the request query parameters (name-value pairs)
     * @see getQueryParam()
     * @see getQueryParams()
     */
    public function setQueryParams($values): void
    {
        $this->_queryParams = $values;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return static
     */
    public function withQueryParams(array $query)
    {
        $clone               = clone $this;
        $clone->_queryParams = $query;

        return $clone;
    }

    /**
     * Returns GET parameter with a given name. If name isn't specified, returns an array of all GET parameters.
     * @param string $name the parameter name
     * @param mixed  $defaultValue the default parameter value if the parameter does not exist.
     * @return array|mixed
     */
    public function get($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $this->getQueryParams();
        }

        return $this->getQueryParam($name, $defaultValue);
    }

    /**
     * @param      $name
     * @param null $defaultValue
     * @return mixed|null
     */
    public function getQueryParam($name, $defaultValue = null)
    {
        $params = $this->getQueryParams();

        return $params[$name] ?? $defaultValue;
    }

    /** @var string */
    private $_rawBody;

    /**
     * Returns the raw HTTP request body.
     * @return string the request body
     */
    public function getRawBody(): string
    {
        if ($this->_rawBody === null) {
            $this->_rawBody = \file_get_contents('php://input');
        }

        return $this->_rawBody;
    }

    /**
     * Sets the raw HTTP request body, this method is mainly used by test scripts to simulate raw HTTP requests.
     * @param string $rawBody the request body
     */
    public function setRawBody($rawBody): void
    {
        $this->_rawBody = $rawBody;
    }

    /** @var array|null */
    private $bodyParsed;

    /**
     * @return array|null
     * @throws \RuntimeException
     */
    public function getParsedBody(): ?array
    {
        if ($this->bodyParsed !== null) {
            return $this->bodyParsed;
        }

        $this->bodyParsed = [];

        if (!$this->body) {
            return $this->bodyParsed;
        }

        $mediaType = $this->getMediaType();

        // look for a media type with a structured syntax suffix (RFC 6839)
        $parts = \explode('+', $mediaType);
        if (\count($parts) >= 2) {
            $mediaType = 'application/' . $parts[\count($parts) - 1];
        }

        if (isset($this->bodyParsers[$mediaType]) === true) {
            $body   = (string)$this->getBody();
            $parsed = $this->bodyParsers[$mediaType]($body);

            if (null !== $parsed && !\is_object($parsed) && !\is_array($parsed)) {
                throw new \RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }

            $this->bodyParsed = $parsed ?: [];
        }

        return $this->bodyParsed;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data)
    {
        if (null !== $data && !\is_object($data) && !\is_array($data)) {
            throw new \InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }

        $clone             = clone $this;
        $clone->bodyParsed = $data;

        return $clone;
    }

    /**
     * @param array|null $data set Null to reset data.
     */
    public function setParsedBody($data): void
    {
        $this->bodyParsed = $data;
    }

    /**
     * Fetch parameter value from request body.
     * Note: This method is not part of the PSR-7 standard.
     * @param string $key
     * @param mixed  $default
     * @return mixed
     * @throws \RuntimeException
     */
    public function getParsedBodyParam($key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $result     = $default;

        if (\is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (\is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        }

        return $result;
    }

    /**
     * @param null $name
     * @param null $defaultValue
     * @return array|mixed|null
     * @throws \RuntimeException
     */
    public function post($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $this->getParsedBody();
        }

        return $this->getParsedBodyParam($name, $defaultValue);
    }

    /*******************************************************************************
     * Parameters (e.g., POST and GET data)
     ******************************************************************************/

    /**
     * Fetch associative array of body and query string parameters.
     * Note: This method is not part of the PSR-7 standard.
     * @return array
     * @throws \RuntimeException
     */
    public function getParams(): array
    {
        $params     = $this->getQueryParams();
        $postParams = $this->getParsedBody();

        if ($postParams) {
            $params = array_merge($params, (array)$postParams);
        }

        return $params;
    }

    /**
     * Fetch request parameter value from body or query string (in that order).
     * Note: This method is not part of the PSR-7 standard.
     * @param  string $key The parameter key.
     * @param  string $default The default value.
     * @return mixed The parameter value.
     * @throws \RuntimeException
     */
    public function getParam($key, $default = null)
    {
        $result     = $default;
        $getParams  = $this->getQueryParams();
        $postParams = $this->getParsedBody();

        if (\is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (\is_object($postParams) && \property_exists($postParams, $key)) {
            $result = $postParams->$key;
        } elseif (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }

    /*******************************************************************************
     * Uploaded Files
     ******************************************************************************/

    /**
     * @return array
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array $uploadedFiles
     * @return $this
     */
    public function setUploadedFiles(array $uploadedFiles): self
    {
        $this->uploadedFiles = $uploadedFiles;

        return $this;
    }

    /**
     * @param array $uploadedFiles
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $clone                = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    /*******************************************************************************
     * Attributes
     ******************************************************************************/

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes(): array
    {
        return $this->attributes->all();
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed  $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes->get($name, $default);
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return $this
     */
    public function setAttribute(string $name, $value): self
    {
        $this->attributes->set($name, $value);

        return $this;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function setAttributes(array $values): self
    {
        $this->attributes->replace($values);

        return $this;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed  $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes->set($name, $value);

        return $clone;
    }

    /**
     * @param array $attributes
     * @return ServerRequest
     */
    public function withAttributes(array $attributes): self
    {
        $clone             = clone $this;
        $clone->attributes = new Collection($attributes);

        return $clone;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function delAttribute(string $name): self
    {
        $this->attributes->remove($name);

        return $this;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute($name)
    {
        $clone = clone $this;
        $clone->attributes->remove($name);

        return $clone;
    }

    /*******************************************************************************
     * Server Params
     ******************************************************************************/

    /**
     * Retrieve server parameters.
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * Retrieve a server parameter.
     * Note: This method is not part of the PSR-7 standard.
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getServerParam(string $key, $default = null)
    {
        $key = \strtoupper($key);

        return $this->serverParams[$key] ?? $default;
    }

    /**
     * @param array $serverParams
     */
    public function setServerParams(array $serverParams): void
    {
        $this->serverParams = $serverParams;
    }
}
