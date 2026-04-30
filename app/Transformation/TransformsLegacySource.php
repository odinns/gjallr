<?php

declare(strict_types=1);

namespace App\Transformation;

interface TransformsLegacySource
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function transform(array $payload): array;
}
