<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/26 0026
 * Time: 18:02
 */

namespace Inhere\Http;

use Inhere\Library\Collections\SimpleCollection;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Request
 * @property string $method
 * @property Uri $uri
 * @property-read string $origin
 *
 * @method Body getBody()
 */
class Request extends BaseMessage implements ServerRequestInterface
{
    /**
     * @var array
     */
    private $uploadedFiles;

    /**
     * @var string
     */
    private $method;

    /**
     * The request URI object
     * @var Uri
     */
    private $uri;

    /**
     * The original request method (ignoring override)
     * @var string
     */
    private $originalMethod;

    /**
     * Valid request methods
     * @var string[]
     */
    private $validMethods = [
        'CONNECT' => 1,
        'DELETE' => 1,
        'GET' => 1,
        'HEAD' => 1,
        'OPTIONS' => 1,
        'PATCH' => 1,
        'POST' => 1,
        'PUT' => 1,
        'TRACE' => 1,
    ];

    /**
     * List of request body parsers (e.g., url-encoded, JSON, XML, multipart)
     * @var callable[]
     */
    private $bodyParsers = [];

    /** @var array  */
    private $serverParams = [];

    /** @var  string */
    private $requestTarget;

    /** @var SimpleCollection */
    private $attributes;

    /**
     * @param string $rawData
     * @return static
     */
    public static function makeByParseRawData(string $rawData)
    {
        if (!$rawData) {
            return new static();
        }

        // $rawData = trim($rawData);
        // split head and body
        $two = explode("\r\n\r\n", $rawData, 2);

        if (!$rawHeader = $two[0] ?? '') {
            return new static();
        }

        $body = $two[1] ? new RequestBody($two[1]) : null;

        /** @var array $list */
        $list = explode("\n", trim($rawHeader));

        // e.g: `GET / HTTP/1.1`
        $first = array_shift($list);
        list($method, $uri, $protoStr) = array_map('trim', explode(' ', trim($first)));
        list($protocol, $protocolVersion) = explode('/', $protoStr);

        // other header info
        $headers = [];
        foreach ($list as $item) {
            if ($item) {
                list($name, $value) = explode(': ', trim($item));
                $headers[$name] = trim($value);
            }
        }

        $cookies = [];
        if (isset($headers['Cookie'])) {
            $cookieData = $headers['Cookie'];
            $cookies = Cookies::parseFromRawHeader($cookieData);
        }

        $port = 80;
        $host = '';
        if ($val = $headers['Host'] ?? '') {
            list($host, $port) = strpos($val, ':') ? explode(':', $val) : [$val, 80];
        }

        $path = $uri;
        $query = $fragment = '';
        if (strlen($uri) > 1) {
            $parts = parse_url($uri);
            $path = $parts['path'] ?? '';
            $query = $parts['query'] ?? '';
            $fragment = $parts['fragment'] ?? '';
        }

        $uri = new Uri($protocol, $host, (int)$port, $path, $query, $fragment);

        return new static($method, $uri, $protocol, $protocolVersion, $headers, $cookies, $body);
    }

    /**
     * Request constructor.
     * @param string $method
     * @param Uri $uri
     * @param string $protocol
     * @param string $protocolVersion
     * @param array $headers
     * @param array $cookies
     * @param StreamInterface $body
     * @param array $uploadedFiles
     */
    public function __construct(
        string $method = 'GET', Uri $uri = null, string $protocol = 'HTTP', string $protocolVersion = '1.1',
        array $headers = [], array $cookies = [], StreamInterface $body = null, array $uploadedFiles = []
    )
    {
        parent::__construct($protocol, $protocolVersion, $headers, $cookies);

        $this->method = $method ? strtoupper($method) : 'GET';
        $this->uri = $uri;
        $this->body = new RequestBody();
        $this->uploadedFiles = $uploadedFiles;
        $this->attributes = new SimpleCollection();

        if (!$this->headers->has('Host') || $this->uri->getHost() !== '') {
            $this->headers->set('Host', $this->uri->getHost());
        }

        $this->registerDataParsers();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * registerDataParsers
     */
    protected function registerDataParsers()
    {
        $this->registerMediaTypeParser('application/json', function ($input) {
            $result = json_decode($input, true);
            if (!is_array($result)) {
                return null;
            }

            return $result;
        });

        $xmlParser = function ($input) {
            $backup = libxml_disable_entity_loader();
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
            if ($result === false) {
                return null;
            }

            return $result;
        };

        $this->registerMediaTypeParser('text/xml', $xmlParser);
        $this->registerMediaTypeParser('application/xml', $xmlParser);

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);

            return $data;
        });
    }

    /**
     * @return string
     */
    protected function buildFirstLine()
    {
        // `GET /path HTTP/1.1`
        return sprintf(
            '%s %s %s/%s',
            $this->getMethod(),
            $this->uri->getPathAndQuery(),
            $this->getProtocol(),
            $this->getProtocolVersion()
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

        // append cookies
        if ($cookie = $this->cookies->toRequestHeader()) {
            $output .= "Cookie: $cookie" . self::EOL;
        }

        $output .= self::EOL;

        return $output . $this->getBody();
    }


    /**
     * @param $mediaType
     * @param callable $callable
     */
    public function registerMediaTypeParser($mediaType, callable $callable)
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
    public function getQueryParams()
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
    public function setQueryParams($values)
    {
        $this->_queryParams = $values;
    }

    /**
     * @inheritdoc
     */
    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->_queryParams = $query;

        return $clone;
    }

    /**
     * Returns GET parameter with a given name. If name isn't specified, returns an array of all GET parameters.
     * @param string $name the parameter name
     * @param mixed $defaultValue the default parameter value if the parameter does not exist.
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
     * @param $name
     * @param null $defaultValue
     * @return mixed|null
     */
    public function getQueryParam($name, $defaultValue = null)
    {
        $params = $this->getQueryParams();

        return $params[$name] ?? $defaultValue;
    }

    private $_rawBody;

    /**
     * Returns the raw HTTP request body.
     * @return string the request body
     */
    public function getRawBody()
    {
        if ($this->_rawBody === null) {
            $this->_rawBody = file_get_contents('php://input');
        }

        return $this->_rawBody;
    }

    /**
     * Sets the raw HTTP request body, this method is mainly used by test scripts to simulate raw HTTP requests.
     * @param string $rawBody the request body
     */
    public function setRawBody($rawBody)
    {
        $this->_rawBody = $rawBody;
    }

    private $bodyParsed;

    /**
     * @return array|null
     */
    public function getParsedBody()
    {
        if ($this->bodyParsed !== false) {
            return $this->bodyParsed;
        }

        if (!$this->body) {
            return null;
        }

        $mediaType = $this->getMediaType();

        // look for a media type with a structured syntax suffix (RFC 6839)
        $parts = explode('+', $mediaType);
        if (count($parts) >= 2) {
            $mediaType = 'application/' . $parts[count($parts) - 1];
        }

        if (isset($this->bodyParsers[$mediaType]) === true) {
            $body = (string)$this->getBody();
            $parsed = $this->bodyParsers[$mediaType]($body);

            if (null !== $parsed && !is_object($parsed) && !is_array($parsed)) {
                throw new \RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }
            $this->bodyParsed = $parsed;

            return $this->bodyParsed;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function withParsedBody($data)
    {
        if (null !== $data && !is_object($data) && !is_array($data)) {
            throw new \InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }

        $clone = clone $this;
        $clone->bodyParsed = $data;

        return $clone;
    }

    /**
     * @param array $data
     */
    public function setParsedBody($data)
    {
        $this->bodyParsed = $data;
    }

    /**
     * Fetch parameter value from request body.
     * Note: This method is not part of the PSR-7 standard.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParsedBodyParam($key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $result = $default;

        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        }

        return $result;
    }

    /**
     * @param null $name
     * @param null $defaultValue
     * @return array|mixed|null
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
     */
    public function getParams()
    {
        $params = $this->getQueryParams();
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
     */
    public function getParam($key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $getParams = $this->getQueryParams();
        $result = $default;

        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        } elseif (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }

    /*******************************************************************************
     * Method
     ******************************************************************************/

    /**
     * @return string
     */
    public function getMethod()
    {
        if ($this->method === null) {
            $this->method = $this->originalMethod;
            $customMethod = $this->getHeaderLine('X-Http-Method-Override');

            if ($customMethod) {
                $this->method = $this->filterMethod($customMethod);
            } elseif ($this->originalMethod === 'POST') {
                $overrideMethod = $this->filterMethod($this->getParsedBodyParam('_METHOD'));
                if ($overrideMethod !== null) {
                    $this->method = $overrideMethod;
                }

                if ($this->getBody()->eof()) {
                    $this->getBody()->rewind();
                }
            }
        }

        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getOriginalMethod()
    {
        return $this->originalMethod;
    }

    /**
     * Does this request use a given method?
     * Note: This method is not part of the PSR-7 standard.
     * @param  string $method HTTP method
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * @inheritdoc
     */
    public function withMethod($method)
    {
        $method = $this->filterMethod($method);

        $clone = clone $this;
        $clone->originalMethod = $method;
        $clone->method = $method;

        return $clone;
    }

    /**
     * Validate the HTTP method
     * @param  null|string $method
     * @return null|string
     * @throws \InvalidArgumentException on invalid HTTP method.
     */
    protected function filterMethod($method)
    {
        if ($method === null) {
            return $method;
        }

        if (!is_string($method)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $method = strtoupper($method);
        if (!isset($this->validMethods[$method])) {
            throw new \InvalidArgumentException($this, $method);
        }

        return $method;
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

    /*******************************************************************************
     * Files
     ******************************************************************************/

    /**
     * @return array
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array $uploadedFiles
     * @return $this
     */
    public function setUploadedFiles(array $uploadedFiles)
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
        $clone = clone $this;
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
    public function getAttributes()
    {
        return $this->attributes->all();
    }

    /**
     * @inheritdoc
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes->get($name, $default);
    }

    /**
     * @inheritdoc
     */
    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes->set($name, $value);

        return $clone;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes->set($name, $value);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withAttributes(array $attributes)
    {
        $clone = clone $this;
        $clone->attributes = new SimpleCollection($attributes);

        return $clone;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function delAttribute($name)
    {
        $this->attributes->remove($name);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withoutAttribute($name)
    {
        $clone = clone $this;
        $clone->attributes->remove($name);

        return $clone;
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
            $contentTypePartsLength = count($contentTypeParts);

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

    /*******************************************************************************
     * URI
     ******************************************************************************/

    /**
     * @inheritdoc
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }

        if ($this->uri === null) {
            return '/';
        }

        $basePath = $this->uri->getBasePath();
        $path = $basePath . '/' . ltrim($this->uri->getPath(), '/');

        $query = $this->uri->getQuery();

        if ($query) {
            $path .= '?' . $query;
        }

        $this->requestTarget = $path;

        return $this->requestTarget;
    }

    /**
     * @inheritdoc
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException(
                'Invalid request target provided; must be a string and cannot contain whitespace'
            );
        }
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    /**
     * @return Uri
     */
    public function getUri(): Uri
    {
        return $this->uri;
    }

    /**
     * @param Uri $uri
     */
    public function setUri(Uri $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @inheritdoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost) {
            if ($uri->getHost() !== '') {
                $clone->headers->set('Host', $uri->getHost());
            }
        } else {
            if ($uri->getHost() !== '' && (!$this->hasHeader('Host') || $this->getHeaderLine('Host') === '')) {
                $clone->headers->set('Host', $uri->getHost());
            }
        }

        return $clone;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->getUri()->getPath();
    }

    /*******************************************************************************
     * Cookies
     ******************************************************************************/

    /**
     * @inheritdoc
     */
    public function getCookieParams()
    {
        return $this->cookies->all();
    }

    /**
     * @inheritdoc
     */
    public function getCookieParam($key, $default = null)
    {
        return $this->cookies->get($key, $default);
    }

    /**
     * @inheritdoc
     */
    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookies = new Cookies($cookies);

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
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * Retrieve a server parameter.
     * Note: This method is not part of the PSR-7 standard.
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function getServerParam($key, $default = null)
    {
        $key = strtoupper($key);
        $serverParams = $this->getServerParams();

        return $serverParams[$key] ?? $default;
    }

    /**
     * @param array $serverParams
     */
    public function setServerParams(array $serverParams)
    {
        $this->serverParams = $serverParams;
    }
}
