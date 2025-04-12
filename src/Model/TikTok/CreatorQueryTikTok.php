<?php

namespace App\Model\TikTok;

use App\Service\TikTokService;

class CreatorQueryTikTok
{
    private string $avatarUrl;
    private string $nickname;
    private string $username;
    private bool $duetOff;
    private bool $stitchOff;
    private bool $commentOff;
    private int $maxVideoDurationSec;
    private array $privacyOptions;

    public function __construct(
        string $avatarUrl,
        string $nickname,
        string $username,
        bool $duetOff,
        bool $stitchOff,
        bool $commentOff,
        int $maxVideoDurationSec,
        array $privacyOptions,
    ) {
        $this->avatarUrl = $avatarUrl;
        $this->nickname = $nickname;
        $this->username = $username;
        $this->duetOff = $duetOff;
        $this->stitchOff = $stitchOff;
        $this->commentOff = $commentOff;
        $this->maxVideoDurationSec = $maxVideoDurationSec;
        $this->privacyOptions = $privacyOptions;
    }

    public static function fromJson(array $json): self
    {
        if (empty($json['data']) || empty($json['data']['creator_nickname'])) {
            throw new \Exception('Invalid TikTok JSON: '.var_export($json, 1));
        }

        $data = $json['data'];
        $options = [];

        foreach ($data['privacy_level_options'] as $optionId) {
            if (TikTokService::isValidPrivacyLevel($optionId)) {
                $options[] = $optionId;
            }
        }

        return new self(
            $data['creator_avatar_url'],
            $data['creator_nickname'],
            $data['creator_username'],
            (bool) $data['duet_disabled'],
            (bool) $data['stitch_disabled'],
            (bool) $data['comment_disabled'],
            (int) $data['max_video_post_duration_sec'],
            $options
        );
    }

    public function hasPrivacyOption(string $option): bool
    {
        return in_array($option, $this->getPrivacyOptions());
    }

    public function getAvatarUrl(): string
    {
        return $this->avatarUrl;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function isDuetOff(): bool
    {
        return $this->duetOff;
    }

    public function isStitchOff(): bool
    {
        return $this->stitchOff;
    }

    public function areCommentsOff(): bool
    {
        return $this->commentOff;
    }

    public function getCommentOff(): bool
    {
        return $this->commentOff;
    }

    public function getMaxVideoDuration(): int
    {
        return $this->maxVideoDurationSec;
    }

    public function getPrivacyOptions(): array
    {
        return $this->privacyOptions;
    }
}
