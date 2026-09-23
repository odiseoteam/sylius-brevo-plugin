<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Configuration;

use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;

/** Resolved, ready-to-use Brevo settings of an enabled channel. */
final readonly class BrevoSettings
{
    /** @param list<string> $modules */
    public function __construct(
        public string $channelCode,
        public Credentials $credentials,
        public ?string $senderName = null,
        public ?string $senderEmail = null,
        public array $modules = [],
    ) {
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules, true);
    }
}
