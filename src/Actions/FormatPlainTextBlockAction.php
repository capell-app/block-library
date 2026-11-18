<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Textarea fields hold plain text, so their blank lines and line breaks must
 * survive as markup before the value reaches the HTML sanitiser. Values that
 * already contain markup are returned unchanged.
 */
final class FormatPlainTextBlockAction
{
    use AsFake;
    use AsObject;

    public function handle(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        if (preg_match('/<(?:\/?[a-z][^>]*|!--[\s\S]*?--)>/i', $value) === 1) {
            return $value;
        }

        $normalised = str_replace(["\r\n", "\r"], "\n", trim($value));
        $paragraphs = preg_split('/\n[ \t]*(?:\n[ \t]*)+/', $normalised) ?: [];

        return implode('', array_map(
            static fn (string $paragraph): string => '<p>' . nl2br(e(trim($paragraph)), false) . '</p>',
            array_filter($paragraphs, static fn (string $paragraph): bool => trim($paragraph) !== ''),
        ));
    }
}
