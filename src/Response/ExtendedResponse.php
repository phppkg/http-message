<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/11/28
 * Time: 下午11:43
 */

namespace PhpComp\Http\Message\Response;

use PhpComp\Http\Message\Response;
use PhpComp\Http\Message\Traits\ExtendedResponseTrait;

/**
 * Class ExtendedResponse
 * @package PhpComp\Http\Message\Response
 */
class ExtendedResponse extends Response
{
    use ExtendedResponseTrait;
}
