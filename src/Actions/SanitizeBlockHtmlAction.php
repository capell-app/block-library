<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\Core\Support\Security\PublicHtmlSanitizer;
use Illuminate\Contracts\Support\Htmlable;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class SanitizeBlockHtmlAction
{
    use AsFake;
    use AsObject;

    public function handle(mixed $html): string
    {
        $value = $html instanceof Htmlable ? $html->toHtml() : $html;

        return resolve(PublicHtmlSanitizer::class)->sanitize(is_string($value) ? $value : '');
    }
}
