<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Form\Type;

use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Form\BrevoListChoicesInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** A list of the configuration's Brevo account. The current one stays selectable if Brevo can't be read. */
final class BrevoListChoiceType extends AbstractType
{
    public function __construct(
        private readonly BrevoListChoicesInterface $listChoices,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'configuration' => null,
                'current_list_id' => null,
                'list_help' => null,
                'required' => false,
                'placeholder' => 'odiseo_brevo.form.brevo_list.none',
                'choice_translation_domain' => false,
                'choices' => fn (Options $options): array => $this->choices($options),
                'help' => fn (Options $options): mixed => null === $this->listChoices->forConfiguration(
                    $options['configuration'] instanceof ChannelConfigurationInterface ? $options['configuration'] : null,
                )
                    ? 'odiseo_brevo.form.brevo_list.unavailable'
                    : $options['list_help'],
            ])
            ->setAllowedTypes('configuration', ['null', ChannelConfigurationInterface::class])
            ->setAllowedTypes('current_list_id', ['null', 'int'])
            ->setAllowedTypes('list_help', ['null', 'string'])
        ;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'odiseo_brevo_list_choice';
    }

    /** @return array<string, int> */
    private function choices(Options $options): array
    {
        /** @var ChannelConfigurationInterface|null $configuration */
        $configuration = $options['configuration'];
        /** @var int|null $current */
        $current = $options['current_list_id'];

        $choices = $this->listChoices->forConfiguration($configuration) ?? [];
        if (null !== $current && !in_array($current, $choices, true)) {
            $choices[sprintf('#%d', $current)] = $current;
        }

        return $choices;
    }
}
