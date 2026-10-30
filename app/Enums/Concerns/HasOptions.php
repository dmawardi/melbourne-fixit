<?php

namespace App\Enums\Concerns;

trait HasOptions
{
    abstract public function label(): string;

    /**
     * Get the enum cases as value/label pairs for select inputs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
