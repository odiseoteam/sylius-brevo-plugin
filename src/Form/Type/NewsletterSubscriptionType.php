<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

final class NewsletterSubscriptionType extends AbstractType
{
    public const HONEYPOT = 'website';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'odiseo_brevo.ui.newsletter.email',
                'constraints' => [
                    new NotBlank(message: 'odiseo_brevo.newsletter.email.not_blank'),
                    new Email(message: 'odiseo_brevo.newsletter.email.invalid'),
                ],
            ])
            // Honeypot: hidden to people, bots fill it in.
            ->add(self::HONEYPOT, TextType::class, [
                'label' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // It's on every page: a token would keep pages from being cached. Honeypot and double opt-in cover abuse.
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'odiseo_brevo_newsletter';
    }
}
