<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/28 0028
 * Time: 23:00
 *
 * @from slim3
 */

namespace PhpComp\Http\Message;

use PhpComp\Http\Message\Component\Collection;

/**
 * Class Cookies
 * @package PhpComp\Http\Message
 *
 * Cookies:
 *
 * [
 *  'name' => [ options ...]
 * ]
 */
class Cookies extends Collection
{
    /**
     * Default cookie properties
     * @var array
     */
    protected $defaults = [
        'value'    => '',
        'domain'   => null,
        'hostOnly' => null,
        'path'     => null,
        'expires'  => null,
        'secure'   => false,
        'httpOnly' => false
    ];

    /**
     * Set default cookie properties
     * @param array $settings
     */
    public function setDefaults(array $settings): void
    {
        $this->defaults = \array_replace($this->defaults, $settings);
    }

    /**
     * Set cookie
     * @param string       $name Cookie name
     * @param string|array $value Cookie value, or cookie properties
     * @return $this
     */
    public function set($name, $value): self
    {
        if (!\is_array($value)) {
            $value = ['value' => (string)$value];
        }

        parent::set($name, \array_replace($this->defaults, $value));

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function add($name, $value): self
    {
        if (!\is_array($value)) {
            $value = ['value' => (string)$value];
        }

        return parent::add($name, \array_replace($this->defaults, $value));
    }

    /**
     * Convert to `Set-Cookie` headers
     * @return string[]
     */
    public function toHeaders(): array
    {
        $headers = [];
        foreach ($this as $name => $properties) {
            $headers[] = $this->toHeaderLine($name, $properties);
        }

        return $headers;
    }

    /**
     * Convert to `Set-Cookie` header
     * @param  string $name Cookie name
     * @param  array  $properties Cookie properties
     * @return string
     */
    protected function toHeaderLine(string $name, array $properties): string
    {
        $result = \urlencode($name) . '=' . \urlencode($properties['value']);

        if (isset($properties['domain'])) {
            $result .= '; domain=' . $properties['domain'];
        }

        if (isset($properties['path'])) {
            $result .= '; path=' . $properties['path'];
        }

        if (isset($properties['expires'])) {
            if (\is_string($properties['expires'])) {
                $timestamp = \strtotime($properties['expires']);
            } else {
                $timestamp = (int)$properties['expires'];
            }
            if ($timestamp !== 0) {
                $result .= '; expires=' . \gmdate('D, d-M-Y H:i:s e', $timestamp);
            }
        }

        if (!empty($properties['secure'])) {
            $result .= '; secure';
        }

        if (!empty($properties['hostOnly'])) {
            $result .= '; HostOnly';
        }

        if (!empty($properties['httpOnly'])) {
            $result .= '; HttpOnly';
        }

        return $result;
    }

    /**
     * @return string
     * header: `"Cookie: $cookieValue" . Header::EOL`
     */
    public function toRequestHeader(): string
    {
        $cookieValue = '';

        foreach ($this as $name => $value) {
            $cookieValue .= \urlencode($name) . '=' . \urlencode($value['value']) . '; ';
        }

        return trim($cookieValue, '; ');
    }

    /**
     * Parse HTTP request `Cookie:` header and extract
     * into a PHP associative array.
     * @param  string|array $cookieText The raw HTTP request `Cookie:` header
     * @return array Associative array of cookie names and values
     * @throws \InvalidArgumentException if the cookie data cannot be parsed
     */
    public static function parseFromRawHeader($cookieText): array
    {
        $cookies = [];

        if (\is_array($cookieText)) {
            $cookieText = \array_shift($cookieText) ?: '';
        }

        if (!\is_string($cookieText)) {
            throw new \InvalidArgumentException('Cannot parse Cookie data. Header value must be a string.');
        }

        if (!$cookieText) {
            return $cookies;
        }

        $pieces = \preg_split('#[;]\s*#', \rtrim($cookieText, "\r\n"));

        foreach ($pieces as $cookie) {
            $cookie = \explode('=', $cookie, 2);

            if (\count($cookie) === 2) {
                $key   = \urldecode($cookie[0]);
                $value = \urldecode($cookie[1]);

                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }
}
