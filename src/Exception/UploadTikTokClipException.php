<?php

namespace App\Exception;

class UploadTikTokClipException extends \Exception
{
    public function __construct(
        string $message,
        int $code = 0,
        ?string $username = null,
        ?string $errorCode = null,
        ?\Throwable $previous = null,
    ) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $trace[0]['file'] ?? 'unknown';
        $line = $trace[0]['line'] ?? 'unknown';
        $function = $trace[0]['function'] ?? 'unknown';
        $class = isset($trace[0]['class']) ? $trace[0]['class'].'::' : '';

        $errorMessage = sprintf(
            '[SocialAccountUsername(%s)][Method(%s)][Line(%s)][Code(%s)] %s',
            $username,
            $class.$function,
            $line,
            $errorCode,
            $message
        );

        parent::__construct($errorMessage, $code, $previous);
    }
}
