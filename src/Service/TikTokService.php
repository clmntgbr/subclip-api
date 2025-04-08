<?php

namespace App\Service;

use App\Exception\TikTokException;
use App\Exception\UploadTikTokClipException;
use App\Model\TikTok\CreatorQueryTikTok;
use App\Model\TikTok\TokenTikTok;
use App\Model\TikTok\UserTikTok;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TikTokService
{
    public const BASE_V2 = 'https://open.tiktokapis.com/v2/';
    public const BASE_REDIRECT_URL = 'https://www.tiktok.com/v2/auth/authorize/?client_key=%s&scope=%s&response_type=code&redirect_uri=%s&state=%s';
    public const BASE_AUTH_URL = self::BASE_V2.'oauth/token/';
    public const BASE_USER_URL = self::BASE_V2.'user/info/?fields=%s';
    public const BASE_CREATOR_QUERY = self::BASE_V2.'post/publish/creator_info/query/';
	public const BASE_POST_PUBLISH = self::BASE_V2.'post/publish/video/init/';

    public const FIELD_U_OPENID = 'open_id';
    public const FIELD_U_UNIONID = 'union_id';
    public const FIELD_U_AVATAR = 'avatar_url';
    public const FIELD_U_DISPLAYNAME = 'display_name';

    public const PRIVACY_PUBLIC = 'PUBLIC_TO_EVERYONE';
    public const PRIVACY_FRIENDS = 'MUTUAL_FOLLOW_FRIENDS';
    public const PRIVACY_FOLLOWERS = 'FOLLOWER_OF_CREATOR';
    public const PRIVACY_PRIVATE = 'SELF_ONLY';
    public const VALID_PRIVACY = [self::PRIVACY_PUBLIC, self::PRIVACY_FRIENDS, self::PRIVACY_FOLLOWERS, self::PRIVACY_PRIVATE];

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

    public static function isValidPrivacyLevel(string $privacyLevel)
    {
        if (in_array($privacyLevel, self::VALID_PRIVACY)) {
            return true;
        }

        return false;
    }

    public function refreshToken(string $refreshToken): TokenTikTok
    {
        $data = [
            'client_key' => $this->tiktokClientKey,
            'client_secret' => $this->tiktokClientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
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

    public function getCreatorInfo(string $accessToken): CreatorQueryTikTok
    {
        try {
            $response = $this->httpClient->request('POST', self::BASE_CREATOR_QUERY, [
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                ],
            ]);

            $creatorQuery = CreatorQueryTikTok::fromJson($response->toArray());

            return $creatorQuery;
        } catch (\Exception $_) {
        }

        throw new TikTokException('TikTok API Error');
    }

    public function publish(string $accessToken, string $file): void
    {
        if (!file_exists($file)) {
			throw new UploadTikTokClipException('TikTok file to be uploaded doesn\'t exist: '.$file);
		}

        $data = [
            'post_info' => [
                'title' => Uuid::v4()->toRfc4122(),
                'privacy_level' => self::PRIVACY_PUBLIC,
                'disable_comment' => false,
                'disable_duet' => false,
                'disable_stitch' => false,
                'video_cover_timestamp_ms' => 1000,
                'brand_content_toggle' => false,
                'brand_organic_toggle' => false,
            ],
            'source_info' => [
                "source" => "FILE_UPLOAD",
                "video_size" => filesize($file),
                "chunk_size" => filesize($file),
                "total_chunk_count" => 1
            ]
        ];

        try {
            $response = $this->httpClient->request('POST', self::BASE_POST_PUBLISH, [
                'headers' => [
                    'Authorization' => 'Bearer '.$accessToken,
                ],
                'body' => $data,
            ]);

            dd($response->toArray());
        } catch (\Exception $_) {
        }

        throw new TikTokException('TikTok API Error');
    }
}
