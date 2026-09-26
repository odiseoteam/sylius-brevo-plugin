<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Behat\Mink\Session;
use Sylius\Behat\Page\Shop\Account\ProfileUpdatePageInterface;
use Sylius\Behat\Page\Shop\HomePageInterface;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;
use Webmozart\Assert\Assert;

final class NewsletterContext implements Context
{
    public function __construct(
        private readonly Session $session,
        private readonly HomePageInterface $homePage,
        private readonly ProfileUpdatePageInterface $profileUpdatePage,
        private readonly FakeBrevoHttpClient $fakeBrevoHttpClient,
    ) {
    }

    /**
     * @When I subscribe to the newsletter with :email
     */
    public function iSubscribeToTheNewsletterWith(string $email): void
    {
        $this->subscribe($email);
    }

    /**
     * @When a bot subscribes to the newsletter with :email
     */
    public function aBotSubscribesToTheNewsletterWith(string $email): void
    {
        $this->subscribe($email, 'https://spam.example.com');
    }

    /**
     * @Given I subscribed to the newsletter from my profile
     */
    public function iSubscribedToTheNewsletterFromMyProfile(): void
    {
        $this->profileUpdatePage->open();
        $this->profileUpdatePage->subscribeToTheNewsletter();
        $this->profileUpdatePage->saveChanges();
    }

    /**
     * @When I unsubscribe from the newsletter
     */
    public function iUnsubscribeFromTheNewsletter(): void
    {
        $checkbox = $this->session->getPage()->find('css', '[data-test-subscribe-newsletter]');
        Assert::notNull($checkbox);

        $checkbox->uncheck();
    }

    /**
     * @When I follow the confirmation link of the Brevo email
     */
    public function iFollowTheConfirmationLinkOfTheBrevoEmail(): void
    {
        $requests = $this->fakeBrevoHttpClient->requests('POST', '/contacts/doubleOptinConfirmation');
        $url = [] === $requests ? null : ($requests[array_key_last($requests)]->json['redirectionUrl'] ?? null);
        Assert::string($url, 'Brevo was not asked for a double opt-in.');

        $this->session->visit($url);
    }

    /**
     * @Then I should be notified that I am subscribed to the newsletter
     */
    public function iShouldBeNotifiedThatIAmSubscribed(): void
    {
        $this->assertFlash('You are subscribed to our newsletter.');
    }

    /**
     * @Then I should be asked to confirm my newsletter subscription
     */
    public function iShouldBeAskedToConfirm(): void
    {
        $this->assertFlash('Check your inbox and confirm your subscription.');
    }

    private function subscribe(string $email, ?string $website = null): void
    {
        $this->homePage->open();

        $page = $this->session->getPage();
        $field = $page->find('css', '[data-test-newsletter-email]');
        Assert::notNull($field, 'The newsletter form is not shown.');

        $field->setValue($email);
        if (null !== $website) {
            $page->find('css', '[data-test-newsletter-website]')?->setValue($website);
        }
        $page->find('css', '[data-test-newsletter-subscribe]')?->press();
    }

    /** Shown in the newsletter section. */
    private function assertFlash(string $text): void
    {
        $flashes = $this->session->getPage()->findAll('css', '[data-test-newsletter-message]');
        $texts = array_map(static fn ($flash): string => $flash->getText(), $flashes);

        Assert::true(
            [] !== array_filter($texts, static fn (string $flash): bool => str_contains($flash, $text)),
            sprintf('No "%s" message, got: %s', $text, implode(' | ', $texts)),
        );
    }
}
