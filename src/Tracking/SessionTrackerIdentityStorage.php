<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking;

use Odiseo\SyliusBrevoPlugin\Contact\ContactExtId;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

final class SessionTrackerIdentityStorage implements TrackerIdentityStorageInterface
{
    private const KEY = 'odiseo_brevo.tracker_identity';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function remember(CustomerInterface $customer): void
    {
        $email = $customer->getEmail();
        if (null === $email || '' === $email) {
            return;
        }

        $identifiers = ['email_id' => $email];
        $extId = ContactExtId::of($customer);
        if ('' !== $extId) {
            $identifiers['ext_id'] = $extId;
        }

        try {
            $this->requestStack->getSession()->set(self::KEY, $identifiers);
        } catch (SessionNotFoundException) {
        }
    }

    public function pull(): ?array
    {
        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }

        // Read without starting a session for visitors who have none.
        if (!$session->isStarted() && !$this->requestStack->getCurrentRequest()?->hasPreviousSession()) {
            return null;
        }

        $identifiers = $session->remove(self::KEY);
        if (!is_array($identifiers) || !is_string($identifiers['email_id'] ?? null)) {
            return null;
        }

        return is_string($identifiers['ext_id'] ?? null)
            ? ['email_id' => $identifiers['email_id'], 'ext_id' => $identifiers['ext_id']]
            : ['email_id' => $identifiers['email_id']];
    }
}
