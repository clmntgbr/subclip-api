<?php

namespace App\Model\TikTok;

use App\Exception\TikTokException;

class PublishStatusTikTok
{
    public const NO_ERRORS = 'ok';
    public const STATUS_DOWNLOADING = 'PROCESSING_DOWNLOAD';
    public const STATUS_UPLOADING = 'PROCESSING_UPLOAD';
    public const PUBLISH_COMPLETE = 'PUBLISH_COMPLETE';
    public const FAILED = 'FAILED';

    private bool $success;
    private string $status;
    private string $errorCode;
    private string $errorMessage;
    private string $logId;
    private string $publicPostId;

    public function __construct(bool $success, string $status, string $publicPostId, string $errorCode, string $errorMessage = '', string $logId = '')
    {
        $this->success = $success;
        $this->status = $status;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->logId = $logId;
        $this->publicPostId = $publicPostId;
    }

    public static function fromJson(array $json)
    {
        if (empty($json['error']['code'])) {
            throw new TikTokException('Invalid TikTok JSON: '.var_export($json, 1));
        }

        $errorMessage = '';
        if (self::NO_ERRORS != $json['error']['code']) {
            $errorMessage = '';
            if (!empty($json['error']['message'])) {
                $errorMessage = $json['error']['message'];
            }
            $logId = '';
            if (!empty($json['error']['log_id'])) {
                $logId = $json['error']['log_id'];
            }

            return new self(false, '', '', $json['error']['code'], $errorMessage, $logId);
        }

        if (empty($json['data']['status'])) {
            throw new \Exception('Invalid TikTok JSON: '.var_export($json, 1));
        }

        if (self::FAILED == $json['data']['status']) {
            $reason = '';
            if (!empty($json['data']['fail_reason'])) {
                $reason = $json['data']['fail_reason'];
            }

            return new self(false, $json['data']['status'], '', $json['data']['video_pull_failed'], $reason);
        }

        $publicPostId = '';
        if (!empty($json['data']['publicaly_available_post_id'])) {
            if (is_array($json['data']['publicaly_available_post_id'])) {
                $publicPostId = $json['data']['publicaly_available_post_id'][0];
            } else {
                $publicPostId = (int) $json['data']['publicaly_available_post_id'];
            }
        }

        return new self(true, $json['data']['status'], $publicPostId, $json['error']['code']);
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getPublicPostId()
    {
        return $this->publicPostId;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getLogId()
    {
        return $this->logId;
    }
}
