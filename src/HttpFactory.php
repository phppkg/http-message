<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-20
 * Time: 13:20
 */

namespace Inhere\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class HttpFactory
 * @package Inhere\Http
 * @link  https://github.com/php-fig/fig-standards/blob/master/proposed/http-factory/http-factory.md
 */
class HttpFactory
{
    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     * @var array
     */
    protected static $special = [
        'CONTENT_TYPE' => 1,
        'CONTENT_LENGTH' => 1,
        'PHP_AUTH_USER' => 1,
        'PHP_AUTH_PW' => 1,
        'PHP_AUTH_DIGEST' => 1,
        'AUTH_TYPE' => 1,
    ];

    /**
     * RequestFactoryInterface
     */

    /**
     * Create a new request.
     * @param string $method
     * @param UriInterface|string $uri
     * @return RequestInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public static function createRequest($method, $uri)
    {
        if (\is_string($uri)) {
            $uri = Uri::createFromString($uri);
        }

        return new Request($method, $uri);
    }

    /**
     * ResponseFactoryInterface
     */

    /**
     * Create a new response.
     * @param integer $code HTTP status code
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public static function createResponse($code = 200)
    {
        return new Response($code);
    }

    /**
     * ServerRequestFactoryInterface
     */

    /**
     * Create a new server request.
     * @param string $method
     * @param UriInterface|string $uri
     * @return ServerRequestInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public static function createServerRequest($method, $uri)
    {
        if (\is_string($uri)) {
            $uri = Uri::createFromString($uri);
        }

        return new ServerRequest($method, $uri);
    }

    /**
     * Create a new server request from server variables.
     * @param array|mixed $server Typically $_SERVER or similar structure.
     * @param string|null $class The class
     * @return ServerRequestInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     *  If no valid method or URI can be determined.
     */
    public static function createServerRequestFromArray($server, $class = null)
    {
        $env = self::ensureIsCollection($server);
        $uri = static::createUriFromArray($env);
        $method = $env['REQUEST_METHOD'];
        $headers = static::createHeadersFromArray($env);
        $cookies = Cookies::parseFromRawHeader($headers->get('Cookie', []));
        $serverParams = $env->all();
        $body = new RequestBody();
        $uploadedFiles = UploadedFile::createFromFILES();

        $class = $class ?: ServerRequest::class;

        /** @var ServerRequest $request */
        $request = new $class($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);

        if ($method === 'POST' &&
            \in_array($request->getMediaType(), ['application/x-www-form-urlencoded', 'multipart/form-data'], true)
        ) {
            // parsed body must be $_POST
            $request = $request->withParsedBody($_POST);
        }

        return $request;
    }

    /**
     * StreamFactoryInterface
     */

    /**
     * Create a new stream from a string.
     * The stream SHOULD be created with a temporary resource.
     * @param string $content
     * @return StreamInterface
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public static function createStream($content = '')
    {
        return new RequestBody($content);
    }

    /**
     * Create a stream from an existing file.
     * The file MUST be opened using the given mode, which may be any mode
     * supported by the `fopen` function.
     * The `$filename` MAY be any string supported by `fopen()`.
     * @param string $filename
     * @param string $mode
     * @return StreamInterface
     * @throws \InvalidArgumentException
     */
    public static function createStreamFromFile($filename, $mode = 'r')
    {
        // $stream = fopen('php://temp', $mode);
        $stream = fopen($filename, $mode);

        return new Stream($stream);
    }

    /**
     * Create a new stream from an existing resource.
     * The stream MUST be readable and may be writable.
     * @param resource $resource e.g `$resource = fopen('php://temp', 'r+');`
     * @return StreamInterface
     * @throws \InvalidArgumentException
     */
    public static function createStreamFromResource($resource)
    {
        return new Stream($resource);
    }

    /**
     * UploadedFileFactoryInterface
     */

    /**
     * Create a new uploaded file.
     * If a string is used to create the file, a temporary resource will be
     * created with the content of the string.
     * If a size is not provided it will be determined by checking the size of
     * the file.
     * @see http://php.net/manual/features.file-upload.post-method.php
     * @see http://php.net/manual/features.file-upload.errors.php
     * @param string|resource $file
     * @param integer $size in bytes
     * @param integer $error PHP file upload error
     * @param string $clientFilename
     * @param string $clientMediaType
     * @return UploadedFileInterface
     * @throws \InvalidArgumentException If the file resource is not readable.
     */
    public static function createUploadedFile(
        $file, $size = null, $error = \UPLOAD_ERR_OK, $clientFilename = null, $clientMediaType = null
    )
    {
        return new UploadedFile($file, $clientFilename, $clientMediaType, $size, $error);
    }

    /**
     * UriFactoryInterface
     */

    /**
     * Create a new URI.
     * @param string $uri
     * @return UriInterface
     * @throws \InvalidArgumentException If the given URI cannot be parsed.
     */
    public static function createUri($uri = '')
    {
        return Uri::createFromString($uri);
    }

    /*******************************************************************************
     * extended factory methods
     ******************************************************************************/

    /**
     * @param Collection|array $env
     * @return Headers
     */
    public static function createHeadersFromArray($env)
    {
        $data = [];
        $env = self::ensureIsCollection($env);
        $env = self::determineAuthorization($env);

        foreach ($env as $key => $value) {
            $key = strtoupper($key);
            if (isset(static::$special[$key]) || strpos($key, 'HTTP_') === 0) {
                if ($key !== 'HTTP_CONTENT_LENGTH') {
                    $data[$key] = $value;
                }
            }
        }

        return new Headers($data);
    }

    /**
     * If HTTP_AUTHORIZATION does not exist tries to get it from
     * getallheaders() when available.
     * @param Collection $env The Slim application Collection
     * @return Collection
     */
    public static function determineAuthorization($env)
    {
        $authorization = $env->get('HTTP_AUTHORIZATION');

        if (null === $authorization && \is_callable('getallheaders')) {
            $headers = getallheaders();
            $headers = array_change_key_case($headers, CASE_LOWER);
            if (isset($headers['authorization'])) {
                $env->set('HTTP_AUTHORIZATION', $headers['authorization']);
            }
        }

        return $env;
    }

    /**
     * @param Collection|array $env
     * @return Uri
     * @throws \InvalidArgumentException
     */
    public static function createUriFromArray($env)
    {
        $env = self::ensureIsCollection($env);

        // Scheme
        $isSecure = $env->get('HTTPS');
        $scheme = (empty($isSecure) || $isSecure === 'off') ? 'http' : 'https';

        // Authority: Username and password
        $username = $env->get('PHP_AUTH_USER', '');
        $password = $env->get('PHP_AUTH_PW', '');

        // Authority: Host
        if ($env->has('HTTP_HOST')) {
            $host = $env->get('HTTP_HOST');
        } else {
            $host = $env->get('SERVER_NAME');
        }

        // Authority: Port
        $port = (int)$env->get('SERVER_PORT', 80);
        if (preg_match('/^(\[[a-fA-F0-9:.]+\])(:\d+)?\z/', $host, $matches)) {
            $host = $matches[1];

            if ($matches[2]) {
                $port = (int)substr($matches[2], 1);
            }
        } else {
            $pos = strpos($host, ':');
            if ($pos !== false) {
                $port = (int)substr($host, $pos + 1);
                $host = strstr($host, ':', true);
            }
        }

        // Path
        $requestScriptName = parse_url($env->get('SCRIPT_NAME'), PHP_URL_PATH);
        $requestScriptDir = \dirname($requestScriptName);

        // parse_url() requires a full URL. As we don't extract the domain name or scheme,
        // we use a stand-in.
        $requestUri = parse_url('http://example.com' . $env->get('REQUEST_URI'), PHP_URL_PATH);

        $basePath = '';
        $virtualPath = $requestUri;
        if (stripos($requestUri, $requestScriptName) === 0) {
            $basePath = $requestScriptName;
        } elseif ($requestScriptDir !== '/' && stripos($requestUri, $requestScriptDir) === 0) {
            $basePath = $requestScriptDir;
        }

        if ($basePath) {
            $virtualPath = ltrim(substr($requestUri, \strlen($basePath)), '/');
        }

        // Query string
        $queryString = $env->get('QUERY_STRING', '');
        if ($queryString === '') {
            $queryString = parse_url('http://example.com' . $env->get('REQUEST_URI'), PHP_URL_QUERY);
        }

        // Fragment
        $fragment = '';

        // Build Uri
        $uri = new Uri($scheme, $host, $port, $virtualPath, $queryString, $fragment, $username, $password);
        if ($basePath) {
            $uri = $uri->withBasePath($basePath);
        }

        return $uri;
    }

    /**
     * @param mixed $data
     * @return Collection
     */
    public static function ensureIsCollection($data)
    {
        if (\is_array($data)) {
            return new Collection($data);
        }

        if (\is_object($data) && method_exists($data, 'get')) {
            return $data;
        }

        return new Collection((array)$data);
    }
}
