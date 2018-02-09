<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/11/28
 * Time: 下午11:43
 */

namespace Inhere\Http\Response;

use Inhere\Http\Response;
use Inhere\Http\Traits\ExtendedResponseTrait;

/**
 * Class ExtendedResponse
 * @package Inhere\Http\Response
 */
class ExtendedResponse extends Response
{
    use ExtendedResponseTrait;
}
