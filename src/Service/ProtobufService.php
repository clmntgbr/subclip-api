<?php

namespace App\Service;

use App\Entity\Clip;
use App\Entity\Video;
use App\Protobuf\Clip as ProtobufClip;
use App\Protobuf\Video as ProtobufVideo;
use App\Repository\ClipRepository;
use App\Repository\UserRepository;

class ProtobufService
{
    public function __construct(
        private ClipRepository $clipRepository,
        private UserRepository $userRepository,
    ) {
    }

    public function getProtobufClip(Clip $clip): ProtobufClip
    {
        $protobufClip = $this->transformClipToProtobuf($clip);
        $protobufOriginalVideo = $this->transformVideoToProtobuf($clip->getOriginalVideo());

        $protobufClip->setOriginalVideo($protobufOriginalVideo);

        return $protobufClip;
    }

    public function getClip(ProtobufClip $protobuf): Clip
    {
        $user = $this->userRepository->findOneBy(['id' => $protobuf->getUserId()]);

        if (null === $user) {
            throw new \RuntimeException('User not found');
        }

        $clip = $this->transformProtobufToClip($protobuf);

        return $clip;
    }

    private function transformClipToProtobuf(Clip $clip): ProtobufClip
    {
        $protobuf = new ProtobufClip();

        $protobuf->setId($clip->getId()->__toString());
        $protobuf->setUserId($clip->getUser()->getId()->__toString());
        $protobuf->setStatus($clip->getStatus());

        if ($clip->getCover()) {
            $protobuf->setCover($clip->getCover());
        }

        return $protobuf;
    }

    private function transformProtobufToClip(ProtobufClip $protobufClip): Clip
    {
        /** @var ?Clip $clip */
        $clip = $this->clipRepository->findOneBy(['id' => $protobufClip->getId()]);

        if (null === $clip) {
            throw new \RuntimeException('User not found');
        }

        if ($clip->getCover()) {
            $clip->setCover($protobufClip->getCover());
        }

        $clip->setOriginalVideo(
            $this->transformProtobufToVideo(
                $clip->getOriginalVideo(),
                $protobufClip->getOriginalVideo()
            )
        );

        return $clip;
    }

    private function transformProtobufToVideo(?Video $video, ProtobufVideo $protobufVideo): Video
    {
        if (null === $video) {
            $video = new Video(
                $protobufVideo->getOriginalName(),
                $protobufVideo->getName(),
                $protobufVideo->getMimeType(),
                $protobufVideo->getSize(),
            );
        }

        if ($protobufVideo->getId()) {
            $video->setId($protobufVideo->getId());
        }

        if ($protobufVideo->getName()) {
            $video->setName($protobufVideo->getName());
        }

        if ($protobufVideo->getMimeType()) {
            $video->setMimeType($protobufVideo->getMimeType());
        }

        if ($protobufVideo->getAss()) {
            $video->setAss($protobufVideo->getAss());
        }

        if ($protobufVideo->getLength()) {
            $video->setLength($protobufVideo->getLength());
        }

        if ($protobufVideo->getSize()) {
            $video->setSize($protobufVideo->getSize());
        }

        if ($protobufVideo->getSubtitle()) {
            $video->setSubtitle($protobufVideo->getSubtitle());
        }

        if ($protobufVideo->getOriginalName()) {
            $video->setOriginalName($protobufVideo->getOriginalName());
        }

        if ($protobufVideo->getSubtitles()) {
            $video->setSubtitles([]);
            foreach ($protobufVideo->getSubtitles()->getIterator() as $iterator) {
                $video->addSubtitles($iterator);
            }
        }

        if ($protobufVideo->getAudios()) {
            $video->setAudios([]);
            foreach ($protobufVideo->getAudios()->getIterator() as $iterator) {
                $video->addAudios($iterator);
            }
        }

        return $video;
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

        if ($video->getOriginalName()) {
            $protobuf->setOriginalName($video->getOriginalName());
        }

        if ($video->getMimeType()) {
            $protobuf->setMimeType($video->getMimeType());
        }

        if ($video->getSize()) {
            $protobuf->setSize($video->getSize());
        }

        if ($video->getLength()) {
            $protobuf->setLength($video->getLength());
        }

        if ($video->getAss()) {
            $protobuf->setAss($video->getAss());
        }

        if ($video->getSubtitle()) {
            $protobuf->setSubtitle($video->getSubtitle());
        }

        if ($video->getSubtitles()) {
            $protobuf->setSubtitles($video->getSubtitles());
        }

        if ($video->getAudios()) {
            $protobuf->setAudios($video->getAudios());
        }

        return $protobuf;
    }
}
