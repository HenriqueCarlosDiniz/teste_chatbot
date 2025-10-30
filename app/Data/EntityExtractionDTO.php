<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Nullable;

/**
 * DTO para armazenar as entidades extraídas da mensagem do usuário.
 */
class EntityExtractionDTO extends Data
{
    public function __construct(
        #[Nullable]
        public ?string $name,
        #[Nullable]
        public ?string $phone,
        #[Nullable]
        public ?string $date,
        #[Nullable]
        public ?string $time,
        #[Nullable]
        public ?string $location_type, // ex: 'state', 'city', 'cep'
        #[Nullable]
        public ?string $location_value,
        #[Nullable]
        public ?string $unit_name,
        #[Nullable]
        public ?string $correction_field,
    ) {}
}
