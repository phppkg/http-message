<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-30
 * Time: 13:12
 */

namespace PhpPkg\Http\Message;

use PhpPkg\Http\Message\Traits\MessageTrait;
use Psr\Http\Message\MessageInterface;

/**
 * Class Message
 *
 * @property Cookies $cookies
 *
 */
class Message implements MessageInterface
{
    use MessageTrait;

    /**
     * the connection header line data end char
     */
    public const EOL = "\r\n";

    /**
     * BaseMessage constructor.
     * @param string        $protocol
     * @param string        $protocolVersion
     * @param array|Headers $headers
     * @param string        $body
     * @throws \InvalidArgumentException
     */
    public function __construct(string $protocol = 'http', string $protocolVersion = '1.1', $headers = null, $body = '')
    {
        $this->initialize($protocol, $protocolVersion, $headers, $body);
    }
}
