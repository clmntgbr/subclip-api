<?php

namespace App\Controller;

use App\Dto\GetToken;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api', name: 'api_')]
class SecurityController extends AbstractController
{
    public function __construct(
      private UserPasswordHasherInterface $userPasswordHasher,
      private UserRepository $userRepository,
    ) {
    }

    #[Route('/token', name: 'token', methods: ['GET'])]
    public function token(#[MapRequestPayload()] GetToken $getToken): JsonResponse
    {
      $user = $this->userRepository->findOneBy(['email.value' => $getToken->email]);
      
      if (null === $user) {
        return new JsonResponse(data: ['message' => 'User not found'], status: Response::HTTP_UNAUTHORIZED);
      }
      
      if (!$this->userPasswordHasher->isPasswordValid($user, $getToken->password)) {
        return new JsonResponse(data: ['message' => 'Password is not valid'], status: Response::HTTP_UNAUTHORIZED);
      }

      $plainToken = bin2hex(random_bytes(32));
      $this->userRepository->updateToken($user, $plainToken);

      return new JsonResponse(data: ['token' => $plainToken], status: Response::HTTP_OK);
    }
}