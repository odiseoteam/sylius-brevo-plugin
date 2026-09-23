<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Message;

/** Every message that ends in a Brevo call. Routed to the `odiseo_brevo` transport. */
interface BrevoMessageInterface
{
    public function getChannelCode(): string;

    /** Same key = same change, e.g. "order:123:placed". */
    public function getIdempotencyKey(): string;
}
