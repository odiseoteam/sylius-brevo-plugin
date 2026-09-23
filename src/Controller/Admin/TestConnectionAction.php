<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Controller\Admin;

use Odiseo\SyliusBrevoPlugin\Client\Api\AccountApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\AuthenticationException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** Calls Brevo right away: the only synchronous call, on explicit admin request. */
final class TestConnectionAction
{
    public const CSRF_TOKEN_ID = 'odiseo_brevo_test_connection';

    public function __construct(
        private readonly ChannelConfigurationRepositoryInterface $configurationRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly AccountApiInterface $accountApi,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $configuration = $this->configurationRepository->find($id);
        if (!$configuration instanceof ChannelConfigurationInterface) {
            throw new NotFoundHttpException();
        }

        $token = new CsrfToken(self::CSRF_TOKEN_ID, (string) $request->request->get('_csrf_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $this->addFlash($request, ...$this->test($this->configurationProvider->getCredentials($configuration)));

        return new RedirectResponse($this->urlGenerator->generate('odiseo_brevo_admin_channel_configuration_update', ['id' => $id]));
    }

    /** @return array{string, string, array<string, string>} */
    private function test(?Credentials $credentials): array
    {
        if (null === $credentials) {
            return ['error', 'odiseo_brevo.channel_configuration.test_connection.missing_api_key', []];
        }

        try {
            $account = $this->accountApi->getAccount($credentials);
        } catch (AuthenticationException) {
            return ['error', 'odiseo_brevo.channel_configuration.test_connection.invalid_api_key', []];
        } catch (BrevoException $exception) {
            return ['error', 'odiseo_brevo.channel_configuration.test_connection.failed', ['%error%' => $exception->getMessage()]];
        }

        $plans = implode(', ', array_map(static fn ($plan): string => $plan->type, $account->plans));

        return ['success', 'odiseo_brevo.channel_configuration.test_connection.success', [
            '%email%' => $account->email,
            '%plan%' => '' === $plans ? '-' : $plans,
        ]];
    }

    /** @param array<string, string> $parameters */
    private function addFlash(Request $request, string $type, string $message, array $parameters): void
    {
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, ['message' => $message, 'parameters' => $parameters]);
        }
    }
}
