<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 14:05
 */

namespace Inhere\Http\Traits;

use Inhere\Http\UploadedFile;
use Inhere\Http\Uri;

/**
 * trait ExtendedRequestTrait
 *
 * ```php
 * use Inhere\Http\ServerRequest;
 *
 * class MyRequest extends ServerRequest {
 *   use ExtendedRequestTrait;
 * }
 * ```
 *
 * @package Inhere\Http\Traits
 *
 * @method      string   getRaw($name, $default = null)      Get raw data
 * @method      integer  getInt($name, $default = null)      Get a signed integer.
 * @method      integer  getNumber($name, $default = null)   Get an unsigned integer.
 * @method      float    getFloat($name, $default = null)    Get a floating-point number.
 * @method      boolean  getBool($name, $default = null)     Get a boolean.
 * @method      boolean  getBoolean($name, $default = null)  Get a boolean.
 * @method      string   getString($name, $default = null)
 * @method      string   getTrimmed($name, $default = null)
 * @method      string   getSafe($name, $default = null)
 * @method      string   getEmail($name, $default = null)
 * @method      string   getUrl($name, $default = null)      Get URL
 *
 * @property  Uri $uri;
 *
 * // there methods at the class Request.
 *
 * @method string getHeaderLine(string $name)
 * @method boolean isAjax()
 * @method string getRequestTarget()
 * @method array getUploadedFiles()
 * @method array getQueryParams()
 * @method array getParams()
 * @method mixed getParam($key, $default = null)
 */
trait ExtendedRequestTrait
{
    /** @var string */
    private static $rawFilter = 'raw';

    /** @var array */
    private static $phpTypes = [
        'int',
        'integer',
        'float',
        'double',
        'bool',
        'boolean',
        'string',

        'array',
        'object',
        'resource'
    ];

    /**
     * @var array
     */
    protected static $filters = [
        // return raw
        'raw' => '',

        // (int)$var
        'int' => 'int',
        'integer' => 'int',
        // (float)$var
        'float' => 'float',
        // (bool)$var
        'bool' => 'bool',
        // (bool)$var
        'boolean' => 'bool',
        // (string)$var
        'string' => 'string',
        // (array)$var
        'array' => 'array',

        // trim($var)
        'trimmed' => 'trim',

        // safe data
        'safe' => 'htmlspecialchars',
        'escape' => 'htmlspecialchars',

        // abs((int)$var)
        'number' => 'int|abs',

        // will use filter_var($var ,FILTER_SANITIZE_EMAIL)
        'email' => ['filter_var', FILTER_SANITIZE_EMAIL],

        // will use filter_var($var ,FILTER_SANITIZE_URL)
        'url' => ['filter_var', FILTER_SANITIZE_URL],

        // will use filter_var($var ,FILTER_SANITIZE_ENCODED, $settings);
        'encoded' => ['filter_var', FILTER_SANITIZE_ENCODED],
    ];

    /**
     * getParams() alias method
     * @return array
     */
    public function all(): array
    {
        return $this->getParams();
    }

    /**
     * @param string $name
     * @return UploadedFile
     */
    public function getUploadedFile(string $name): UploadedFile
    {
        return $this->getUploadedFiles()[$name] ?? null;
    }

    /**
     * Get Multi - 获取多个, 可以设置过滤
     * @param array $needKeys
     * $needKeys = [
     *     'name',
     *     'password',
     *     'status' => 'int'
     * ]
     * @param bool $onlyValue
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getMulti(array $needKeys = [], $onlyValue = false): array
    {
        $needed = [];

        foreach ($needKeys as $key => $value) {
            if (\is_int($key)) {
                $needed[$value] = $this->getParam($value);
            } else {
                $needed[$key] = $this->filtering($key, $value);
            }
        }

        return $onlyValue ? \array_values($needed) : $needed;
    }

    /**
     * e.g: `http://xxx.com`
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->uri->getBaseUrl();
    }

    /**
     * path + queryString
     * e.g. `/content/add?type=blog`
     * @return string
     */
    public function getRequestUri(): string
    {
        return $this->getRequestTarget();
    }

    /**
     * Is this an pjax request?
     * pjax = pushState + ajax
     * @return bool
     */
    public function isPjax(): bool
    {
        return $this->isAjax() && ($this->getHeaderLine('X-PJAX') === 'true');
    }

    /**
     * @return string
     */
    public function getPjaxContainer(): string
    {
        return $this->getHeaderLine('X-PJAX-Container');
    }

    /**
     * @param string $default
     * @return string
     */
    public function getReferrer(string $default = '/'): string
    {
        return $this->getHeaderLine('REFERER') ?: $default;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($name, array $arguments)
    {
        if ($arguments && 0 === \strpos($name, 'get')) {
            $filter = \substr($name, 3);
            $default = $arguments[1] ?? null;

            return $this->get($arguments[0], $default, \lcfirst($filter));
        }

        throw new \BadMethodCallException("Method '$name' is not exists in the class");
    }

    /**
     * @param mixed $value
     * @param string|callable $filter
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    public function filtering($value, $filter = null)
    {
        if (!$filter || $filter === self::$rawFilter) {
            return $value;
        }

        // is a custom filter
        if (!\is_string($filter)) {
            $result = $value;

            // is custom callable filter
            if (\is_callable($filter)) {
                $result = $filter($value);
            }

            return $result;
        }

        // is a php data type filter name
        if (\in_array($filter, self::$phpTypes, true)) {
            switch (\lcfirst(\trim($filter))) {
                case 'bool':
                case 'boolean':
                    $result = (bool)$value;
                    break;
                case 'double':
                case 'float':
                    $result = (float)$value;
                    break;
                case 'int' :
                case 'integer':
                    $result = (int)$value;
                    break;
                case 'string':
                    $result = (string)$value;
                    break;
                case 'array':
                    $result = (array)$value;
                    break;
                case 'object':
                    $result = (object)$value;
                    break;
                default:
                    $result = $value;
                    break;
            }

            return $result;
        }

        if (!isset(self::$filters[$filter])) {
            return $this->callFilterChain($value, $filter);
        }

        // is a defined filter
        $internalFilter = self::$filters[$filter];

        // url, email ...
        if (\is_array($internalFilter)) {
            $filter = $internalFilter[0];

            return $filter($value, $internalFilter[1]);
        }

        return $this->callFilterChain($value, $internalFilter);
    }

    /**
     * @param mixed $value
     * @param string $filter
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function callFilterChain($value, $filter)
    {
        if (\strpos($filter, '|') === false) {
            return $filter($value);
        }

        foreach (\explode('|', $filter) as $func) {
            if (!\is_callable($func)) {
                throw new \InvalidArgumentException("The filter '$func' is not a callable");
            }

            $value = $filter($value);
        }

        return $value;
    }
}
