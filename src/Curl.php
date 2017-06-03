<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-08
 * Time: 16:40
 */

namespace inhere\http;

use inhere\library\helpers\UrlHelper;

/**
 * Class Curl
 * @package inhere\library\http
 *
 * ```
 * $curl = Curl::make([
 *   'baseUrl' =>  'http://my-site.com'
 * ]);
 * $curl->get('/users/1');
 *
 * $headers = $curl->getResponseHeaders();
 * $data = $curl->getResponseBody();
 * $array = $curl->getArrayData();
 *
 * $post = ['name' => 'john'];
 * $curl->reset()->post('/users/1', $post);
 * // $curl->reset()->byAjax()->post('/users/1', $post);
 * // $curl->reset()->byJson()->post('/users/1', json_encode($post));
 * $array = $curl->getArrayData();
 *
 * ```
 */
class Curl extends CurlLite implements CurlExtraInterface
{
    /**
     * config for self
     * @var array
     */
    private $_config = [
        // open debug mode
        'debug' => false,

        // if 'debug = true ', is valid. will output log to the file. if is empty, output to STDERR.
        'logFile' => '',

        // retry times, when an error occurred.
        'retry' => 0,
    ];

    /**
     * The default curl options
     * @var array
     */
    protected $defaultOptions = [
        // TRUE 将 curl_exec() 获取的信息以字符串返回，而不是直接输出
        CURLOPT_RETURNTRANSFER => true,

        //
        CURLOPT_FOLLOWLOCATION => true,

        // true curl_exec() 会将头文件的信息作为数据流输出到响应的最前面，此时可用 [[self::parseResponse()]] 解析。
        // false curl_exec() 返回的响应就只有body
        CURLOPT_HEADER => true,

        // enable debug
        CURLOPT_VERBOSE => false,

        // auto add REFERER
        CURLOPT_AUTOREFERER => true,

        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 30,

        CURLOPT_SSL_VERIFYPEER => false,
        // isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        CURLOPT_USERAGENT => '5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
        //CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
    ];

    /**
     * Can to retry
     * @var array
     */
    private static $canRetryErrorCodes = [
        CURLE_COULDNT_RESOLVE_HOST,
        CURLE_COULDNT_CONNECT,
        CURLE_HTTP_NOT_FOUND,
        CURLE_READ_ERROR,
        CURLE_OPERATION_TIMEOUTED,
        CURLE_HTTP_POST_ERROR,
        CURLE_SSL_CONNECT_ERROR,
    ];

    /**
     * setting headers for curl
     *
     * [ 'Content-Type' => 'Content-Type: application/json' ]
     *
     * @var array
     */
    private $_headers = [];

    /**
     * setting options for curl
     * @var array
     */
    private $_options = [];

    /**
     * @var array
     */
    private $_cookies = [];

    /**
     * The curl exec response
     * @var string
     */
    private $_response;
    private $_responseBody = '';
    private $_responseHeaders = [];
    private $_responseParsed = false;

    /**
     * The curl exec result mete info.
     * @var array
     */
    private $_responseMeta = [
        // http status code
        'status' => 200,
        'errno' => 0,
        'error' => '',
        'info' => '',
    ];


    /**
     * @param $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, array $args)
    {
        return call_user_func_array([self::make(), $method], $args);
    }

///////////////////////////////////////////////////////////////////////
// extra
///////////////////////////////////////////////////////////////////////

    /**
     * File upload
     * @param string $url The target url
     * @param string $field The post field name
     * @param string $filePath The file path
     * @param string $mimeType The post file mime type
     * param string $postFilename The post file name
     * @return mixed
     */
    public function upload($url, $field, $filePath, $mimeType = '')
    {
        if (!$mimeType) {
            $fInfo = finfo_open(FILEINFO_MIME); // 返回 mime 类型
            $mimeType = finfo_file($fInfo, $filePath) ?: 'application/octet-stream';
        }

        // create file
        if (function_exists('curl_file_create')) {
            $file = curl_file_create($filePath, $mimeType); // , $postFilename
        } else {
            $this->setOption(CURLOPT_SAFE_UPLOAD, true);
            $file = "@{$filePath};type={$mimeType}"; // ;filename={$postFilename}
        }

        $headers = ['Content-Type' => 'multipart/form-data'];

        return $this->post($url, [$field => $file], $headers);
    }

    /**
     * File download and save
     * @param string $url
     * @param string $saveTo
     * @return self
     * @throws \Exception
     */
    public function download($url, $saveTo)
    {
        if (($fp = fopen($saveTo, 'wb')) === false) {
            throw new \RuntimeException('Failed to save the content', __LINE__);
        }

        $data = $this->request($url);

        fwrite($fp, $data);
        fclose($fp);

        return $this;
    }

    /**
     * Image file download and save
     * @param string $imgUrl image url e.g. http://static.oschina.net/uploads/user/277/554046_50.jpg
     * @param string $saveTo 图片保存路径
     * @param string $rename 图片重命名(只写名称，不用后缀) 为空则使用原名称
     * @return string
     */
    public function downloadImage($imgUrl, $saveTo, $rename = '')
    {
        // e.g. http://static.oschina.net/uploads/user/277/554046_50.jpg?t=34512323
        if (strpos($imgUrl, '?')) {
            [$real,] = explode('?', $imgUrl, 2);
        } else {
            $real = $imgUrl;
        }

        $last = trim(strrchr($real, '/'), '/');

        // special url e.g http://img.blog.csdn.net/20150929103749499
        if (false === strpos($last, '.')) {
            $suffix = '.jpg';
            $name = $rename ?: $last;
        } else {
            $info = pathinfo($real, PATHINFO_EXTENSION | PATHINFO_FILENAME);
            $suffix = $info['extension'] ?: '.jpg';
            $name = $rename ?: $info['filename'];
        }

        $imgFile = $saveTo . '/' . $name . $suffix;

        if (file_exists($imgFile)) {
            return $imgFile;
        }

        // set Referrer
        $this->setReferrer('http://www.baidu.com');

        $imgData = $this->request($imgUrl)->getResponseBody();

        file_put_contents($imgFile, $imgData);

        return $imgFile;
    }

    /**
     * Send request
     * @inheritdoc
     */
    public function request($url, $data = null, $method = self::GET, array $headers = [], array $options = [])
    {
        $method = strtoupper($method);

        if (!isset(self::$supportedMethods[$method])) {
            throw new \InvalidArgumentException("The method type [$method] is not supported!");
        }

        // init curl
        $ch = curl_init();

        $this->prepareRequest($ch, $headers, $options);

        // add send data
        if ($data) {
            // allow post data
            if (self::$supportedMethods[$method]) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                $url .= (strpos($url, '?') ? '&' : '?') . http_build_query($data);
            }
        }

        // set request url
        $url = $this->buildUrl($url);
        curl_setopt($ch, CURLOPT_URL, UrlHelper::encode2($url));

        $response = '';
        $retries = (int)$this->_config['retry'];

        // execute
        while ($retries >= 0) {
            if (false === ($response = curl_exec($ch))) {
                $curlErrNo = curl_errno($ch);

                if (false === in_array($curlErrNo, self::$canRetryErrorCodes, true)) {
                    $curlError = curl_error($ch);

                    // throw new \RuntimeException(sprintf('Curl error (code %s): %s', $curlErrNo, $curlError));
                    $this->_responseMeta['errno'] = $curlErrNo;
                    $this->_responseMeta['error'] = $curlError;
                }

                $retries--;
                continue;
            }
            break;
        }

        // get http status code
        $this->_responseMeta['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($this->isDebug()) {
            $this->_responseMeta['info'] = curl_getinfo($ch);
        }

        $this->_response = $response;

        // close
        curl_close($ch);

        return $this;
    }

///////////////////////////////////////////////////////////////////////
//   helper method
///////////////////////////////////////////////////////////////////////

    protected function prepareRequest($ch, array $headers = [], array $options = [])
    {
        $this->resetResponse();

        // open debug
        if ($this->isDebug()) {
            $this->_options[CURLOPT_VERBOSE] = true;

            // redirect exec log to logFile.
            if ($logFile = $this->_config['logFile']) {
                $this->_options[CURLOPT_STDERR] = $logFile;
            }
        }

        // set options, can not use `array_merge()`, $options key is int.

        // merge default options
        $this->_options = self::mergeOptions($this->defaultOptions, $this->_options);
        $this->_options = self::mergeOptions($this->_options, $options);

        // set headers
        $this->setHeaders($headers);

        // append http headers to options
        if ($this->_headers) {
            $options[CURLOPT_HTTPHEADER] = $this->getHeaders(true);
        }

        // append http cookies to options
        if ($this->_cookies) {
            $options[CURLOPT_COOKIE] = http_build_query($this->_cookies, '', '; ');
        }

        curl_setopt_array($ch, $this->_options);
    }

    protected function parseResponse()
    {
        // have been parsed || no response data
        if ($this->_responseParsed || !($response = $this->_response)) {
            return false;
        }

        // if no return headers data
        if (false === $this->getOption(CURLOPT_HEADER, false)) {
            $this->_responseBody = $response;
            $this->_responseParsed = true;

            return true;
        }

        # Headers regex
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        # Extract headers from response
        preg_match_all($pattern, $response, $matches);
        $headers_string = array_pop($matches[0]);
        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

        # Include all received headers in the $headers_string
        while (count($matches[0])) {
            $headers_string = array_pop($matches[0]) . $headers_string;
        }

        # Remove all headers from the response body
        $this->_responseBody = str_replace($headers_string, '', $response);

        # Extract the version and status from the first header
        $versionAndStatus = array_shift($headers);

        preg_match_all('#HTTP/(\d\.\d)\s((\d\d\d)\s((.*?)(?=HTTP)|.*))#', $versionAndStatus, $matches);

        $this->_responseHeaders['Http-Version'] = array_pop($matches[1]);
        $this->_responseHeaders['Status-Code'] = array_pop($matches[3]);
        $this->_responseHeaders['Status'] = array_pop($matches[2]);

        # Convert headers into an associative array
        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $this->_responseHeaders[$matches[1]] = $matches[2];
        }

        $this->_responseParsed = true;

        return true;
    }

    /**
     * @return array
     */
    public static function getSupportedMethods()
    {
        return self::$supportedMethods;
    }

///////////////////////////////////////////////////////////////////////
//   response data
///////////////////////////////////////////////////////////////////////

    /**
     * @return bool
     */
    public function isOk()
    {
        return !$this->_responseMeta['error'];
    }

    /**
     * @return bool
     */
    public function isFail()
    {
        return !!$this->_responseMeta['error'];
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @param null|string $key
     * @return array|mixed|null
     */
    public function getMeta($key = null)
    {
        return $this->getResponseMeta($key);
    }

    public function getResponseMeta($key = null)
    {
        if ($key) {
            return $this->_responseMeta[$key] ?? null;
        }

        return $this->_responseMeta;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->getResponseBody();
    }

    public function getResponseBody()
    {
        $this->parseResponse();

        return $this->_responseBody;
    }

    /**
     * @return bool|array
     */
    public function getArrayData()
    {
        return $this->getJsonArray();
    }

    /**
     * @return bool|array
     */
    public function getJsonArray()
    {
        if (!$this->getResponseBody()) {
            return false;
        }

        $data = json_decode($this->_responseBody, true);

        if (json_last_error() > 0) {
            return false;
        }

        return $data;
    }

    /**
     * @return bool|\stdClass
     */
    public function getJsonObject()
    {
        if (!$this->getResponseBody()) {
            return false;
        }

        $data = json_decode($this->_responseBody);

        if (json_last_error() > 0) {
            return false;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getResponseHeaders()
    {
        $this->parseResponse();

        return $this->_responseHeaders;
    }

    /**
     * @param string $name
     * @param null $default
     * @return string
     */
    public function getResponseHeader($name, $default = null)
    {
        $this->parseResponse();

        return $this->_responseHeaders[$name] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getHttpCode()
    {
        return $this->_responseMeta['status'];
    }

    /**
     * Was an 'info' header returned.
     */
    public function isInfo()
    {
        return $this->_responseMeta['status'] >= 100 && $this->_responseMeta['status'] < 200;
    }

    /**
     * Was an 'OK' response returned.
     */
    public function isSuccess()
    {
        return $this->_responseMeta['status'] >= 200 && $this->_responseMeta['status'] < 300;
    }

    /**
     * Was a 'redirect' returned.
     */
    public function isRedirect()
    {
        return $this->_responseMeta['status'] >= 300 && $this->_responseMeta['status'] < 400;
    }

    /**
     * Was an 'error' returned (client error or server error).
     */
    public function isError()
    {
        return $this->_responseMeta['status'] >= 400 && $this->_responseMeta['status'] < 600;
    }

///////////////////////////////////////////////////////////////////////
//   reset data/unset attribute
///////////////////////////////////////////////////////////////////////

    /**
     * @return $this
     */
    public function resetHeaders()
    {
        $this->_headers = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetCookies()
    {
        $this->_cookies = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetOptions()
    {
        $this->_options = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetResponse()
    {
        $this->_response = $this->_responseBody = null;
        $this->_responseParsed = false;
        $this->_responseHeaders = [];
        $this->_responseMeta = [
            // http status code
            'status' => 200,
            'errno' => 0,
            'error' => '',
            'info' => '',
        ];

        return $this;
    }

    /**
     * Reset the last time headers,cookies,options,response data.
     * @return $this
     */
    public function reset()
    {
        return $this->resetAll();
    }

    public function resetAll()
    {
        $this->_headers = $this->_options = $this->_cookies = [];

        return $this->resetResponse();
    }

///////////////////////////////////////////////////////////////////////
//   request cookies
///////////////////////////////////////////////////////////////////////

    /**
     * Set contents of HTTP Cookie header.
     * @param string $key The name of the cookie
     * @param string $value The value for the provided cookie name
     * @return $this
     */
    public function setCookie($key, $value)
    {
        $this->_cookies[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->_cookies;
    }

///////////////////////////////////////////////////////////////////////
//   request headers
///////////////////////////////////////////////////////////////////////

    public function byJson()
    {
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        return $this;
    }

    public function byXhr()
    {
        return $this->byAjax();
    }

    public function byAjax()
    {
        $this->setHeader('X-Requested-With', 'XMLHttpRequest');

        return $this;
    }

    /**
     * get Headers
     * @param bool $onlyValues
     * @return array
     */
    public function getHeaders($onlyValues = false)
    {
        return $onlyValues ? array_values($this->_headers) : $this->_headers;
    }

    /**
     * set Headers
     * @inheritdoc
     */
    public function setHeaders(array $headers, $override = false)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value, $override);
        }

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @param bool $override
     * @return $this
     */
    public function setHeader($name, $value, $override = false)
    {
        if ($override || !isset($this->_headers[$name])) {
            $this->_headers[$name] = ucwords($name) . ": $value";
        }

        return $this;
    }

    /**
     * @param string|array $name
     * @return $this
     */
    public function delHeader($name)
    {
        foreach ((array)$name as $item) {
            if (isset($this->_headers[$item])) {
                unset($this->_headers[$item]);
            }
        }

        return $this;
    }

///////////////////////////////////////////////////////////////////////
//  request options
///////////////////////////////////////////////////////////////////////

    /**
     * @param string $userAgent
     * @return $this
     */
    public function setUserAgent($userAgent)
    {
        $this->_options[CURLOPT_USERAGENT] = $userAgent;

        return $this;
    }

    /**
     * @param string $referrer
     * @return $this
     */
    public function setReferrer($referrer)
    {
        $this->_options[CURLOPT_REFERER] = $referrer;

        return $this;
    }

    /**
     * @param string $host
     * @param string $port
     * @return $this
     */
    public function setProxy($host, $port)
    {
        $this->_options[CURLOPT_PROXY] = $host;
        $this->_options[CURLOPT_PROXYPORT] = $port;

        return $this;
    }

    /**
     * Use http auth
     * @param string $user
     * @param string $pwd
     * @param int $authType CURLAUTH_BASIC CURLAUTH_DIGEST
     * @return $this
     */
    public function setUserAuth($user, $pwd = '', $authType = CURLAUTH_BASIC)
    {
        $this->_options[CURLOPT_HTTPAUTH] = $authType;
        $this->_options[CURLOPT_USERPWD] = "$user:$pwd";

        return $this;
    }

    /**
     * Use SSL certificate/private-key auth
     *
     * @param string $pwd The SLL CERT/KEY password
     * @param string $file The SLL CERT/KEY file
     * @param string $authType The auth type: 'cert' or 'key'
     * @return $this
     */
    public function setSSLAuth($pwd, $file, $authType = self::SSL_TYPE_CERT)
    {
        if ($authType !== self::SSL_TYPE_CERT && $authType !== self::SSL_TYPE_KEY) {
            throw new \InvalidArgumentException('The SSL auth type only allow: cert|key');
        }

        if (!file_exists($file)) {
            $name = $authType === self::SSL_TYPE_CERT ? 'certificate' : 'private key';
            throw new \InvalidArgumentException("The SSL $name file not found: {$file}");
        }

        if ($authType === self::SSL_TYPE_CERT) {
            $this->_options[CURLOPT_SSLCERTPASSWD] = $pwd;
            $this->_options[CURLOPT_SSLCERT] = $file;
        } else {
            $this->_options[CURLOPT_SSLKEYPASSWD] = $pwd;
            $this->_options[CURLOPT_SSLKEY] = $file;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setOptions(array $options)
    {
        $this->_options = array_merge($this->_options, $options);

        return $this;
    }

    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @param int $name
     * @param bool $default
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return $this->_options[$name] ?? $default;
    }

///////////////////////////////////////////////////////////////////////
//   config self
///////////////////////////////////////////////////////////////////////

    /**
     * @inheritdoc
     */
    public function getConfig($name = null, $default = null)
    {
        if ($name === null) {
            return $this->_config;
        }

        return $this->_config[$name] ?? $default;
    }

    /**
     * @inheritdoc
     */
    public function setConfig(array $config)
    {
        $this->_config = array_merge($this->_config, $config);

        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return (bool)$this->_config['debug'];
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->_config['debug'] = (bool)$debug;

        return $this;
    }

    /**
     * @param int $retry
     * @return $this
     */
    public function setRetry($retry)
    {
        $this->_config['retry'] = (int)$retry;

        return $this;
    }

}
