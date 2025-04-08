<?php

namespace App\Serializer;

use App\Entity\ValueObject\AccessToken;
use App\Entity\ValueObject\Email;
use App\Entity\ValueObject\Firstname;
use App\Entity\ValueObject\Lastname;
use App\Entity\ValueObject\RefreshToken;
use App\Entity\ValueObject\SociaAccountId;
use App\Entity\ValueObject\SocialAccountType;
use App\Entity\ValueObject\Username;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ValueObjectNormalizer implements NormalizerInterface
{
    public function getSupportedTypes(?string $format): array
    {
        return [
            AccessToken::class => true,
            RefreshToken::class => true,
            Email::class => true,
            SociaAccountId::class => true,
            Username::class => true,
            SocialAccountType::class => true,
            Firstname::class => true,
            Lastname::class => true,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AccessToken
            || $data instanceof RefreshToken
            || $data instanceof Email
            || $data instanceof SociaAccountId
            || $data instanceof Username
            || $data instanceof SocialAccountType
            || $data instanceof Firstname
            || $data instanceof Lastname;
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return (string) $data;
    }
}
