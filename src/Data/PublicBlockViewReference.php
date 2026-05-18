<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

use InvalidArgumentException;

final class PublicBlockViewReference
{
    public function __construct(public readonly string $view)
    {
        throw_if(trim($this->view) === '', InvalidArgumentException::class, 'Public block view cannot be empty.');

        if ($this->isAdminView($this->view)) {
            throw new InvalidArgumentException(sprintf('Public block view [%s] cannot reference admin or Filament views.', $this->view));
        }
    }

    public static function from(string $view): self
    {
        return new self($view);
    }

    public function value(): string
    {
        return $this->view;
    }

    private function isAdminView(string $view): bool
    {
        $normalizedView = strtolower($view);

        return str_contains($normalizedView, 'filament')
            || str_contains($normalizedView, 'admin::')
            || str_contains($normalizedView, '::admin.')
            || str_contains($normalizedView, '::admin-');
    }
}
