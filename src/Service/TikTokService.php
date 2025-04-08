<?php

namespace App\Service;

use App\Exception\TikTokException;
use App\Model\TikTok\TokenTikTok;
use App\Model\TikTok\UserTikTok;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TikTokService
{
    public const BASE_V2 = 'https://open.tiktokapis.com/v2/';
    public const BASE_REDIRECT_URL = 'https://www.tiktok.com/v2/auth/authorize/?client_key=%s&scope=%s&response_type=code&redirect_uri=%s&state=%s';
    public const BASE_AUTH_URL = self::BASE_V2.'oauth/token/';
    public const BASE_USER_URL = self::BASE_V2.'user/info/?fields=%s';

    public const FIELD_U_OPENID = 'open_id';
    public const FIELD_U_UNIONID = 'union_id';
    public const FIELD_U_AVATAR = 'avatar_url';
    public const FIELD_U_AVATAR_THUMB = 'avatar_url_100';
    public const FIELD_U_AVATAR_LARGER = 'avatar_large_url';
    public const FIELD_U_DISPLAYNAME = 'display_name';
    public const FIELD_U_BIO = 'bio_description';
    public const FIELD_U_URL = 'profile_deep_link';
    public const FIELD_U_ISVERIFIED = 'is_verified';
    public const FIELD_U_FOLLOWERS = 'follower_count';
    public const FIELD_U_FOLLOWING = 'following_count';
    public const FIELD_U_LIKES = 'likes_count';
    public const FIELD_U_NUMVIDEOS = 'video_count';
    public const FIELDS_U_ALL = [self::FIELD_U_OPENID, self::FIELD_U_UNIONID, self::FIELD_U_AVATAR, self::FIELD_U_AVATAR_THUMB, self::FIELD_U_AVATAR_LARGER, self::FIELD_U_DISPLAYNAME, self::FIELD_U_BIO, self::FIELD_U_URL, self::FIELD_U_ISVERIFIED, self::FIELD_U_FOLLOWERS, self::FIELD_U_FOLLOWING, self::FIELD_U_LIKES, self::FIELD_U_NUMVIDEOS];

    public function __construct(
        private string $tiktokClientKey,
        private string $tiktokClientSecret,
        private string $tiktokRedirectUri,
        private HttpClientInterface $httpClient,
    ) {
    }

    public function getLoginUrl(string $state): string
    {
        return sprintf(self::BASE_REDIRECT_URL, $this->tiktokClientKey, 'user.info.basic,video.publish,video.upload', urlencode($this->tiktokRedirectUri), $state);
    }

    public function getToken(string $code): TokenTikTok
    {
        $data = [
            'client_key' => $this->tiktokClientKey,
            'client_secret' => $this->tiktokClientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->tiktokRedirectUri,
        ];

        try {
            $response = $this->httpClient->request('POST', self::BASE_AUTH_URL, [
                'body' => $data,
            ]);

            $token = TokenTikTok::fromJson($response->toArray());

            if ($token && $token->getAccessToken()) {
                return $token;
            }
        } catch (\Exception $_) {
        }

        throw new TikTokException('TikTok API Error');
    }

    public function getUserInfo(string $accessToken): UserTikTok
    {
        $url = sprintf(self::BASE_USER_URL, implode(',', [self::FIELD_U_OPENID, self::FIELD_U_UNIONID, self::FIELD_U_AVATAR, self::FIELD_U_DISPLAYNAME]));

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                ],
            ]);

            $user = UserTikTok::fromJson($response->toArray());

            return $user;
        } catch (\Exception $_) {
        }

        throw new TikTokException('TikTok API Error');
    }
}
