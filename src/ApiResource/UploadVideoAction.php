<?php

namespace App\ApiResource;

use App\Model\UploadVideoConfiguration;
use App\Service\UploadVideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class UploadVideoAction extends AbstractController
{
    public function __construct(
        private UploadVideoService $uploadVideoService,
        private SerializerInterface $serializer,
    ) {
    }

    public function __invoke(
        #[MapUploadedFile] UploadedFile $video,
        #[MapRequestPayload] ?UploadVideoConfiguration $configuration = new UploadVideoConfiguration(),
    ): JsonResponse {
        return $this->uploadVideoService->upload($video, $configuration);
    }
}
