<?php

declare(strict_types=1);

namespace App\Domain\Content;

use Carbon\CarbonImmutable;

final readonly class ContentItem
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $bodyHtml,
        public ?CarbonImmutable $publishedAt = null,
    ) {}
}
