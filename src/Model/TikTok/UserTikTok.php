<?php

namespace App\Model\TikTok;

class UserTikTok
{
    private ?string $openId;
    private ?string $unionId;
    private ?string $avatar;
    private ?string $avatarThumb;
    private ?string $avatarLarger;
    private ?string $displayName;
    private ?string $bio;
    private ?string $url;
    private bool $isVerified;
    private int $followers;
    private int $following;
    private int $likes;
    private int $numVideos;
    private ?string $handle;

    public function __construct(?string $openId, ?string $unionId, ?string $avatar, ?string $avatarLarger, ?string $displayName, ?string $avatarThumb, ?string $bio, ?string $url, bool $isVerified, int $followers, int $following, int $likes, int $numVideos, ?string $handle)
    {
        $this->openId = $openId;
        $this->unionId = $unionId;
        $this->avatar = $avatar;
        $this->avatarLarger = $avatarLarger;
        $this->displayName = $displayName;
        $this->avatarThumb = $avatarThumb;
        $this->bio = $bio;
        $this->url = $url;
        $this->isVerified = $isVerified;
        $this->followers = $followers;
        $this->following = $following;
        $this->likes = $likes;
        $this->numVideos = $numVideos;
        $this->handle = $handle;
    }

    public static function fromJson(array $json, bool $getUsernameRemote = false)
    {
        $data = $json['data']['user'] ?? [];
        $openId = $data['open_id'] ?? null;
        $unionId = $data['union_id'] ?? null;
        $avatar = $data['avatar_url'] ?? null;
        $avatarLarger = $data['avatar_larger'] ?? null;
        $displayName = $data['display_name'] ?? null;
        $avatarThumb = $data['avatar_url_100'] ?? null;
        $bio = $data['bio_description'] ?? null;
        $url = $data['profile_deep_link'] ?? null;
        $isVerified = !empty($data['is_verified']);
        $followers = isset($data['follower_count']) ? (int) $data['follower_count'] : 0;
        $following = isset($data['following_count']) ? (int) $data['following_count'] : 0;
        $likes = isset($data['likes_count']) ? (int) $data['likes_count'] : 0;
        $numVideos = isset($data['video_count']) ? (int) $data['video_count'] : 0;
        $handle = null;

        if ($getUsernameRemote && $url) {
            $profileUrl = self::getProfileUrl($url);
            $handle = self::parseHandleFromUrl($profileUrl);
        }

        return new self(
            openId: $openId,
            unionId: $unionId,
            avatar: $avatar,
            avatarLarger: $avatarLarger,
            displayName: $displayName,
            avatarThumb: $avatarThumb,
            bio: $bio,
            url: $url,
            isVerified: $isVerified,
            followers: $followers,
            following: $following,
            likes: $likes,
            numVideos: $numVideos,
            handle: $handle
        );
    }

    public function getOpenID(): ?string
    {
        return $this->openId;
    }

    public function getUnionID(): ?string
    {
        return $this->unionId;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function getAvatarLarger(): ?string
    {
        return $this->avatarLarger;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getAvatarThumb(): ?string
    {
        return $this->avatarThumb;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function getFollowers(): int
    {
        return $this->followers;
    }

    public function getFollowing(): int
    {
        return $this->following;
    }

    public function getLikes(): int
    {
        return $this->likes;
    }

    public function getNumVideos(): int
    {
        return $this->numVideos;
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function getBestAvatar(): ?string
    {
        if ($this->getAvatarLarger()) {
            return $this->getAvatarLarger();
        }
        if ($this->getAvatar()) {
            return $this->getAvatar();
        }
        if ($this->getAvatarThumb()) {
            return $this->getAvatarThumb();
        }

        return null;
    }

    private static function getProfileUrl(?string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_exec($ch);
        $redirectURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return $redirectURL;
    }

    private static function parseHandleFromUrl(?string $url): ?string
    {
        preg_match('@www.tiktok.com%2F%40([^%]+)@', $url, $m);
        if ($m && !empty($m[1])) {
            return trim($m[1]);
        }
        preg_match('@www.tiktok.com/\@([^\?]+)@', $url, $m);
        if ($m && !empty($m[1])) {
            return trim($m[1]);
        }

        return null;
    }
}
