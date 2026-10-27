<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use DOMDocument;
use DOMElement;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** Apply the builder URL policy to rich text as well as the structured link fields. */
final class SanitizeBuilderBlockHtmlAction
{
    use AsFake;
    use AsObject;

    public function handle(mixed $value): string
    {
        $html = SanitizeBlockHtmlAction::run($value);
        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        foreach ($document->getElementsByTagName('*') as $element) {
            $element->removeAttribute('id');
            foreach (['href', 'src'] as $attribute) {
                if ($element->hasAttribute($attribute) && BuildBuilderBlockRenderDataAction::make()->url($element->getAttribute($attribute)) === '') {
                    $element->removeAttribute($attribute);
                }
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body instanceof DOMElement) {
            return '';
        }

        $result = '';
        foreach ($body->childNodes as $node) {
            $result .= $document->saveHTML($node);
        }

        return $result;
    }
}
