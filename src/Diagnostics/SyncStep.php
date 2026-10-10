<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Diagnostics;

/**
 * A step of `odiseo:brevo:sync`: a console command run when a channel has its module on. Tag:
 * `odiseo_brevo.sync_step`, higher priority first. The command gets `--channel` and `--dry-run`
 * when it has them.
 */
final readonly class SyncStep
{
    public function __construct(
        public string $name,
        public string $module,
        public string $command,
    ) {
    }
}
