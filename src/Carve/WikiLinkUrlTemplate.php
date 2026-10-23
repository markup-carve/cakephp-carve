<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Carve;

final class WikiLinkUrlTemplate
{
    public function __construct(private readonly string $template)
    {
    }

    public function __invoke(string $page): string
    {
        $slug = strtolower(trim($page));
        $slug = preg_replace('/\s+/', '-', $slug) ?? $slug;
        $slug = preg_replace('/[^a-z0-9\-_\/]/', '', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;

        return str_replace('{page}', $slug, $this->template);
    }
}
