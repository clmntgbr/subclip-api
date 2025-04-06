<?php

namespace App\Controller\Social;

use App\Dto\GetToken;
use App\Entity\User;
use App\Repository\UserRepository;
use App\UseCase\Command\UpdateApiKey;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tiktok', name: 'api_tiktok_')]
class TikTokLoginController extends AbstractController
{
    public function __construct(
    ) {
    }

    #[Route('/login', name: 'login', methods: ['GET'])]
    public function login()
    {
        
    }
}
