<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\Core\Support\Security\PublicHtmlSanitizer;
use Lorisleiva\Actions\Concerns\AsObject;

final class SanitizeBlockHtmlAction
{
    use AsObject;

    public function handle(mixed $html): string
    {
        return resolve(PublicHtmlSanitizer::class)->sanitize(is_string($html) ? $html : '');
    }
}
