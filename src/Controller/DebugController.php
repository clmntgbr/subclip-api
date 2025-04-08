<?php

namespace App\Controller;

use App\Entity\Clip;
use App\Entity\Configuration;
use App\Entity\User;
use App\Entity\Video;
use App\Protobuf\ClipStatus;
use App\Repository\ClipRepository;
use App\Repository\SocialAccountRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\UseCase\Command\UploadTikTokClip;
use League\Flysystem\FilesystemOperator;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

use const App\Entity\CLIP_READ;

#[Route('/api', name: 'api_')]
class DebugController extends AbstractController
{
    public function __construct(
        private string $rabbitMqTransportDsn,
        private UserRepository $userRepository,
        private VideoRepository $videoRepository,
        private ClipRepository $clipRepository,
        private readonly SerializerInterface $serializer,
        private FilesystemOperator $awsStorage,
        private SocialAccountRepository $socialAccountRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/tiktok', name: 'tiktok', methods: ['GET'])]
    public function tiktok(): JsonResponse
    {
        $clip = $this->clipRepository->findOneBy(['id' => '8e90c18c-da70-4e1b-8671-30ce14851cd2']);
        $socialAccount = $this->socialAccountRepository->findOneBy(['id' => 'f760f517-ed69-4a70-8487-ec5ba6b821fd']);

        $this->messageBus->dispatch(new UploadTikTokClip(
            clipId: $clip->getId(),
            socialAccountId: $socialAccount->getId(),
        ), [
            new AmqpStamp('async', 0, []),
        ]);

        dd($clip);
        return new JsonResponse(data: [
            'message' => 'Debug index',
        ], status: Response::HTTP_OK);
    }

    #[Route('/debug/{service}', name: 'debug', methods: ['GET'])]
    public function debug(#[CurrentUser] ?User $user, string $service): JsonResponse
    {
        $channel = $this->rabbitMqConnection();

        $video = $this->getOriginalVideo('87bad469-33bd-4c4d-8a83-08142581a31d');
        $clip = $this->getClip($user, '8e90c18c-da70-4e1b-8671-30ce14851cd2', $video);

        $this->sendToS3($user, $clip);

        $message = match ($service) {
            'sound_extractor' => $this->toSoundExtractor($clip),
            'subtitle_generator' => $this->toSubtitleGenerator($clip),
            'subtitle_merger' => $this->toSubtitleMerger($clip),
            'subtitle_transformer' => $this->toSubtitleTransformer($clip),
            'video_formatter' => $this->toVideoFormatter($clip),
            'subtitle_incrustator' => $this->toSubtitleIncrustator($clip),
            default => throw new \Exception('This service does not exist.'),
        };

        $channel->basic_publish($message, 'microservices', $service);

        $this->clipRepository->save($clip);

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups([CLIP_READ])
            ->toArray();

        return new JsonResponse(data: $this->serializer->serialize($clip, 'json', $context), status: Response::HTTP_OK, json: true);
    }

    private function rabbitMqConnection(): AMQPChannel
    {
        $parsedDsn = parse_url($this->rabbitMqTransportDsn);
        $user = $parsedDsn['user'] ?? 'guest';
        $password = $parsedDsn['pass'] ?? 'guest';
        $host = $parsedDsn['host'] ?? 'localhost';
        $port = $parsedDsn['port'] ?? 5672;
        $vhost = ltrim($parsedDsn['path'] ?? '/', '/') ?: '/';

        $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $channel = $connection->channel();
        $channel->exchange_declare('microservices', 'direct', false, true, false);

        return $channel;
    }

    private function getOriginalVideo(string $id): Video
    {
        $video = $this->videoRepository->findOneBy(['id' => $id]);

        if ($video instanceof Video) {
            return $video;
        }

        $video = new Video(
            videoId: Uuid::fromString($id),
            originalName: 'video.mp4',
            name: '8e90c18c-da70-4e1b-8671-30ce14851cd2.mp4',
            mimeType: 'video/mp4',
            size: 71541180
        );

        $video->setAss('8e90c18c-da70-4e1b-8671-30ce14851cd2.ass');
        $video->setSubtitle('8e90c18c-da70-4e1b-8671-30ce14851cd2.srt');

        $video->setId($id);

        return $video;
    }

    private function getClip(?User $user, string $id, Video $video)
    {
        $clip = $this->clipRepository->findOneBy(['id' => $id]);

        if ($clip instanceof Clip) {
            return $clip;
        }

        $clip = new Clip(
            user: $user,
            clipId: Uuid::fromString($id),
            originalVideo: $video,
            configuration: new Configuration(),
        );

        return $clip;
    }

    private function sendToS3(User $user, Clip $clip): void
    {
        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2.mp4');
        $stream = fopen('/app/public/debug/8e90c18c-da70-4e1b-8671-30ce14851cd2.mp4', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        // Audios

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.wav');
        $stream = fopen('/app/public/debug/audios/8e90c18c-da70-4e1b-8671-30ce14851cd2_1.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.wav');
        $stream = fopen('/app/public/debug/audios/8e90c18c-da70-4e1b-8671-30ce14851cd2_2.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.wav');
        $stream = fopen('/app/public/debug/audios/8e90c18c-da70-4e1b-8671-30ce14851cd2_3.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.wav');
        $stream = fopen('/app/public/debug/audios/8e90c18c-da70-4e1b-8671-30ce14851cd2_4.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.wav');
        $stream = fopen('/app/public/debug/audios/8e90c18c-da70-4e1b-8671-30ce14851cd2_5.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        // Subtitles

        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2.srt');
        $stream = fopen('/app/public/debug/8e90c18c-da70-4e1b-8671-30ce14851cd2.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2.ass');
        $stream = fopen('/app/public/debug/8e90c18c-da70-4e1b-8671-30ce14851cd2.ass', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.srt');
        $stream = fopen('/app/public/debug/subtitles/8e90c18c-da70-4e1b-8671-30ce14851cd2_1.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.srt');
        $stream = fopen('/app/public/debug/subtitles/8e90c18c-da70-4e1b-8671-30ce14851cd2_2.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.srt');
        $stream = fopen('/app/public/debug/subtitles/8e90c18c-da70-4e1b-8671-30ce14851cd2_3.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.srt');
        $stream = fopen('/app/public/debug/subtitles/8e90c18c-da70-4e1b-8671-30ce14851cd2_4.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.srt');
        $stream = fopen('/app/public/debug/subtitles/8e90c18c-da70-4e1b-8671-30ce14851cd2_5.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.srt');
        $stream = fopen('/app/public/debug/subtitles/8e90c18c-da70-4e1b-8671-30ce14851cd2_5.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);
    }

    private function toSoundExtractor(Clip $clip): AMQPMessage
    {
        $messageData = json_encode([
            'task' => 'tasks.process_message',
            'args' => [$this->getJsonSoundExtractor()],
            'queue' => 'sound_extractor',
        ]);

        $message = new AMQPMessage($messageData,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'headers' => [
                    'type' => 'sound_extractor',
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        $clip->setProcessedVideo(null);
        $clip->setStatuses([ClipStatus::name(ClipStatus::UPLOADED)]);
        $clip->setStatus(ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_PENDING));

        return $message;
    }

    private function toSubtitleGenerator(Clip $clip): AMQPMessage
    {
        $messageData = json_encode([
            'task' => 'tasks.process_message',
            'args' => [$this->getJsonSubtitleGenerator()],
            'queue' => 'subtitle_generator',
        ]);

        $message = new AMQPMessage($messageData,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'headers' => [
                    'type' => 'subtitle_generator',
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        $clip->setProcessedVideo(null);
        $clip->setStatuses([
            ClipStatus::name(ClipStatus::UPLOADED),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_PENDING),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_COMPLETE),
        ]);
        $clip->setStatus(ClipStatus::name(ClipStatus::SUBTITLE_GENERATOR_PENDING));

        return $message;
    }

    private function toSubtitleMerger(Clip $clip): AMQPMessage
    {
        $messageData = json_encode([
            'task' => 'tasks.process_message',
            'args' => [$this->getJsonSubtitleMerger()],
            'queue' => 'subtitle_merger',
        ]);

        $message = new AMQPMessage($messageData,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'headers' => [
                    'type' => 'subtitle_merger',
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        $clip->setProcessedVideo(null);
        $clip->setStatuses([
            ClipStatus::name(ClipStatus::UPLOADED),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_PENDING),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_COMPLETE),
            ClipStatus::name(ClipStatus::SUBTITLE_GENERATOR_PENDING),
            ClipStatus::name(ClipStatus::SUBTITLE_GENERATOR_COMPLETE),
        ]);
        $clip->setStatus(ClipStatus::name(ClipStatus::SUBTITLE_MERGER_PENDING));

        return $message;
    }

    private function toSubtitleTransformer(Clip $clip): AMQPMessage
    {
        $messageData = json_encode([
            'task' => 'tasks.process_message',
            'args' => [$this->getJsonSubtitleTransformer()],
            'queue' => 'subtitle_transformer',
        ]);

        $message = new AMQPMessage($messageData,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'headers' => [
                    'type' => 'subtitle_transformer',
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        $clip->setProcessedVideo(null);
        $clip->setStatuses([
            ClipStatus::name(ClipStatus::UPLOADED),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_PENDING),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_COMPLETE),
            ClipStatus::name(ClipStatus::SUBTITLE_GENERATOR_PENDING),
            ClipStatus::name(ClipStatus::SUBTITLE_GENERATOR_COMPLETE),
            ClipStatus::name(ClipStatus::SUBTITLE_MERGER_PENDING),
            ClipStatus::name(ClipStatus::SUBTITLE_MERGER_COMPLETE),
        ]);
        $clip->setStatus(ClipStatus::name(ClipStatus::SUBTITLE_TRANSFORMER_PENDING));

        return $message;
    }

    private function toVideoFormatter(Clip $clip): AMQPMessage
    {
        $messageData = json_encode([
            'task' => 'tasks.process_message',
            'args' => [$this->getJsonVideoFormatter()],
            'queue' => 'video_formatter',
        ]);

        $message = new AMQPMessage($messageData,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'headers' => [
                    'type' => 'video_formatter',
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        $processedVideo = $clip->getProcessedVideo();

        if (null === $processedVideo) {
            $processedVideo = new Video(
                videoId: Uuid::fromString('fe6c8eed-f435-4b00-b9dd-3bf97e9e4eee'),
                originalName: $clip->getOriginalVideo()->getOriginalName(),
                name: $clip->getOriginalVideo()->getName(),
                mimeType: $clip->getOriginalVideo()->getMimeType(),
                size: $clip->getOriginalVideo()->getSize(),
            );
        }

        $processedVideo->setAss($clip->getOriginalVideo()->getAss());
        $processedVideo->setSubtitle($clip->getOriginalVideo()->getSubtitle());
        $clip->setProcessedVideo($processedVideo);

        $clip->setStatuses([
            ClipStatus::name(ClipStatus::UPLOADED),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_PENDING),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_COMPLETE),
            ClipStatus::name(ClipStatus::SUBTITLE_GENERATOR_PENDING),
            ClipStatus::name(ClipStatus::SUBTITLE_GENERATOR_COMPLETE),
            ClipStatus::name(ClipStatus::SUBTITLE_MERGER_PENDING),
            ClipStatus::name(ClipStatus::SUBTITLE_MERGER_COMPLETE),
            ClipStatus::name(ClipStatus::SUBTITLE_TRANSFORMER_PENDING),
            ClipStatus::name(ClipStatus::SUBTITLE_TRANSFORMER_COMPLETE),
        ]);
        $clip->setStatus(ClipStatus::name(ClipStatus::VIDEO_FORMATTER_PENDING));

        return $message;
    }

    private function toSubtitleIncrustator(Clip $clip): AMQPMessage
    {
        $messageData = json_encode([
            'task' => 'tasks.process_message',
            'args' => [$this->getJsonSubtitleIncrustator()],
            'queue' => 'subtitle_incrustator',
        ]);

        $message = new AMQPMessage($messageData,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'headers' => [
                    'type' => 'subtitle_incrustator',
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        $path = sprintf('%s/%s/%s', $clip->getUser()->getId(), $clip->getId(), '8e90c18c-da70-4e1b-8671-30ce14851cd2_processed.mp4');
        $stream = fopen('/app/public/debug/8e90c18c-da70-4e1b-8671-30ce14851cd2_subtitle_incrustator.mp4', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $clip->setProcessedVideo(null);
        $clip->setStatuses([
            ClipStatus::name(ClipStatus::UPLOADED),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_PENDING),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_COMPLETE),
            ClipStatus::name(ClipStatus::SUBTITLE_GENERATOR_PENDING),
            ClipStatus::name(ClipStatus::SUBTITLE_GENERATOR_COMPLETE),
            ClipStatus::name(ClipStatus::SUBTITLE_MERGER_PENDING),
            ClipStatus::name(ClipStatus::SUBTITLE_MERGER_COMPLETE),
            ClipStatus::name(ClipStatus::SUBTITLE_TRANSFORMER_PENDING),
            ClipStatus::name(ClipStatus::SUBTITLE_TRANSFORMER_COMPLETE),
            ClipStatus::name(ClipStatus::VIDEO_FORMATTER_PENDING),
            ClipStatus::name(ClipStatus::VIDEO_FORMATTER_COMPLETE),
        ]);
        $clip->setStatus(ClipStatus::name(ClipStatus::SUBTITLE_INCRUSTATOR_PENDING));

        return $message;
    }

    private function getJsonSoundExtractor(): string
    {
        return json_encode([
            'clip' => [
                'id' => '8e90c18c-da70-4e1b-8671-30ce14851cd2',
                'userId' => 'da59434f-602f-4d39-879c-eb0950812737',
                'status' => 'SOUND_EXTRACTOR_PENDING',
                'originalVideo' => [
                    'id' => '87bad469-33bd-4c4d-8a83-08142581a31d',
                    'originalName' => 'video1.mp4',
                    'name' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.mp4',
                    'mimeType' => 'video/mp4',
                    'size' => '71541180',
                ],
                'configuration' => [
                    'id' => '249fe1bc-a8f0-4bea-8901-c7afe66fd3cf',
                    'subtitleFont' => 'ARIAL',
                    'subtitleSize' => '14',
                    'subtitleColor' => '#FFFFFF',
                    'subtitleBold' => '0',
                    'subtitleItalic' => '0',
                    'subtitleUnderline' => '0',
                    'subtitleOutlineColor' => '#000000',
                    'subtitleOutlineThickness' => '2',
                    'subtitleShadow' => '2',
                    'subtitleShadowColor' => '#000000',
                    'format' => 'NORMAL_916_WITH_BORDERS',
                    'split' => '1',
                    'marginV' => '50',
                ],
            ],
            'service' => 'sound_extractor',
        ]);
    }

    private function getJsonSubtitleGenerator(): string
    {
        return json_encode([
            'clip' => [
                'id' => '8e90c18c-da70-4e1b-8671-30ce14851cd2',
                'userId' => 'da59434f-602f-4d39-879c-eb0950812737',
                'status' => 'SUBTITLE_GENERATOR_PENDING',
                'originalVideo' => [
                    'id' => '87bad469-33bd-4c4d-8a83-08142581a31d',
                    'originalName' => 'video1.mp4',
                    'name' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.mp4',
                    'mimeType' => 'video/mp4',
                    'size' => '71541180',
                    'length' => '1449',
                    'audios' => [
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.wav',
                    ],
                ],
                'cover' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.jpg',
                'configuration' => [
                    'id' => '249fe1bc-a8f0-4bea-8901-c7afe66fd3cf',
                    'subtitleFont' => 'ARIAL',
                    'subtitleSize' => '14',
                    'subtitleColor' => '#FFFFFF',
                    'subtitleBold' => '0',
                    'subtitleItalic' => '0',
                    'subtitleUnderline' => '0',
                    'subtitleOutlineColor' => '#000000',
                    'subtitleOutlineThickness' => '2',
                    'subtitleShadow' => '2',
                    'subtitleShadowColor' => '#000000',
                    'format' => 'NORMAL_916_WITH_BORDERS',
                    'split' => '1',
                    'marginV' => '50',
                ],
            ],
            'service' => 'subtitle_generator',
        ]);
    }

    private function getJsonSubtitleMerger(): string
    {
        return json_encode([
            'clip' => [
                'id' => '8e90c18c-da70-4e1b-8671-30ce14851cd2',
                'userId' => 'da59434f-602f-4d39-879c-eb0950812737',
                'status' => 'SUBTITLE_MERGER_PENDING',
                'originalVideo' => [
                    'id' => '87bad469-33bd-4c4d-8a83-08142581a31d',
                    'originalName' => 'video1.mp4',
                    'name' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.mp4',
                    'mimeType' => 'video/mp4',
                    'size' => '71541180',
                    'length' => '1449',
                    'subtitles' => [
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.srt',
                    ],
                    'audios' => [
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.wav',
                    ],
                ],
                'cover' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.jpg',
                'configuration' => [
                    'id' => '249fe1bc-a8f0-4bea-8901-c7afe66fd3cf',
                    'subtitleFont' => 'ARIAL',
                    'subtitleSize' => '14',
                    'subtitleColor' => '#FFFFFF',
                    'subtitleBold' => '0',
                    'subtitleItalic' => '0',
                    'subtitleUnderline' => '0',
                    'subtitleOutlineColor' => '#000000',
                    'subtitleOutlineThickness' => '2',
                    'subtitleShadow' => '2',
                    'subtitleShadowColor' => '#000000',
                    'format' => 'NORMAL_916_WITH_BORDERS',
                    'split' => '1',
                    'marginV' => '50',
                ],
            ],
            'service' => 'subtitle_merger',
        ]);
    }

    private function getJsonSubtitleTransformer(): string
    {
        return json_encode([
            'clip' => [
                'id' => '8e90c18c-da70-4e1b-8671-30ce14851cd2',
                'userId' => 'da59434f-602f-4d39-879c-eb0950812737',
                'status' => 'SUBTITLE_TRANSFORMER_PENDING',
                'originalVideo' => [
                    'id' => '87bad469-33bd-4c4d-8a83-08142581a31d',
                    'originalName' => 'video1.mp4',
                    'name' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.mp4',
                    'mimeType' => 'video/mp4',
                    'size' => '71541180',
                    'length' => '1449',
                    'subtitle' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.srt',
                    'subtitles' => [
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.srt',
                    ],
                    'audios' => [
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.wav',
                    ],
                ],
                'cover' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.jpg',
                'configuration' => [
                    'id' => '249fe1bc-a8f0-4bea-8901-c7afe66fd3cf',
                    'subtitleFont' => 'ARIAL',
                    'subtitleSize' => '14',
                    'subtitleColor' => '#FFFFFF',
                    'subtitleBold' => '0',
                    'subtitleItalic' => '0',
                    'subtitleUnderline' => '0',
                    'subtitleOutlineColor' => '#000000',
                    'subtitleOutlineThickness' => '2',
                    'subtitleShadow' => '2',
                    'subtitleShadowColor' => '#000000',
                    'format' => 'NORMAL_916_WITH_BORDERS',
                    'split' => '1',
                    'marginV' => '50',
                ],
            ],
            'service' => 'subtitle_transformer',
        ]);
    }

    private function getJsonSubtitleIncrustator(): string
    {
        return json_encode([
            'clip' => [
                'id' => '8e90c18c-da70-4e1b-8671-30ce14851cd2',
                'userId' => 'da59434f-602f-4d39-879c-eb0950812737',
                'status' => 'SUBTITLE_INCRUSTATOR_PENDING',
                'originalVideo' => [
                    'id' => '87bad469-33bd-4c4d-8a83-08142581a31d',
                    'originalName' => 'video1.mp4',
                    'name' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.mp4',
                    'mimeType' => 'video/mp4',
                    'size' => '71541180',
                    'length' => '1449',
                    'subtitle' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.srt',
                    'ass' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.ass',
                    'subtitles' => [
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.srt',
                    ],
                    'audios' => [
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.wav',
                    ],
                ],
                'cover' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.jpg',
                'configuration' => [
                    'id' => '249fe1bc-a8f0-4bea-8901-c7afe66fd3cf',
                    'subtitleFont' => 'ARIAL',
                    'subtitleSize' => '14',
                    'subtitleColor' => '#FFFFFF',
                    'subtitleBold' => '0',
                    'subtitleItalic' => '0',
                    'subtitleUnderline' => '0',
                    'subtitleOutlineColor' => '#000000',
                    'subtitleOutlineThickness' => '2',
                    'subtitleShadow' => '2',
                    'subtitleShadowColor' => '#000000',
                    'format' => 'NORMAL_916_WITH_BORDERS',
                    'split' => '1',
                    'marginV' => '50',
                ],
            ],
            'service' => 'subtitle_incrustator',
        ]);
    }

    private function getJsonVideoFormatter(): string
    {
        return json_encode([
            'clip' => [
                'id' => '8e90c18c-da70-4e1b-8671-30ce14851cd2',
                'userId' => 'da59434f-602f-4d39-879c-eb0950812737',
                'status' => 'VIDEO_FORMATTER_PENDING',
                'originalVideo' => [
                    'id' => '87bad469-33bd-4c4d-8a83-08142581a31d',
                    'originalName' => 'video1.mp4',
                    'name' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.mp4',
                    'mimeType' => 'video/mp4',
                    'size' => '71541180',
                    'length' => '1449',
                    'subtitle' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.srt',
                    'ass' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.ass',
                    'subtitles' => [
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.srt',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.srt',
                    ],
                    'audios' => [
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_1.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_2.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_3.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_4.wav',
                        '8e90c18c-da70-4e1b-8671-30ce14851cd2_5.wav',
                    ],
                ],
                'cover' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.jpg',
                'configuration' => [
                    'id' => '164a482e-aee8-4184-821e-0edca831b1d0',
                    'subtitleFont' => 'ARIAL',
                    'subtitleSize' => '14',
                    'subtitleColor' => '#FFFFFF',
                    'subtitleBold' => '0',
                    'subtitleItalic' => '0',
                    'subtitleUnderline' => '0',
                    'subtitleOutlineColor' => '#000000',
                    'subtitleOutlineThickness' => '2',
                    'subtitleShadow' => '2',
                    'subtitleShadowColor' => '#000000',
                    'format' => 'NORMAL_916_WITH_BORDERS',
                    'split' => '1',
                    'marginV' => '50',
                ],
                'processedVideo' => [
                    'id' => 'fe6c8eed-f435-4b00-b9dd-3bf97e9e4eee',
                    'originalName' => 'video1.mp4',
                    'name' => '8e90c18c-da70-4e1b-8671-30ce14851cd2_processed.mp4',
                    'mimeType' => 'video/mp4',
                    'size' => '71541180',
                    'length' => '1449',
                    'subtitle' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.srt',
                    'ass' => '8e90c18c-da70-4e1b-8671-30ce14851cd2.ass',
                ],
            ],
            'service' => 'video_formatter',
        ]);
    }

    private function getJsonVideoSplitter(): string
    {
        return '';
    }
}
