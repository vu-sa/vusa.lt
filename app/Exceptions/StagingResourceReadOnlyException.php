<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class StagingResourceReadOnlyException extends HttpException
{
    public static function files(): self
    {
        return new self(403, 'File modifications are disabled in staging because files are shared with production.');
    }

    public static function sharepoint(): self
    {
        return new self(403, 'SharePoint modifications in staging are limited to the test site.');
    }
}
