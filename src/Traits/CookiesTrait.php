<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午1:18
 */

namespace PhpPkg\Http\Message\Traits;

use PhpPkg\Http\Message\Cookies;
use function is_array;

/**
 * Trait CookiesTrait
 *
 * @package PhpPkg\Http\Message\Traits
 */
trait CookiesTrait
{
    /**
     * @var Cookies|null
     */
    private ?Cookies $cookies = null;

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
     * @param null   $default
     * @return mixed
     */
    public function getCookieParam(string $key, $default = null): mixed
    {
        return $this->cookies->get($key, $default);
    }

    /**
     * @param array $cookies
     * @return static
     */
    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;

        $clone->cookies = new Cookies($cookies);

        return $clone;
    }

    /**
     * @param string       $name
     * @param array|string $value
     *
     * @return static
     */
    public function setCookie(string $name, array|string $value): static
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
     * @param array|Cookies $cookies
     *
     * @return static
     */
    public function setCookies(array|Cookies $cookies): static
    {
        if (is_array($cookies)) {
            return $this->setCookiesFromArray($cookies);
        }

        $this->cookies = $cookies;
        return $this;
    }

    /**
     * @param array $cookies
     * @return static
     */
    public function setCookiesFromArray(array $cookies): static
    {
        if (!$this->cookies) {
            $this->cookies = new Cookies($cookies);
        } else {
            $this->cookies->sets($cookies);
        }

        return $this;
    }
}
