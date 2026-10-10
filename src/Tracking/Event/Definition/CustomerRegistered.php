<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition;

use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSide;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Webmozart\Assert\Assert;

/** The customer created an account in the shop. */
final class CustomerRegistered implements TrackingEventInterface
{
    public const CODE = 'customer_registered';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getSide(): TrackingEventSide
    {
        return TrackingEventSide::Server;
    }

    public function supports(object $subject): bool
    {
        return $subject instanceof CustomerInterface;
    }

    public function getProperties(object $subject, ChannelInterface $channel): array
    {
        Assert::isInstanceOf($subject, CustomerInterface::class);

        return [
            'first_name' => $subject->getFirstName(),
            'last_name' => $subject->getLastName(),
            'subscribed_to_newsletter' => $subject->isSubscribedToNewsletter(),
        ];
    }
}
