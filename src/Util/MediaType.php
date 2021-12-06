<?php declare(strict_types=1);

namespace PhpPkg\Http\Message\Util;

/**
 * class MediaType
 *
 * @author inhere
 */
class MediaType
{
    public const TEXT_XML = 'text/xml';

    public const APP_XML  = 'application/xml';
    public const APP_JSON = 'application/json';

    public const FORM_URLENCODED = 'application/x-www-form-urlencoded';
    public const FORM_DATA = 'multipart/form-data';
}
