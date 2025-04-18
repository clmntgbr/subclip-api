<?php

namespace App\Service;

use App\Entity\SocialAccount;
use App\Entity\ValueObject\AccessToken;
use App\Exception\TikTokException;
use App\Exception\UploadTikTokClipException;
use App\Model\TikTok\CreatorQueryTikTok;
use App\Model\TikTok\PublishInfoTikTok;
use App\Model\TikTok\PublishStatusTikTok;
use App\Model\TikTok\TokenTikTok;
use App\Model\TikTok\UserTikTok;
use App\Repository\SocialAccountRepository;
use App\UseCase\Command\UpdateTikTokToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class TikTokService
{
    public const BASE_V2 = 'https://open.tiktokapis.com/v2/';
    public const BASE_REDIRECT_URL = 'https://www.tiktok.com/v2/auth/authorize/?client_key=%s&scope=%s&response_type=code&redirect_uri=%s&state=%s';
    public const BASE_AUTH_URL = self::BASE_V2.'oauth/token/';
    public const BASE_USER_URL = self::BASE_V2.'user/info/?fields=%s';
    public const BASE_CREATOR_QUERY = self::BASE_V2.'post/publish/creator_info/query/';
    public const BASE_POST_PUBLISH = self::BASE_V2.'post/publish/video/init/';
    public const BASE_PUBLISH_STATUS = self::BASE_V2.'post/publish/status/fetch/';

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
        private string $apiUrl,
        private string $tiktokClientKey,
        private string $tiktokClientSecret,
        private string $tiktokRedirectUri,
        private MessageBusInterface $messageBus,
        private SocialAccountRepository $socialAccountRepository,
        private LoggerInterface $logger,
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
            $response = $this->post(self::BASE_AUTH_URL, $data);
            $token = TokenTikTok::fromJson($response);

            if ($token && $token->getAccessToken()) {
                return $token;
            }
        } catch (\Exception $_) {
            throw new TikTokException('TikTok API Error');
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
            $response = $this->post(self::BASE_AUTH_URL, $data);
            $token = TokenTikTok::fromJson($response);

            if ($token && $token->getAccessToken()) {
                return $token;
            }
        } catch (\Exception $_) {
        }

        throw new TikTokException('TikTok API Error on refreshToken');
    }

    public function getUserInfo(SocialAccount $socialAccount): UserTikTok
    {
        $url = sprintf(self::BASE_USER_URL, implode(',', [self::FIELD_U_OPENID, self::FIELD_U_UNIONID, self::FIELD_U_AVATAR, self::FIELD_U_DISPLAYNAME]));

        try {
            $response = $this->getWithAuth($socialAccount, $url);
            $user = UserTikTok::fromJson($response);

            return $user;
        } catch (\Exception $_) {
            throw new TikTokException(sprintf('[%s]: TikTok API Error : %s', __METHOD__, $_->getMessage()));
        }
    }

    public function getCreatorInfo(SocialAccount $socialAccount): CreatorQueryTikTok
    {
        try {
            $response = $this->postWithAuth($socialAccount, self::BASE_CREATOR_QUERY, []);
            $creatorQuery = CreatorQueryTikTok::fromJson($response);

            return $creatorQuery;
        } catch (\Exception $_) {
            throw new TikTokException(sprintf('[%s]: TikTok API Error : %s', __METHOD__, $_->getMessage()));
        }
    }

    public function getPublishStatus(SocialAccount $socialAccount, ?string $publishId): PublishStatusTikTok
    {
        try {
            $response = $this->postWithAuth($socialAccount, self::BASE_PUBLISH_STATUS, ['publish_id' => $publishId]);
            $publishStatusTikTok = PublishStatusTikTok::fromJson($response);

            return $publishStatusTikTok;
        } catch (\Exception $exception) {
            throw new UploadTikTokClipException(username: $socialAccount->getUsername(), message: $exception->getMessage(), errorCode: $exception->getCode());
        }
    }

    public function publish(
        SocialAccount $socialAccount,
        string $file,
        bool $areCommentsOff = false,
        bool $isDuetOff = false,
        bool $isStitchOff = false,
    ): PublishInfoTikTok {
        $data = [
            'post_info' => [
                'title' => Uuid::v4()->toRfc4122(),
                'privacy_level' => self::PRIVACY_PRIVATE,
                'disable_comment' => $areCommentsOff,
                'disable_duet' => $isDuetOff,
                'disable_stitch' => $isStitchOff,
                'video_cover_timestamp_ms' => 1000,
                'brand_content_toggle' => false,
                'brand_organic_toggle' => false,
            ],
            'source_info' => [
                'source' => 'PULL_FROM_URL',
                'video_url' => sprintf('%s%s', $this->apiUrl, $file),
            ],
        ];

        try {
            $response = $this->postWithAuth($socialAccount, self::BASE_POST_PUBLISH, $data);
            $publishInfoTikTok = PublishInfoTikTok::fromJson($response);

            return $publishInfoTikTok;
        } catch (\Exception $exception) {
            throw new TikTokException(sprintf('[SocialAccountUsername(%s)][Method(%s)][Code(%s)] %s', $socialAccount->getUsername(), __METHOD__, $exception->getCode(), $exception->getMessage()));
        }
    }

    public function postWithAuth(SocialAccount $socialAccount, string $url, array $data): array
    {
        $accessToken = $this->checkToken($socialAccount);

        $headers = [
            'Authorization: Bearer '.$accessToken->__toString(),
        ];

        return self::post($url, $data, $headers, true);
    }

    private static function post(string $url, array $data, array $headers = [], bool $is_json = false): array
    {
        $curl = curl_init();
        $headers[] = 'Cache-Control: no-cache';
        $post = http_build_query($data);
        if ($is_json) {
            $post = json_encode($data);
            if (!$data) {
                $post = '';
            }
            $headers[] = 'Content-Type: application/json';
        } else {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $post,
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new TikTokException('Invalid response '.$error);
        }

        if (!$response) {
            throw new TikTokException('Invalid returned value '.var_export($response, 1));
        }

        $response = json_decode($response, true);

        if (!$response) {
            throw new TikTokException('Invalid JSON '.$response);
        }

        return $response;
    }

    private function getWithAuth(SocialAccount $socialAccount, string $url): array
    {
        $accessToken = $this->checkToken($socialAccount);

        $headers = [
            'Authorization: Bearer '.$accessToken->__toString(),
        ];

        return self::get($url, $headers);
    }

    private static function get(string $url, array $headers = []): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new TikTokException('Invalid response '.$error);
        }

        if (!$response) {
            throw new TikTokException('Invalid returned value '.var_export($response, 1));
        }

        $response = json_decode($response, true);

        if (!$response) {
            throw new TikTokException('Invalid JSON '.$response);
        }

        return $response;
    }

    private function checkToken(SocialAccount $socialAccount): AccessToken
    {
        if ($socialAccount->getExpireAt() < new \DateTimeImmutable() && $socialAccount->getRefreshExpireAt() < new \DateTimeImmutable()) {
            throw new TikTokException('Tokens are expired');
        }

        if ($socialAccount->getExpireAt() < new \DateTimeImmutable()) {
            $this->messageBus->dispatch(new UpdateTikTokToken($socialAccount->getId()));
            /** @var SocialAccount $socialAccount */
            $socialAccount = $this->socialAccountRepository->refresh($socialAccount);
        }

        return $socialAccount->getAccessToken();
    }
}
