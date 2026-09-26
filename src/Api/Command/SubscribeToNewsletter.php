<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Api\Command;

use Sylius\Bundle\ApiBundle\Attribute\ChannelCodeAware;
use Sylius\Bundle\ApiBundle\Attribute\LocaleCodeAware;
use Sylius\Bundle\ApiBundle\Attribute\LoggedInCustomerEmailAware;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/** Without an email, the logged-in customer's. */
#[ChannelCodeAware]
#[LocaleCodeAware]
#[LoggedInCustomerEmailAware]
final class SubscribeToNewsletter
{
    public function __construct(
        public readonly string $channelCode,
        public readonly string $localeCode,
        #[Groups(['odiseo_brevo:shop:newsletter_subscription:create'])]
        #[Assert\NotBlank(message: 'odiseo_brevo.newsletter.email.not_blank')]
        #[Assert\Email(message: 'odiseo_brevo.newsletter.email.invalid')]
        public readonly ?string $email = null,
    ) {
    }
}
