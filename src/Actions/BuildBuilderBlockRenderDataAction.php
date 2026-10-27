<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\BlockLibrary\Data\BuilderBlockRenderData;
use Capell\BlockLibrary\Enums\BlockAlignment;
use Capell\BlockLibrary\Enums\BlockColumnCount;
use Capell\BlockLibrary\Enums\BlockDividerStyle;
use Capell\BlockLibrary\Support\BuilderBlockFields;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildBuilderBlockRenderDataAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, array{url: string, alt: ?string}>  $images  Hydrated, owner-scoped media only.
     */
    public function handle(string $type, array $state, array $images = []): BuilderBlockRenderData
    {
        $meta = [];
        foreach (BuilderBlockFields::scalars($type) as $field) {
            if (in_array($field, ['media_key', 'block_images', 'url'], true)) {
                continue;
            }

            $value = $state[$field] ?? null;
            if ($field === 'columns' && ! in_array($this->text($value), array_column(BlockColumnCount::cases(), 'value'), true)) {
                $value = in_array($type, ['logos', 'stats'], true) ? '4' : '3';
            }

            $meta[$field] = $this->value($field, $value);
        }

        foreach (BuilderBlockFields::repeaters($type) as $key => $fields) {
            $rows = [];
            foreach (is_array($state[$key] ?? null) ? array_values($state[$key]) : [] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $row = [];
                foreach ($fields as $field) {
                    if ($field === 'image') {
                        $reference = $item[$field] ?? null;
                        $image = is_string($reference) ? ($images[$reference] ?? null) : null;
                        $row[$field] = $image !== null && $this->url($image['url']) !== '' ? $image : null;
                    } else {
                        $value = $this->value($field, $item[$field] ?? null);
                        // An unsafe or empty link is absent, so the views never emit href="".
                        $row[$field] = in_array($field, ['url', 'action_url'], true) && $value === '' ? null : $value;
                    }
                }

                $rows[] = $row;
            }

            $meta[$key] = $rows;
        }

        return new BuilderBlockRenderData(
            title: $this->text($state['title'] ?? null),
            summary: SanitizeBuilderBlockHtmlAction::run($state['summary'] ?? null),
            url: $this->url($state['url'] ?? null),
            linkText: '',
            meta: $meta,
        );
    }

    public function url(mixed $value): string
    {
        $url = trim($this->text($value));
        if ($url === '' || preg_match('/[\\\\\x00-\x20\x7f]/', $url) === 1 || str_starts_with($url, '//')) {
            return '';
        }

        return $this->safeUrl($url) ? $url : '';
    }

    private function safeUrl(string $url, int $depth = 0): bool
    {
        // Establish component boundaries before decoding user-controlled values.
        $parts = parse_url($url);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https', 'mailto', 'tel'], true)) {
            return false;
        }

        $path = $this->decode($parts['path'] ?? '');
        if ($path === null || preg_match('~(?:^|/)(?:_capell|admin)(?:/|$)~i', $path) === 1) {
            return false;
        }

        $parameterSets = [$parts['query'] ?? '', $parts['fragment'] ?? ''];
        // Inspect both the complete fragment and a hash-router query; neither
        // may conceal credentials in the other by introducing a question mark.
        if (isset($parts['fragment']) && str_contains($parts['fragment'], '?')) {
            $parameterSets[] = explode('?', $parts['fragment'], 2)[1];
        }

        foreach ($parameterSets as $parameters) {
            foreach (preg_split('/[&;]/', $parameters) ?: [] as $parameter) {
                [$rawName, $rawValue] = array_pad(explode('=', $parameter, 2), 2, '');
                $name = $this->decode($rawName);
                $value = $this->decode($rawValue);
                if ($name === null || $value === null) {
                    return false;
                }

                $literalName = strtolower(preg_replace('/[^a-zA-Z0-9%]+/', '_', $name) ?? $name);
                $name = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $name) ?? $name;
                $name = strtolower(preg_replace('/[^a-zA-Z0-9%]+/', '_', $name) ?? $name);
                if (str_contains($name, '%') || preg_match('/(?:^x_(?:amz|goog)_|(?:^|_)(?:credentials?|access_token|id_token|refresh_token|token|api_key|apikey|sig|signature|expires|se|sp|sv|sr|policy|key_pair_id|hmac|auth|authorization|password|secret|bearer|session)(?:$|_))/', $name . '_' . $literalName) === 1 || str_starts_with($literalName, 'x_amz_') || str_starts_with($literalName, 'x_goog_')) {
                    return false;
                }

                if (preg_match('/(?:^|_)key(?:_|$)/', $name) === 1 && $this->credentialKey($value)) {
                    return false;
                }

                // Decode a nested URL only after separating its containing parameter.
                if (str_contains($value, '?') || str_contains($value, '#') || str_contains($value, '://')) {
                    if ($depth >= 4 || ! $this->safeUrl($value, $depth + 1)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function decode(string $value): ?string
    {
        for ($depth = 0; $depth < 8; $depth++) {
            $next = rawurldecode($value);
            if ($next === $value) {
                return $value;
            }

            $value = $next;
        }

        // Values still changing at the budget boundary may conceal nested
        // credentials or protected paths. Never publish partially decoded URLs.
        return rawurldecode($value) === $value ? $value : null;
    }

    private function credentialKey(string $value): bool
    {
        if (strlen($value) < 32 || preg_match('~^[A-Za-z0-9_+/=-]+$~D', $value) !== 1 || preg_match('/^[A-Za-z]+(?:-[A-Za-z]+)+$/D', $value) === 1) {
            return false;
        }

        $entropy = 0.0;
        foreach (count_chars($value, 1) as $count) {
            $probability = $count / strlen($value);
            $entropy -= $probability * log($probability, 2);
        }

        return $entropy >= 3.0;
    }

    private function value(string $field, mixed $value): string|bool
    {
        return match ($field) {
            'url', 'action_url' => $this->url($value),
            'alignment' => in_array($this->text($value), array_column(BlockAlignment::cases(), 'value'), true) ? $this->text($value) : BlockAlignment::Center->value,
            'columns' => in_array($this->text($value), array_column(BlockColumnCount::cases(), 'value'), true) ? $this->text($value) : BlockColumnCount::Three->value,
            'style' => in_array($this->text($value), array_column(BlockDividerStyle::cases(), 'value'), true) ? $this->text($value) : BlockDividerStyle::Line->value,
            'first_open', 'highlighted' => in_array($value, [true, 1, '1'], true),
            'content', 'answer' => SanitizeBuilderBlockHtmlAction::run($value),
            default => $this->text($value),
        };
    }

    private function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
