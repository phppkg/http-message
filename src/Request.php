<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-31
 * Time: 14:05
 */

namespace inhere\http;

use inhere\validate\StrainerList;
use inhere\library\DataType;

/**
 * Class Request
 * @package inhere\library\http
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
 * @property  \Slim\Http\Uri $uri;
 */
class Request extends \Slim\Http\Request
{
    /**
     * return raw data
     */
    const FILTER_RAW = 'raw';

    /**
     * @var array
     */
    protected $filterList = [
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
        'trimmed' => StrainerList::class . '::trim',

        // safe data
        'safe' => 'htmlspecialchars',

        // abs((int)$var)
        'number' => StrainerList::class . '::abs',
        // will use filter_var($var ,FILTER_SANITIZE_EMAIL)
        'email' => StrainerList::class . '::email',
        // will use filter_var($var ,FILTER_SANITIZE_URL)
        'url' => StrainerList::class . '::url',

        // will use filter_var($var ,FILTER_SANITIZE_ENCODED, $settings);
        'encoded' => StrainerList::class . '::encoded',
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
     * @return array|null
     */
    public function post()
    {
        return $this->getParsedBody();
    }

    /**
     * @param $name
     * @return \Slim\Http\UploadedFile
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
    public function get($name, $default = null, $filter = 'raw')
    {
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
     * @return array
     */
    public function getMulti(array $needKeys = [])
    {
        $needed = [];

        foreach ($needKeys as $key => $value) {
            if (is_int($key)) {
                $needed[$value] = $this->getParam($value);
            } else {
                $needed[$key] = $this->filtering($key, $value);
            }
        }

        return $needed;
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
     * Is this an XHR request?
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->isXhr();
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

    public function getPjaxContainer()
    {
        return $this->getHeaderLine('X-PJAX-Container');
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (0 === strpos($name, 'get') && $arguments) {
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
        if (!is_string($filter) || !isset($this->filterList[$filter])) {
            $result = $value;

            // is custom callable filter
            if (is_callable($filter)) {
                $result = call_user_func($filter, $value);
            }

            return $result;
        }

        // is a defined filter
        $filter = $this->filterList[$filter];

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
