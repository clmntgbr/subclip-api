<?php

namespace App\Model\TikTok;

class TokenTikTok
{
    private string $accessToken;
    private string $refreshToken;
    private int $expiresIn;
    private string $openId;
    private int $refreshExpiresIn;
    private array $scope;
    private string $tokenType;

    public function __construct(string $accessToken, string $refreshToken, int $expiresIn, string $openId, int $refreshExpiresIn, array $scope, string $tokenType)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresIn = $expiresIn;
        $this->openId = $openId;
        $this->refreshExpiresIn = $refreshExpiresIn;
        $this->scope = $scope;
        $this->tokenType = $tokenType;
    }

    /**
     * @return TokenTikTok
     *
     * @throws \Exception
     */
    public static function fromJson(array $json)
    {
        if (empty($json['access_token']) || empty($json['open_id'])) {
            throw new \Exception('Invalid TikTok JSON: '.var_export($json, true));
        }

        $accessToken = $json['access_token'];
        $openId = $json['open_id'];
        $refreshToken = $json['refresh_token'] ?? '';
        $expiresIn = isset($json['expires_in']) ? (int) $json['expires_in'] : 0;
        $refreshExpiresIn = isset($json['refresh_expires_in']) ? (int) $json['refresh_expires_in'] : 0;
        $scope = !empty($json['scope']) ? explode(',', $json['scope']) : [];
        $tokenType = $json['token_type'] ?? 'Bearer';

        return new self(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $expiresIn,
            openId: $openId,
            refreshExpiresIn: $refreshExpiresIn,
            scope: $scope,
            tokenType: $tokenType
        );
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getScope(): array
    {
        return $this->scope;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getRefreshExpiresIn(): int
    {
        return $this->refreshExpiresIn;
    }
}
