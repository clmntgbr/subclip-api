<?php

namespace App\Controller;

use App\Entity\Clip;
use App\Entity\Configuration;
use App\Entity\User;
use App\Entity\Video;
use App\Protobuf\ClipStatus;
use App\Repository\ClipRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use League\Flysystem\FilesystemOperator;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
    ) {
    }

    #[Route('/debug/{service}', name: 'debug', methods: ['GET'])]
    public function debug(#[CurrentUser] ?User $user, string $service): JsonResponse
    {
        $channel = $this->rabbitMqConnection();

        $video = $this->getOriginalVideo('464f7205-9d37-41b2-bb78-c2f652d7fc33');
        $clip = $this->getClip($user, 'e363934c-837f-49fa-9f4a-55bb9afcfcff', $video);

        $this->sendToS3($user, $clip);

        $message = match ($service) {
            'sound_extractor' => $this->toSoundExtractor($clip),
            'subtitle_generator' => $this->toSubtitleGenerator($clip),
            'subtitle_merger' => $this->toSubtitleMerger($clip),
            'subtitle_transformer' => $this->toSubtitleTransformer($clip),
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
            'video.mp4',
            'e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4',
            'video/mp4',
            71541180
        );

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
            $user,
            Uuid::fromString($id),
            $video,
            new Configuration(),
        );

        return $clip;
    }

    private function sendToS3(User $user, Clip $clip): void
    {
        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4');
        $stream = fopen('/app/public/debug/e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        // Audios

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_1.wav');
        $stream = fopen('/app/public/debug/audios/e363934c-837f-49fa-9f4a-55bb9afcfcff_1.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_2.wav');
        $stream = fopen('/app/public/debug/audios/e363934c-837f-49fa-9f4a-55bb9afcfcff_2.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_3.wav');
        $stream = fopen('/app/public/debug/audios/e363934c-837f-49fa-9f4a-55bb9afcfcff_3.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_4.wav');
        $stream = fopen('/app/public/debug/audios/e363934c-837f-49fa-9f4a-55bb9afcfcff_4.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_5.wav');
        $stream = fopen('/app/public/debug/audios/e363934c-837f-49fa-9f4a-55bb9afcfcff_5.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        // Subtitles

        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff.srt');
        $stream = fopen('/app/public/debug/e363934c-837f-49fa-9f4a-55bb9afcfcff.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff.ass');
        $stream = fopen('/app/public/debug/e363934c-837f-49fa-9f4a-55bb9afcfcff.ass', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_1.srt');
        $stream = fopen('/app/public/debug/subtitles/e363934c-837f-49fa-9f4a-55bb9afcfcff_1.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_2.srt');
        $stream = fopen('/app/public/debug/subtitles/e363934c-837f-49fa-9f4a-55bb9afcfcff_2.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_3.srt');
        $stream = fopen('/app/public/debug/subtitles/e363934c-837f-49fa-9f4a-55bb9afcfcff_3.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_4.srt');
        $stream = fopen('/app/public/debug/subtitles/e363934c-837f-49fa-9f4a-55bb9afcfcff_4.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt');
        $stream = fopen('/app/public/debug/subtitles/e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt');
        $stream = fopen('/app/public/debug/subtitles/e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt', 'r');
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
        $clip->setStatus(ClipStatus::name(ClipStatus::SUBTITLE_INCRUSTATOR_PENDING));


        return $message;
    }

    private function getJsonSoundExtractor(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4","mimeType":"video/mp4","size":"71541180"},"status":"SOUND_EXTRACTOR_PENDING","configuration":{"id":"4dd6c05b-0dd6-4ef0-bcb3-7feccb330641","subtitleFont":"ARIAL","subtitleSize":16,"subtitleColor":"#FFFFFF","subtitleBold":"0","subtitleItalic":"0","subtitleUnderline":"0","subtitleOutlineColor":"#000000","subtitleOutlineThickness":2,"subtitleShadow":2,"subtitleShadowColor":"#000000","format":"ORIGINAL","split":1}}}';
    }

    private function getJsonSubtitleGenerator(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","audios":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.wav"]},"status":"SOUND_EXTRACTOR_COMPLETE","configuration":{"id":"4dd6c05b-0dd6-4ef0-bcb3-7feccb330641","subtitleFont":"ARIAL","subtitleSize":16,"subtitleColor":"#FFFFFF","subtitleBold":"0","subtitleItalic":"0","subtitleUnderline":"0","subtitleOutlineColor":"#000000","subtitleOutlineThickness":2,"subtitleShadow":2,"subtitleShadowColor":"#000000","format":"ORIGINAL","split":1}}}';
    }

    private function getJsonSubtitleMerger(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitles":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt"],"audios":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.wav"]},"status":"SUBTITLE_GENERATOR_COMPLETE","configuration":{"id":"4dd6c05b-0dd6-4ef0-bcb3-7feccb330641","subtitleFont":"ARIAL","subtitleSize":16,"subtitleColor":"#FFFFFF","subtitleBold":"0","subtitleItalic":"0","subtitleUnderline":"0","subtitleOutlineColor":"#000000","subtitleOutlineThickness":2,"subtitleShadow":2,"subtitleShadowColor":"#000000","format":"ORIGINAL","split":1}}}';
    }

    private function getJsonSubtitleTransformer(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"e363934c-837f-49fa-9f4a-55bb9afcfcff.srt","subtitles":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt"],"audios":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.wav"]},"status":"SUBTITLE_MERGER_COMPLETE","configuration":{"id":"4dd6c05b-0dd6-4ef0-bcb3-7feccb330641","subtitleFont":"ARIAL","subtitleSize":16,"subtitleColor":"#FFFFFF","subtitleBold":"0","subtitleItalic":"0","subtitleUnderline":"0","subtitleOutlineColor":"#000000","subtitleOutlineThickness":2,"subtitleShadow":2,"subtitleShadowColor":"#000000","format":"ORIGINAL","split":1}}}';
    }

    private function getJsonVideoFormatter(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"e363934c-837f-49fa-9f4a-55bb9afcfcff.srt","ass":"e363934c-837f-49fa-9f4a-55bb9afcfcff.ass","subtitles":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt"],"audios":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.wav"]},"status":"SUBTITLE_TRANSFORMER_COMPLETE","configuration":{"id":"4dd6c05b-0dd6-4ef0-bcb3-7feccb330641","subtitleFont":"ARIAL","subtitleSize":16,"subtitleColor":"#FFFFFF","subtitleBold":"0","subtitleItalic":"0","subtitleUnderline":"0","subtitleOutlineColor":"#000000","subtitleOutlineThickness":2,"subtitleShadow":2,"subtitleShadowColor":"#000000","format":"ORIGINAL","split":1}}}';
    }

    private function getJsonSubtitleIncrustator(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"e363934c-837f-49fa-9f4a-55bb9afcfcff.srt","ass":"e363934c-837f-49fa-9f4a-55bb9afcfcff.ass","subtitles":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt"],"audios":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.wav"]},"status":"VIDEO_FORMATTER_COMPLETE","configuration":{"id":"4dd6c05b-0dd6-4ef0-bcb3-7feccb330641","subtitleFont":"ARIAL","subtitleSize":16,"subtitleColor":"#FFFFFF","subtitleBold":"0","subtitleItalic":"0","subtitleUnderline":"0","subtitleOutlineColor":"#000000","subtitleOutlineThickness":2,"subtitleShadow":2,"subtitleShadowColor":"#000000","format":"ORIGINAL","split":1}}}';
    }

    private function getJsonVideoSplitter(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"e363934c-837f-49fa-9f4a-55bb9afcfcff.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"e363934c-837f-49fa-9f4a-55bb9afcfcff.srt","ass":"e363934c-837f-49fa-9f4a-55bb9afcfcff.ass","subtitles":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt"],"audios":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.wav"]},"status":"SUBTITLE_INCRUSTATOR_COMPLETE","configuration":{"id":"4dd6c05b-0dd6-4ef0-bcb3-7feccb330641","subtitleFont":"ARIAL","subtitleSize":16,"subtitleColor":"#FFFFFF","subtitleBold":"0","subtitleItalic":"0","subtitleUnderline":"0","subtitleOutlineColor":"#000000","subtitleOutlineThickness":2,"subtitleShadow":2,"subtitleShadowColor":"#000000","format":"ORIGINAL","split":1},"processedVideo":{"id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"e363934c-837f-49fa-9f4a-55bb9afcfcff_processed.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"e363934c-837f-49fa-9f4a-55bb9afcfcff.srt","ass":"e363934c-837f-49fa-9f4a-55bb9afcfcff.ass","subtitles":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.srt","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.srt"],"audios":["e363934c-837f-49fa-9f4a-55bb9afcfcff_1.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_2.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_3.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_4.wav","e363934c-837f-49fa-9f4a-55bb9afcfcff_5.wav"]}}}';
    }
}
