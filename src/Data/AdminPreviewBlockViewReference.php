<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

use InvalidArgumentException;

final class AdminPreviewBlockViewReference
{
    public function __construct(public readonly string $view)
    {
        throw_if(trim($this->view) === '', InvalidArgumentException::class, 'Admin preview block view cannot be empty.');
    }

    public static function from(string $view): self
    {
        return new self($view);
    }

    public function value(): string
    {
        return $this->view;
    }
}
