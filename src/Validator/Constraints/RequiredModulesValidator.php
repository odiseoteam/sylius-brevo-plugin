<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Validator\Constraints;

use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Module\DependentModuleInterface;
use Odiseo\SyliusBrevoPlugin\Module\ModuleRegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RequiredModulesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ModuleRegistryInterface $moduleRegistry,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RequiredModules) {
            throw new UnexpectedTypeException($constraint, RequiredModules::class);
        }

        if (!$value instanceof ChannelConfigurationInterface) {
            return;
        }

        $modules = $this->moduleRegistry->all();
        $enabled = $value->getModules();

        foreach ($enabled as $code) {
            $module = $modules[$code] ?? null;
            if (!$module instanceof DependentModuleInterface) {
                continue;
            }

            foreach (array_diff($module->getRequiredModules(), $enabled) as $required) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('%module%', $this->label($module->getLabel()))
                    ->setParameter('%required%', $this->label(($modules[$required] ?? null)?->getLabel() ?? $required))
                    ->atPath('modules')
                    ->addViolation()
                ;
            }
        }
    }

    /** The module name, without the description after the colon. */
    private function label(string $key): string
    {
        return trim(explode(':', $this->translator->trans($key), 2)[0]);
    }
}
