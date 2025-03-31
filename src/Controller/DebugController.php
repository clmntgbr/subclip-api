<?php

namespace App\Controller;

use App\Entity\Clip;
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
            'f27644432084872be07b716b6b32af76.mp4',
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
            $video
        );

        return $clip;
    }

    private function sendToS3(User $user, Clip $clip): void
    {
        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76.mp4');
        $stream = fopen('/app/public/debug/f27644432084872be07b716b6b32af76.mp4', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        // Audios

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_1.wav');
        $stream = fopen('/app/public/debug/audios/f27644432084872be07b716b6b32af76_1.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_2.wav');
        $stream = fopen('/app/public/debug/audios/f27644432084872be07b716b6b32af76_2.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_3.wav');
        $stream = fopen('/app/public/debug/audios/f27644432084872be07b716b6b32af76_3.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_4.wav');
        $stream = fopen('/app/public/debug/audios/f27644432084872be07b716b6b32af76_4.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/audios/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_5.wav');
        $stream = fopen('/app/public/debug/audios/f27644432084872be07b716b6b32af76_5.wav', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        // Subtitles

        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76.srt');
        $stream = fopen('/app/public/debug/f27644432084872be07b716b6b32af76.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76.ass');
        $stream = fopen('/app/public/debug/f27644432084872be07b716b6b32af76.ass', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_1.srt');
        $stream = fopen('/app/public/debug/subtitles/f27644432084872be07b716b6b32af76_1.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_2.srt');
        $stream = fopen('/app/public/debug/subtitles/f27644432084872be07b716b6b32af76_2.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_3.srt');
        $stream = fopen('/app/public/debug/subtitles/f27644432084872be07b716b6b32af76_3.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_4.srt');
        $stream = fopen('/app/public/debug/subtitles/f27644432084872be07b716b6b32af76_4.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_5.srt');
        $stream = fopen('/app/public/debug/subtitles/f27644432084872be07b716b6b32af76_5.srt', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $path = sprintf('%s/%s/subtitles/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_5.srt');
        $stream = fopen('/app/public/debug/subtitles/f27644432084872be07b716b6b32af76_5.srt', 'r');
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

    private function toVideoFormatter(): AMQPMessage
    {
        $messageData = json_encode([
            'task' => 'tasks.process_message',
            'args' => [$this->getJsonVideoFormatter()],
            'queue' => 'video_transformer',
        ]);

        $message = new AMQPMessage($messageData,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'headers' => [
                    'type' => 'video_transformer',
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        return $message;
    }

    private function toSubtitleIncrustator(User $user, Clip $clip): AMQPMessage
    {
        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_processed.mp4');
        $stream = fopen('/app/public/debug/f27644432084872be07b716b6b32af76_processed_subtitle_incrustator.mp4', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

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

        return $message;
    }

    private function toVideoSplitter(User $user, Clip $clip): AMQPMessage
    {
        $path = sprintf('%s/%s/%s', $user->getId(), $clip->getId(), 'f27644432084872be07b716b6b32af76_processed.mp4');
        $stream = fopen('/app/public/debug/f27644432084872be07b716b6b32af76_processed_video_splitter.mp4', 'r');
        $this->awsStorage->writeStream($path, $stream, [
            'visibility' => 'public',
        ]);

        $messageData = json_encode([
            'task' => 'tasks.process_message',
            'args' => [$this->getJsonVideoSplitter()],
            'queue' => 'video_splitter',
        ]);

        $message = new AMQPMessage($messageData,
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'headers' => [
                    'type' => 'video_splitter',
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        return $message;
    }

    private function getJsonSoundExtractor(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"f27644432084872be07b716b6b32af76.mp4","mimeType":"video/mp4","size":"71541180"},"status":"SOUND_EXTRACTOR_PENDING"}}';
    }

    private function getJsonSubtitleGenerator(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"f27644432084872be07b716b6b32af76.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","audios":["f27644432084872be07b716b6b32af76_1.wav","f27644432084872be07b716b6b32af76_2.wav","f27644432084872be07b716b6b32af76_3.wav","f27644432084872be07b716b6b32af76_4.wav","f27644432084872be07b716b6b32af76_5.wav"]},"status":"SOUND_EXTRACTOR_COMPLETE"}}';
    }

    private function getJsonSubtitleMerger(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"f27644432084872be07b716b6b32af76.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitles":["f27644432084872be07b716b6b32af76_1.srt","f27644432084872be07b716b6b32af76_2.srt","f27644432084872be07b716b6b32af76_3.srt","f27644432084872be07b716b6b32af76_4.srt","f27644432084872be07b716b6b32af76_5.srt"],"audios":["f27644432084872be07b716b6b32af76_1.wav","f27644432084872be07b716b6b32af76_2.wav","f27644432084872be07b716b6b32af76_3.wav","f27644432084872be07b716b6b32af76_4.wav","f27644432084872be07b716b6b32af76_5.wav"]},"status":"SUBTITLE_GENERATOR_COMPLETE"}}';
    }

    private function getJsonSubtitleTransformer(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"f27644432084872be07b716b6b32af76.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"f27644432084872be07b716b6b32af76.srt","subtitles":["f27644432084872be07b716b6b32af76_1.srt","f27644432084872be07b716b6b32af76_2.srt","f27644432084872be07b716b6b32af76_3.srt","f27644432084872be07b716b6b32af76_4.srt","f27644432084872be07b716b6b32af76_5.srt"],"audios":["f27644432084872be07b716b6b32af76_1.wav","f27644432084872be07b716b6b32af76_2.wav","f27644432084872be07b716b6b32af76_3.wav","f27644432084872be07b716b6b32af76_4.wav","f27644432084872be07b716b6b32af76_5.wav"]},"status":"SUBTITLE_MERGER_COMPLETE"}}';
    }

    private function getJsonVideoFormatter(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"f27644432084872be07b716b6b32af76.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"f27644432084872be07b716b6b32af76.srt","ass":"f27644432084872be07b716b6b32af76.ass","subtitles":["f27644432084872be07b716b6b32af76_1.srt","f27644432084872be07b716b6b32af76_2.srt","f27644432084872be07b716b6b32af76_3.srt","f27644432084872be07b716b6b32af76_4.srt","f27644432084872be07b716b6b32af76_5.srt"],"audios":["f27644432084872be07b716b6b32af76_1.wav","f27644432084872be07b716b6b32af76_2.wav","f27644432084872be07b716b6b32af76_3.wav","f27644432084872be07b716b6b32af76_4.wav","f27644432084872be07b716b6b32af76_5.wav"]},"status":"SUBTITLE_TRANSFORMER_COMPLETE"}}';
    }

    private function getJsonSubtitleIncrustator(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"f27644432084872be07b716b6b32af76.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"f27644432084872be07b716b6b32af76.srt","ass":"f27644432084872be07b716b6b32af76.ass","subtitles":["f27644432084872be07b716b6b32af76_1.srt","f27644432084872be07b716b6b32af76_2.srt","f27644432084872be07b716b6b32af76_3.srt","f27644432084872be07b716b6b32af76_4.srt","f27644432084872be07b716b6b32af76_5.srt"],"audios":["f27644432084872be07b716b6b32af76_1.wav","f27644432084872be07b716b6b32af76_2.wav","f27644432084872be07b716b6b32af76_3.wav","f27644432084872be07b716b6b32af76_4.wav","f27644432084872be07b716b6b32af76_5.wav"]},"status":"VIDEO_FORMATTER_COMPLETE","processedVideo":{"id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"f27644432084872be07b716b6b32af76_processed.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"f27644432084872be07b716b6b32af76.srt","ass":"f27644432084872be07b716b6b32af76.ass","subtitles":["f27644432084872be07b716b6b32af76_1.srt","f27644432084872be07b716b6b32af76_2.srt","f27644432084872be07b716b6b32af76_3.srt","f27644432084872be07b716b6b32af76_4.srt","f27644432084872be07b716b6b32af76_5.srt"],"audios":["f27644432084872be07b716b6b32af76_1.wav","f27644432084872be07b716b6b32af76_2.wav","f27644432084872be07b716b6b32af76_3.wav","f27644432084872be07b716b6b32af76_4.wav","f27644432084872be07b716b6b32af76_5.wav"]}}}';
    }

    private function getJsonVideoSplitter(): string
    {
        return '{"clip":{"id":"e363934c-837f-49fa-9f4a-55bb9afcfcff","userId":"da59434f-602f-4d39-879c-eb0950812737","originalVideo":{"originalName":"video.mp4","id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"f27644432084872be07b716b6b32af76.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"f27644432084872be07b716b6b32af76.srt","ass":"f27644432084872be07b716b6b32af76.ass","subtitles":["f27644432084872be07b716b6b32af76_1.srt","f27644432084872be07b716b6b32af76_2.srt","f27644432084872be07b716b6b32af76_3.srt","f27644432084872be07b716b6b32af76_4.srt","f27644432084872be07b716b6b32af76_5.srt"],"audios":["f27644432084872be07b716b6b32af76_1.wav","f27644432084872be07b716b6b32af76_2.wav","f27644432084872be07b716b6b32af76_3.wav","f27644432084872be07b716b6b32af76_4.wav","f27644432084872be07b716b6b32af76_5.wav"]},"status":"SUBTITLE_INCRUSTATOR_COMPLETE","processedVideo":{"id":"464f7205-9d37-41b2-bb78-c2f652d7fc33","name":"f27644432084872be07b716b6b32af76_processed.mp4","mimeType":"video/mp4","size":"71541180","length":"1449","subtitle":"f27644432084872be07b716b6b32af76.srt","ass":"f27644432084872be07b716b6b32af76.ass","subtitles":["f27644432084872be07b716b6b32af76_1.srt","f27644432084872be07b716b6b32af76_2.srt","f27644432084872be07b716b6b32af76_3.srt","f27644432084872be07b716b6b32af76_4.srt","f27644432084872be07b716b6b32af76_5.srt"],"audios":["f27644432084872be07b716b6b32af76_1.wav","f27644432084872be07b716b6b32af76_2.wav","f27644432084872be07b716b6b32af76_3.wav","f27644432084872be07b716b6b32af76_4.wav","f27644432084872be07b716b6b32af76_5.wav"]}}}';
    }
}
