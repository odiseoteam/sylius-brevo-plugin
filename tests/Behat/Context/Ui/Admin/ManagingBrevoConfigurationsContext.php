<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Service\NotificationCheckerInterface;
use Sylius\Behat\Service\Resolver\CurrentPageResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Setup\BrevoContext;
use Tests\Odiseo\SyliusBrevoPlugin\Behat\Page\Admin\ChannelConfiguration\CreatePageInterface;
use Tests\Odiseo\SyliusBrevoPlugin\Behat\Page\Admin\ChannelConfiguration\IndexPageInterface;
use Tests\Odiseo\SyliusBrevoPlugin\Behat\Page\Admin\ChannelConfiguration\UpdatePageInterface;
use Webmozart\Assert\Assert;

final class ManagingBrevoConfigurationsContext implements Context
{
    public function __construct(
        private readonly CurrentPageResolverInterface $currentPageResolver,
        private readonly IndexPageInterface $indexPage,
        private readonly CreatePageInterface $createPage,
        private readonly UpdatePageInterface $updatePage,
        private readonly NotificationCheckerInterface $notificationChecker,
        private readonly ChannelConfigurationRepositoryInterface $configurationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ConfigurationProviderInterface $configurationProvider,
    ) {
    }

    /**
     * @When I browse Brevo configurations
     */
    public function iBrowseBrevoConfigurations(): void
    {
        $this->indexPage->open();
    }

    /**
     * @When I want to configure Brevo for a channel
     */
    public function iWantToConfigureBrevoForAChannel(): void
    {
        $this->createPage->open();
    }

    /**
     * @When /^I want to modify the Brevo configuration of the ("[^"]+" channel)$/
     */
    public function iWantToModifyTheBrevoConfigurationOf(ChannelInterface $channel): void
    {
        $this->updatePage->open(['id' => $this->configurationOf($channel)->getId()]);
    }

    /**
     * @When I choose the :name channel
     */
    public function iChooseTheChannel(string $name): void
    {
        $this->createPage->chooseChannel($name);
    }

    /**
     * @When I set its API key to :apiKey
     */
    public function iSetItsApiKeyTo(string $apiKey): void
    {
        $this->resolveCurrentPage()->fillApiKey($apiKey);
    }

    /**
     * @When I set its default sender to :name with email :email
     */
    public function iSetItsDefaultSenderTo(string $name, string $email): void
    {
        $this->resolveCurrentPage()->fillSender($name, $email);
    }

    /**
     * @When I enable the :label module
     */
    public function iEnableTheModule(string $label): void
    {
        $this->resolveCurrentPage()->enableModule($label);
    }

    /**
     * @When I choose :name as its customers list
     */
    public function iChooseAsItsCustomersList(string $name): void
    {
        $this->updatePage->chooseCustomersList($name);
    }

    /**
     * @When I choose :name as its newsletter list
     */
    public function iChooseAsItsNewsletterList(string $name): void
    {
        $this->updatePage->chooseNewsletterList($name);
    }

    /**
     * @When I ask subscribers to confirm with the Brevo template :templateId
     */
    public function iAskSubscribersToConfirmWithTheTemplate(int $templateId): void
    {
        $this->updatePage->fillDoubleOptInTemplate($templateId);
    }

    /**
     * @When I add it
     */
    public function iAddIt(): void
    {
        $this->createPage->create();
    }

    /**
     * @When I save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->updatePage->saveChanges();
    }

    /**
     * @When I test the connection
     */
    public function iTestTheConnection(): void
    {
        $this->updatePage->testConnection();
    }

    /**
     * @Then I should see a Brevo configuration for the :name channel
     */
    public function iShouldSeeABrevoConfigurationFor(string $name): void
    {
        $this->indexPage->open();

        Assert::true($this->indexPage->isSingleResourceOnPage(['channel' => $name]));
    }

    /**
     * @Then /^the ("[^"]+" channel) should use the Brevo API key "([^"]+)"$/
     */
    public function theChannelShouldUseTheBrevoApiKey(ChannelInterface $channel, string $apiKey): void
    {
        Assert::same($this->configurationProvider->getCredentials($this->configurationOf($channel))?->apiKey, $apiKey);
    }

    /**
     * @Then /^the ("[^"]+" channel) should send from "([^"]+)" <([^>]+)>$/
     */
    public function theChannelShouldSendFrom(ChannelInterface $channel, string $name, string $email): void
    {
        $configuration = $this->configurationOf($channel);

        Assert::same($configuration->getSenderName(), $name);
        Assert::same($configuration->getSenderEmail(), $email);
    }

    /**
     * @Then /^the ("[^"]+" channel) should have the "([^"]+)" Brevo module enabled$/
     */
    public function theChannelShouldHaveTheModuleEnabled(ChannelInterface $channel, string $module): void
    {
        Assert::true($this->configurationOf($channel)->hasModule($module));
    }

    /**
     * @Then /^the ("[^"]+" channel) should add its customers to the Brevo list "([^"]+)"$/
     */
    public function theChannelShouldAddItsCustomersToTheList(ChannelInterface $channel, string $name): void
    {
        Assert::same($this->configurationOf($channel)->getCustomersListId(), BrevoContext::listId($name));
    }

    /**
     * @Then /^the ("[^"]+" channel) should have the Brevo newsletter list "([^"]+)"$/
     */
    public function theChannelShouldHaveTheNewsletterList(ChannelInterface $channel, string $name): void
    {
        Assert::same($this->configurationOf($channel)->getNewsletterListId(), BrevoContext::listId($name));
    }

    /**
     * @Then /^the ("[^"]+" channel) should ask subscribers to confirm with the Brevo template (\d+)$/
     */
    public function theChannelShouldAskSubscribersToConfirm(ChannelInterface $channel, int $templateId): void
    {
        Assert::same($this->configurationOf($channel)->getDoubleOptInTemplateId(), $templateId);
    }

    /**
     * @Then the API key should not be shown
     */
    public function theApiKeyShouldNotBeShown(): void
    {
        Assert::same($this->updatePage->getApiKey(), '');
    }

    /**
     * @Then I should not be able to change its channel
     */
    public function iShouldNotBeAbleToChangeItsChannel(): void
    {
        Assert::true($this->updatePage->isChannelDisabled());
    }

    /**
     * @Then I should be notified that this channel is already configured
     */
    public function iShouldBeNotifiedThatThisChannelIsAlreadyConfigured(): void
    {
        Assert::same(
            $this->createPage->getValidationMessage('channel'),
            'This channel already has a Brevo configuration.',
        );
    }

    /**
     * @Then I should be notified that the sender email is not valid
     */
    public function iShouldBeNotifiedThatTheSenderEmailIsNotValid(): void
    {
        Assert::same($this->resolveCurrentPage()->getValidationMessage('sender_email'), 'This email is not valid.');
    }

    /**
     * @Then I should be notified that the :module module needs the :required module
     */
    public function iShouldBeNotifiedThatTheModuleNeeds(string $module, string $required): void
    {
        Assert::same($this->updatePage->getModulesValidationMessage(), sprintf('%s needs the %s module too.', $module, $required));
    }

    /**
     * @Then I should be notified that I am connected to the Brevo account :email on the :plan plan
     */
    public function iShouldBeNotifiedThatIAmConnected(string $email, string $plan): void
    {
        $this->notificationChecker->checkNotification(
            sprintf('Connected to the Brevo account %s (plan: %s).', $email, $plan),
            NotificationType::success(),
        );
    }

    /**
     * @Then I should be notified that Brevo rejected the API key
     */
    public function iShouldBeNotifiedThatBrevoRejectedTheApiKey(): void
    {
        $this->notificationChecker->checkNotification('Brevo rejected the API key.', NotificationType::error());
    }

    private function configurationOf(ChannelInterface $channel): ChannelConfigurationInterface
    {
        // The browser saved it through another entity manager.
        $this->entityManager->clear();

        $configuration = $this->configurationRepository->findOneByChannel($channel);
        Assert::notNull($configuration, sprintf('The "%s" channel has no Brevo configuration.', $channel->getName()));

        return $configuration;
    }

    private function resolveCurrentPage(): CreatePageInterface|UpdatePageInterface
    {
        /** @var CreatePageInterface|UpdatePageInterface $page */
        $page = $this->currentPageResolver->getCurrentPageWithForm([$this->createPage, $this->updatePage]);

        return $page;
    }

    /**
     * @Then I should be warned that the :channelName channel needs an exchange rate from :from to :to
     */
    public function iShouldBeWarnedAboutAMissingExchangeRate(string $channelName, string $from, string $to): void
    {
        Assert::inArray(sprintf('%s: %s to %s', $channelName, $from, $to), $this->updatePage->getMissingExchangeRates());
    }

    /**
     * @Then I should not be warned about missing exchange rates
     */
    public function iShouldNotBeWarnedAboutMissingExchangeRates(): void
    {
        Assert::isEmpty($this->updatePage->getMissingExchangeRates());
    }
}
