<?php

namespace App\Model\TikTok;

use App\Exception\TikTokException;

class PublishInfoTikTok
{
    public const NO_ERRORS = 'ok';

    private $success;
    private $publishId;
    private $uploadUrl;
    private $errorCode;
    private $errorMessage;
    private $logId;

    public function __construct($success, $publishId, $uploadUrl, $errorCode, $errorMessage, $logId)
    {
        $this->success = $success;
        $this->publishId = $publishId;
        $this->uploadUrl = $uploadUrl;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->logId = $logId;
    }

    public static function fromJson(array $json)
    {
        if (empty($json['error']['code'])) {
            throw new TikTokException('Invalid TikTok JSON: '.var_export($json, 1));
        }

        if (self::NO_ERRORS == $json['error']['code']) {
            $success = true;
            $errorCode = '';
        } else {
            $success = false;
            $errorCode = $json['error']['code'];
        }

        $publishId = '';
        if (!empty($json['data']['publish_id'])) {
            $publishId = $json['data']['publish_id'];
        }

        $uploadUrl = '';
        if (!empty($json['data']['upload_url'])) {
            $uploadUrl = $json['data']['upload_url'];
        }

        $errorMessage = '';
        if (!empty($json['error']['message'])) {
            $errorMessage = $json['error']['message'];
        }

        $logId = '';
        if (!empty($json['error']['log_id'])) {
            $logId = $json['error']['log_id'];
        }

        return new self($success, $publishId, $uploadUrl, $errorCode, $errorMessage, $logId);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getPublishId()
    {
        return $this->publishId;
    }

    public function getUploadUrl()
    {
        return $this->uploadUrl;
    }

    public function getErrorCode()
    {
        return (string) $this->errorCode;
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
