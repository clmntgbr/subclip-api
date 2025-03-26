<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class StatusController extends AbstractController
{
    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse(data: ['status' => 'ok'], status: Response::HTTP_OK);
    }
}
