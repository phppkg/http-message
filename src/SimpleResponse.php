<?php

namespace inhere\http;

use inhere\library\helpers\ObjectHelper;
use inhere\library\StdObject;

/**
 * Class SimpleResponse
 * @package inhere\library\http
 */
class SimpleResponse extends StdObject
{
    const DEFAULT_CHARSET = 'UTF-8';

    /**
     * output charset
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $body = [];

    /**
     * @param $name
     * @param $value
     */
    public function header($name, $value)
    {
        header("$name: $value");
    }

    /**
     * @param $content
     * @param null|string $name
     * @return $this
     */
    public function write($content, $name = null)
    {
        if ($name) {
            $this->body[$name] = $content;
        } else {
            $this->body[] = $content;
        }

        return $this;
    }

    /**
     * send response
     * @param  string $content
     * @return mixed
     */
    public function send($content = '')
    {
        if ($content) {
            $this->write($content);
        }

        header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
        header('Content-type: text/html; charset=' . $this->charset);

        $content = implode('', $this->body);

        if (!$content) {
            throw new \RuntimeException('No content to display.');
        }

        echo $content;

        $this->body = [];

        return true;
    }

    /**
     * output json response
     * @param  array $data
     * @return mixed
     */
    public function json(array $data)
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
        header('Content-type: application/json; charset=' . $this->charset);

        echo json_encode($data);

        return true;
    }

    /**
     * @param $data
     * @param int $code
     * @param string $msg
     * @return mixed
     */
    public function formatJson($data, $code = 0, $msg = '')
    {
        // if `$data` is integer, equals to `formatJson(int $code, string $msg )`
        if (is_numeric($data)) {
            $jsonData = [
                'code' => (int)$data,
                'msg' => $code,
                'data' => [],
            ];
        } else {
            $jsonData = [
                'code' => (int)$code,
                'msg' => $msg ?: 'successful!',
                'data' => (array)$data,
            ];
        }

        return $this->json($jsonData);
    }
}
