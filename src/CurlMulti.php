<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/5
 * Time: 下午9:17
 */

namespace inhere\http;

/**
 * Class CurlMulti
 * @package inhere\library\http
 */
class CurlMulti extends CurlLite
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $chMap = [];

    /**
     * @var resource
     */
    private $mh;

    /**
     * make Multi
     * @param  array $data
     * @return self
     */
    public function create(array $data)
    {
        $this->mh = curl_multi_init();

        foreach ($data as $key => $opts) {
            $opts = array_merge($this->defaultOptions, $opts);

            $this->chMap[$key] = $this->createResource($opts['url'], [], [], $opts);

            curl_multi_add_handle($this->mh, $this->chMap[$key]);
        }

        unset($data);

        return $this;
    }

    /**
     * @param $url
     * @param null $data
     * @param array $headers
     * @param array $options
     * @return $this
     */
    public function append($url, $data = null, array $headers = [], array $options = [])
    {
        $this->chMap[] = $ch = $this->createResource($url, $data, $headers, $options);

        curl_multi_add_handle($this->mh, $ch);

        return $this;
    }

    /**
     * execute multi request
     * @param null|resource $mh
     * @return bool|array
     */
    public function execute($mh = null)
    {
        if (!($mh = $mh ?: $this->mh)) {
            return false;
        }

        $active = true;
        $mrc = CURLM_OK;

        while ($active && $mrc === CURLM_OK) {
            // Solve CPU 100% usage
            if (curl_multi_select($mh) === -1) {
                usleep(100);
            }

            do {
                $mrc = curl_multi_exec($mh, $active);
                // curl_multi_select($mh); // Solve CPU 100% usage
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);
        }

        $responses = [];

        // 关闭全部句柄
        foreach ($this->chMap as $key => $ch) {
            curl_multi_remove_handle($mh, $ch);
            $eno = curl_errno($ch);

            if ($eno) {
                $eor = curl_error($ch);
                $this->errors[$key] = [$eno, $eor];
                $responses[$key] = null;
            } else {
                $responses[$key] = curl_multi_getcontent($ch);
            }
        }

        curl_multi_close($mh);

        return $responses;
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        return !$this->errors;
    }

    /**
     * @return bool
     */
    public function isFail()
    {
        return !!$this->errors;
    }

    /**
     * reset
     */
    public function reset()
    {
        parent::reset();

        $this->mh = null;
        $this->chMap = $this->errors = [];
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getChMap(): array
    {
        return $this->chMap;
    }

    /**
     * @param array $chMap
     */
    public function setChMap(array $chMap)
    {
        $this->chMap = $chMap;
    }

    /**
     * @return resource
     */
    public function getMh(): resource
    {
        return $this->mh;
    }

    /**
     * @param resource $mh
     */
    public function setMh(resource $mh)
    {
        $this->mh = $mh;
    }
}
