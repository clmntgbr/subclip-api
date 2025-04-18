<?php

namespace App\ApiResource;

use App\Service\UploadVideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

#[AsController]
class UploadVideoAction extends AbstractController
{
    public function __construct(
        private UploadVideoService $uploadVideoService,
    ) {
    }

    public function __invoke(#[MapUploadedFile] UploadedFile $video): JsonResponse
    {
        return $this->uploadVideoService->upload($video);
    }
}
