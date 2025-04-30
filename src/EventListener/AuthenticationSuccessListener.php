<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;

use const App\Entity\USER_READ;

class AuthenticationSuccessListener
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        /** @var User $user */
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $context = (new ObjectNormalizerContextBuilder())
        ->withGroups([USER_READ])
        ->toArray();

        $data['user'] = json_decode($this->serializer->serialize($user, 'json', $context));

        $event->setData($data);
    }
}
