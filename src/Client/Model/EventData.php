<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/** A custom event on a contact (Events API). */
final readonly class EventData
{
    /**
     * @param array<string, string> $identifiers e.g. email_id, ext_id
     * @param array<string, mixed> $properties event properties; lists and objects are allowed
     * @param array<string, mixed> $contactProperties contact attributes updated with the event
     */
    public function __construct(
        public string $name,
        public array $identifiers,
        public array $properties = [],
        public array $contactProperties = [],
        public ?\DateTimeInterface $date = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'event_name' => $this->name,
            'identifiers' => $this->identifiers,
            'event_properties' => $this->properties,
            'contact_properties' => $this->contactProperties,
            'event_date' => $this->date?->format(\DATE_ATOM),
        ], static fn (mixed $value): bool => null !== $value && [] !== $value);
    }
}
