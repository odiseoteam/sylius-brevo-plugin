<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** Every enabled module has the modules it needs enabled too. */
final class RequiredModules extends Constraint
{
    public string $message = 'odiseo_brevo.channel_configuration.modules.required';

    public function validatedBy(): string
    {
        return 'odiseo_brevo_required_modules';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
