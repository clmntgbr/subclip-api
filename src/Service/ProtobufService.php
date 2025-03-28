<?php

namespace App\Service;

use App\Entity\Clip;
use App\Entity\User;
use App\Entity\Video;
use App\Message\CreateClip;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use App\Protobuf\Clip as ProtobufClip;
use App\Protobuf\Video as ProtobufVideo;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProtobufService
{
    public function __construct(
    ) {
    }

    public function getProtobufClip(Clip $clip): ProtobufClip
    {
        $protobufClip = $this->transformClipToProtobuf($clip);
        $protobufOriginalVideo = $this->transformVideoToProtobuf($clip->getOriginalVideo());

        $protobufClip->setOriginalVideo($protobufOriginalVideo);

        return $protobufClip;
    }

    private function transformClipToProtobuf(Clip $clip): ProtobufClip
    {
        $protobuf = new ProtobufClip();

        $protobuf->setId($clip->getId()->__toString());
        $protobuf->setUserId($clip->getUser()->getId()->__toString());
        $protobuf->setStatus($clip->getStatusToString());

        return $protobuf;
    }

    private function transformVideoToProtobuf(Video $video): ProtobufVideo
    {
        $protobuf = new ProtobufVideo();

        if ($video->getName()) {
            $protobuf->setName($video->getName());
        }

        if ($video->getId()) {
            $protobuf->setId($video->getId()->__toString());
        }

        if ($video->getMimeType()) {
            $protobuf->setMimeType($video->getMimeType());
        }

        if ($video->getSize()) {
            $protobuf->setSize($video->getSize());
        }

        return $protobuf;
    }
}
