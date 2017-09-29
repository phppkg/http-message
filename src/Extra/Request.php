<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 14:05
 */

namespace Inhere\Http\Extra;

use Inhere\Http\UploadedFile;
use Inhere\Http\Uri;
use Inhere\Validate\FilterList;
use inhere\library\DataType;

/**
 * Class Request
 * @package Inhere\Http\Extra
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
 */
class Request extends \Inhere\Http\Request
{
    /**
     * return raw data
     */
    const FILTER_RAW = 'raw';

    /**
     * @var array
     */
    protected static $filterList = [
        // return raw
        'raw' => '',

        // (int)$var
        'int' => 'int',
        // (float)$var or floatval($var)
        'float' => 'float',
        // (bool)$var
        'bool' => 'bool',
        // (bool)$var
        'boolean' => 'bool',
        // (string)$var
        'string' => 'string',

        // trim($var)
        'trimmed' => FilterList::class . '::trim',

        // safe data
        'safe' => 'htmlspecialchars',

        // abs((int)$var)
        'number' => FilterList::class . '::abs',
        // will use filter_var($var ,FILTER_SANITIZE_EMAIL)
        'email' => FilterList::class . '::email',
        // will use filter_var($var ,FILTER_SANITIZE_URL)
        'url' => FilterList::class . '::url',

        // will use filter_var($var ,FILTER_SANITIZE_ENCODED, $settings);
        'encoded' => FilterList::class . '::encoded',
    ];

    /**
     * getParams() alias method
     * @return array
     */
    public function all()
    {
        return $this->getParams();
    }

    /**
     * @param $name
     * @return UploadedFile
     */
    public function getUploadedFile($name)
    {
        return $this->getUploadedFiles()[$name] ?? null;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @param string $filter
     * @return mixed
     */
    public function get($name = null, $default = null, $filter = 'raw')
    {
        if ($name === null) {
            return $this->getQueryParams();
        }

        $value = $this->getParams()[$name] ?? $default;

        return $this->filtering($value, $filter);
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
     */
    public function getMulti(array $needKeys = [], $onlyValue = false)
    {
        $needed = [];

        foreach ($needKeys as $key => $value) {
            if (is_int($key)) {
                $needed[$value] = $this->getParam($value);
            } else {
                $needed[$key] = $this->filtering($key, $value);
            }
        }

        return $onlyValue ? array_values($needed) : $needed;
    }

    /**
     * e.g: `http://xxx.com`
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->uri->getBaseUrl();
    }

    /**
     * path + queryString
     * e.g. `/content/add?type=blog`
     * @return string
     */
    public function getRequestUri()
    {
        return $this->getRequestTarget();
    }

    /**
     * Is this an pjax request?
     * pjax = pushState + ajax
     * @return bool
     */
    public function isPjax()
    {
        return $this->isAjax() && ($this->getHeaderLine('X-PJAX') === 'true');
    }

    /**
     * @return string
     */
    public function getPjaxContainer()
    {
        return $this->getHeaderLine('X-PJAX-Container');
    }

    /**
     * @param string $default
     * @return string
     */
    public function getReferrer($default = '/')
    {
        return $this->getHeaderLine('REFERER') ?: $default;
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if ($arguments && 0 === strpos($name, 'get')) {
            $filter = substr($name, 3);
            $default = $arguments[1] ?? null;

            return $this->get($arguments[0], $default, lcfirst($filter));
        }

        throw new \BadMethodCallException("Method $name is not a valid method");
    }

    /**
     * @param $value
     * @param $filter
     * @return mixed|null
     */
    public function filtering($value, $filter)
    {
        if ($filter === static::FILTER_RAW) {
            return $value;
        }

        // is a custom filter
        if (!is_string($filter) || !isset(self::$filterList[$filter])) {
            $result = $value;

            // is custom callable filter
            if (is_callable($filter)) {
                $result = call_user_func($filter, $value);
            }

            return $result;
        }

        // is a defined filter
        $filter = self::$filterList[$filter];

        if (!in_array($filter, DataType::types(), true)) {
            $result = call_user_func($filter, $value);
        } else {
            switch (lcfirst(trim($filter))) {
                case DataType::T_BOOL :
                case DataType::T_BOOLEAN :
                    $result = (bool)$value;
                    break;
                case DataType::T_DOUBLE :
                case DataType::T_FLOAT :
                    $result = (float)$value;
                    break;
                case DataType::T_INT :
                case DataType::T_INTEGER :
                    $result = (int)$value;
                    break;
                case DataType::T_STRING :
                    $result = (string)$value;
                    break;
                case DataType::T_ARRAY :
                    $result = (array)$value;
                    break;
                case DataType::T_OBJECT :
                    $result = (object)$value;
                    break;
                default:
                    $result = $value;
                    break;
            }
        }

        return $result;
    }
}
