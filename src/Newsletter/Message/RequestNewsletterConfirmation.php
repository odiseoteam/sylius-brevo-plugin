<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Newsletter\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class RequestNewsletterConfirmation extends AbstractBrevoMessage
{
    public function __construct(
        string $channelCode,
        public readonly string $email,
        public readonly string $localeCode,
    ) {
        parent::__construct($channelCode, sprintf('newsletter:%s:confirmation', mb_strtolower($email)));
    }
}
