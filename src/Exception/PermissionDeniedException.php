<?php

namespace Platformsh\Cli\Exception;

class PermissionDeniedException extends HttpException
{
    protected $message = 'Permission denied. Check your project or environment permissions.';
    protected $code = 6;
}
