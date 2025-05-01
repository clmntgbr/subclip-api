<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[Route('', name: 'api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    #[Route('/token/invalidate', name: 'token_invalidate', methods: ['GET'])]
    public function tokenInvalidate(Request $request): JsonResponse
    {
        $this->eventDispatcher->dispatch(new LogoutEvent($request, $this->tokenStorage->getToken()));
        return new JsonResponse([], Response::HTTP_OK);
    }

    #[Route('/token/invalidate/{userId}', name: 'token_invalidate_user', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminInvalidateUserToken(string $userId, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneBy(['id' => $userId]);
        
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        $token = new UsernamePasswordToken($user, 'api', $user->getRoles());
        $this->eventDispatcher->dispatch(new LogoutEvent(new Request(), $token));
        
        return new JsonResponse([], Response::HTTP_OK);
    }
}
