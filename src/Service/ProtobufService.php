<?php

namespace App\Service;

use App\Entity\Clip;
use App\Entity\Video;
use App\Protobuf\Clip as ProtobufClip;
use App\Protobuf\Video as ProtobufVideo;

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
