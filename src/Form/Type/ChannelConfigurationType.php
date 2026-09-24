<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Form\Type;

use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Module\ModuleRegistryInterface;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class ChannelConfigurationType extends AbstractResourceType
{
    /** @param list<string> $validationGroups */
    public function __construct(
        string $dataClass,
        array $validationGroups,
        private readonly ModuleRegistryInterface $moduleRegistry,
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $modules = [];
        foreach ($this->moduleRegistry->all() as $code => $module) {
            $modules[$module->getLabel()] = $code;
        }

        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => 'sylius.ui.enabled',
                'required' => false,
            ])
            // Never rendered back; left blank it keeps the stored key.
            ->add('apiKey', PasswordType::class, [
                'label' => 'odiseo_brevo.form.channel_configuration.api_key',
                'mapped' => false,
                'required' => false,
            ])
            ->add('senderName', TextType::class, [
                'label' => 'odiseo_brevo.form.channel_configuration.sender_name',
                'required' => false,
            ])
            ->add('senderEmail', EmailType::class, [
                'label' => 'odiseo_brevo.form.channel_configuration.sender_email',
                'required' => false,
            ])
            ->add('syncingGuestContacts', CheckboxType::class, [
                'label' => 'odiseo_brevo.form.channel_configuration.syncing_guest_contacts',
                'required' => false,
            ])
            ->add('deletingContactsOfRemovedCustomers', CheckboxType::class, [
                'label' => 'odiseo_brevo.form.channel_configuration.deleting_contacts_of_removed_customers',
                'required' => false,
            ])
            ->add('modules', ChoiceType::class, [
                'label' => 'odiseo_brevo.form.channel_configuration.modules',
                'choices' => $modules,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
                $configuration = $event->getData();

                // The channel is the configuration's identity: chosen once.
                $event->getForm()->add('channel', ChannelChoiceType::class, [
                    'label' => 'sylius.ui.channel',
                    'disabled' => $configuration instanceof ChannelConfigurationInterface && null !== $configuration->getId(),
                    'placeholder' => 'odiseo_brevo.form.channel_configuration.choose_channel',
                ]);
            })
            ->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
                $configuration = $event->getData();
                $apiKey = $event->getForm()->get('apiKey')->getData();

                if ($configuration instanceof ChannelConfigurationInterface && is_string($apiKey) && '' !== trim($apiKey)) {
                    $configuration->setApiKey(trim($apiKey));
                }
            })
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'odiseo_brevo_channel_configuration';
    }
}
