<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Support;

/** The persisted builder vocabulary, including nested repeater fields. */
final class BuilderBlockFields
{
    /** @return array<string, list<string>> */
    public static function repeaters(string $type): array
    {
        return match ($type) {
            'accordion' => ['items' => ['heading', 'content']],
            'call_to_action' => ['actions' => ['label', 'url']],
            'comparison' => ['columns' => ['heading', 'description', 'highlighted'], 'rows' => ['label', 'values']],
            'counter' => ['counters' => ['value', 'prefix', 'suffix', 'label', 'description']],
            'faq' => ['questions' => ['question', 'answer']],
            'features' => ['features' => ['heading', 'description', 'url']],
            'logos' => ['logos' => ['name', 'url', 'image']],
            'pricing' => ['plans' => ['name', 'price', 'period', 'description', 'features', 'highlighted', 'action_label', 'action_url']],
            'stats' => ['stats' => ['value', 'label', 'description']],
            'table' => ['headers' => ['label'], 'rows' => ['cells']],
            'tabs' => ['tabs' => ['label', 'content']],
            'team' => ['members' => ['name', 'role', 'bio', 'url', 'image']],
            'timeline' => ['milestones' => ['date', 'heading', 'description']],
            default => [],
        };
    }

    /** @return list<string> */
    public static function scalars(string $type): array
    {
        return match ($type) {
            'accordion', 'faq' => ['first_open'],
            'call_to_action' => ['alignment'],
            'divider' => ['style'],
            'features', 'stats' => ['columns'],
            'hero' => ['alignment', 'url'],
            'logos', 'team' => ['columns', 'media_key', 'block_images'],
            'table' => ['caption'],
            'testimonial' => ['quote', 'author', 'role'],
            default => [],
        };
    }
}
