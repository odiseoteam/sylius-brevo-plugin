<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Command;

use Odiseo\SyliusBrevoPlugin\Catalog\CatalogTargetResolverInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\EcommerceActivatorInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'odiseo:brevo:ecommerce:activate',
    description: 'Activates Brevo Ecommerce and sets its currency on the accounts of the channels with the catalog module.',
)]
final class ActivateEcommerceCommand extends Command
{
    public function __construct(
        private readonly CatalogTargetResolverInterface $targetResolver,
        private readonly EcommerceActivatorInterface $activator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Only the Brevo account of this channel code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $channelCode = $input->getOption('channel');

        $channels = array_filter(
            $this->targetResolver->accounts(),
            static fn ($channel): bool => null === $channelCode || $channel->getCode() === $channelCode,
        );
        if ([] === $channels) {
            $io->warning('No channel with an enabled Brevo configuration and the catalog module on.');

            return Command::SUCCESS;
        }

        $failed = false;
        foreach ($channels as $channel) {
            try {
                $currency = $this->activator->activate($channel);
                $io->success(sprintf('Brevo Ecommerce activated for channel %s%s.', (string) $channel->getCode(), null === $currency ? '' : ', amounts in ' . $currency));
            } catch (BrevoException $exception) {
                $io->error(sprintf('Channel %s: %s', (string) $channel->getCode(), $exception->getMessage()));
                $failed = true;
            }
        }

        $io->note('A first activation takes Brevo a few minutes; then run odiseo:brevo:categories:sync.');

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
