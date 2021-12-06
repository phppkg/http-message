<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/11/28
 * Time: 下午11:43
 */

namespace PhpPkg\Http\Message\Response;

use PhpPkg\Http\Message\Response;
use PhpPkg\Http\Message\Traits\ExtendedResponseTrait;

/**
 * Class ExtendedResponse
 * @package PhpPkg\Http\Message\Response
 */
class ExtendedResponse extends Response
{
    use ExtendedResponseTrait;
}
