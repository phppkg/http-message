<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午1:18
 */

namespace Inhere\Http\Traits;

use Inhere\Http\Cookies;

/**
 * Trait CookiesTrait
 * @package Inhere\Http\Traits
 */
trait CookiesTrait
{
    /**
     * @var Cookies
     */
    private $cookies;

    /*******************************************************************************
     * Cookies
     ******************************************************************************/

    /**
     * @return array
     */
    public function getCookieParams(): array
    {
        return $this->cookies->all();
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getCookieParam(string $key, $default = null)
    {
        return $this->cookies->get($key, $default);
    }

    /**
     * @param array $cookies
     * @return self
     */
    public function withCookieParams(array $cookies): self
    {
        $clone = clone $this;
        $clone->cookies = new Cookies($cookies);

        return $clone;
    }

    /**
     * @param string $name
     * @param string|array $value
     * @return $this
     */
    public function setCookie(string $name, $value): self
    {
        $this->cookies->set($name, $value);

        return $this;
    }

    /**
     * @return Cookies
     */
    public function getCookies(): Cookies
    {
        return $this->cookies;
    }

    /**
     * @param Cookies|array $cookies
     * @return $this
     */
    public function setCookies($cookies): self
    {
        if (\is_array($cookies)) {
            return $this->setCookiesFromArray($cookies);
        }

        $this->cookies = $cookies;

        return $this;
    }

    /**
     * @param array $cookies
     * @return $this
     */
    public function setCookiesFromArray(array $cookies): self
    {
        if (!$this->cookies) {
            $this->cookies = new Cookies($cookies);
        } else {
            $this->cookies->sets($cookies);
        }

        return $this;
    }
}
