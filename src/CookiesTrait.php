<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午1:18
 */

namespace Inhere\Http;

/**
 * Trait CookiesTrait
 * @package Inhere\Http
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

    /**
     * @param string $name
     * @param string|array $value
     * @return $this
     */
    public function setCookie(string $name, $value)
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
    public function setCookies($cookies)
    {
        if (is_array($cookies)) {
            return $this->setCookiesFromArray($cookies);
        }

        $this->cookies = $cookies;

        return $this;
    }

    /**
     * @param array $cookies
     * @return $this
     */
    public function setCookiesFromArray(array $cookies)
    {
        if (!$this->cookies) {
            $this->cookies = new Cookies($cookies);
        } else {
            $this->cookies->sets($cookies);
        }

        return $this;
    }
}
