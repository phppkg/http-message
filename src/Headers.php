<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-30
 * Time: 19:02
 */

namespace PhpPkg\Http\Message;

use PhpPkg\Http\Message\Util\Collection;

/**
 * Class Headers
 * @package PhpPkg\Http\Message
 */
class Headers extends Collection
{
    /**
     * the connection header line data end char
     */
    public const EOL = "\r\n";

    public const HEADER_END = "\r\n\r\n";

    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     *
     * @var array
     */
    protected static array $special = [
        'CONTENT_TYPE'    => 1,
        'CONTENT_LENGTH'  => 1,
        'PHP_AUTH_USER'   => 1,
        'PHP_AUTH_PW'     => 1,
        'PHP_AUTH_DIGEST' => 1,
        'AUTH_TYPE'       => 1,
    ];

    /**
     * Return array of HTTP header names and values.
     * This method returns the _original_ header name
     * as specified by the end user.
     *
     * @return array
     */
    public function all(): array
    {
        $out = [];
        foreach (parent::all() as $key => $props) {
            $out[$props['originalKey']] = $props['value'];
        }

        return $out;
    }

    /**
     * Set HTTP header value
     * This method sets a header value. It replaces
     * any values that may already exist for the header name.
     * @param string       $key The case-insensitive header name
     * @param string|array $value The header value
     * @return mixed
     */
    public function set($key, $value): mixed
    {
        return parent::set($this->normalizeKey($key), [
            'value'       => (array)$value,
            'originalKey' => $key
        ]);
    }

    /**
     * Get HTTP header value
     *
     * @param  string $key The case-insensitive header name
     * @param  mixed  $default The default value if key does not exist
     *
     * @return string[]|mixed|null
     */
    public function get(string $key, $default = null): mixed
    {
        if ($this->has($key)) {
            return parent::get($this->normalizeKey($key))['value'];
        }

        return $default;
    }

    /**
     * @param      $name
     * @param null $default
     * @return null|string
     */
    public function getLine($name, $default = null): ?string
    {
        if ($val = $this->get($name)) {
            return \implode(',', $val);
        }

        return $default;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return mixed|null
     */
    public function add(string $name, mixed $value): mixed
    {
        if (!$value) {
            return $this;
        }

        return parent::add($this->normalizeKey($name), [
            'value'       => (array)$value,
            'originalKey' => $name
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return parent::has($this->normalizeKey($key));
    }

    /**
     * {@inheritDoc}
     */
    public function remove($key)
    {
        parent::remove($this->normalizeKey($key));
    }

    /**
     * @param string $key
     * @return bool|string
     */
    public function normalizeKey(string $key): bool|string
    {
        $key = \str_replace('_', '-', \strtolower($key));

        if (\str_starts_with($key, 'Http-')) {
            $key = \substr($key, 5);
        }

        return $key;
    }

    /**
     * get client supported languages from header
     * eg: `Accept-Language:zh-CN,zh;q=0.8`
     * @return array
     */
    public function getAcceptLanguages(): array
    {
        $ls = [];

        if ($value = $this->getLine('Accept-Language')) {
            if (\strpos($value, ';')) {
                [$value,] = \explode(';', $value, 2);
            }

            $value = \str_replace(' ', '', $value);
            $ls    = \explode(',', $value);
        }

        return $ls;
    }

    /**
     * get client supported languages from header
     * eg: `Accept-Encoding:gzip, deflate, sdch, br`
     * @return array
     */
    public function getAcceptEncodes(): array
    {
        $ens = [];

        if ($value = $this->getLine('Accept-Encoding')) {
            if (\strpos($value, ';')) {
                [$value,] = \explode(';', $value, 2);
            }

            $value = \str_replace(' ', '', $value);
            $ens   = \explode(',', $value);
        }

        return $ens;
    }

    /**
     * @param bool $join to String
     * @return array|string
     */
    public function toHeaderLines(bool $join = false): array|string
    {
        $output = [];

        foreach ($this as $name => $info) {
            $name     = \ucwords($name, '-');
            $value    = \implode(',', $info['value']);
            $output[] = "$name: $value\r\n";
        }

        return $join ? \implode('', $output) : $output;
    }

    /**
     * @return array
     */
    public function getLines(): array
    {
        $output = [];

        foreach ($this as $name => $info) {
            $name          = \ucwords($name, '-');
            $output[$name] = \implode(',', $info['value']);
        }

        return $output;
    }
}
