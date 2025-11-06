<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Nullable;

class LocationDTO extends Data
{
    public function __construct(
        #[Required]
        #[In(['state', 'city', 'neighborhood', 'cep', 'unknown'])]
        public string $type,

        #[Nullable]
        public ?string $value,
    ) {}
}
