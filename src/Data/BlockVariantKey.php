<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

use InvalidArgumentException;
use Stringable;

final class BlockVariantKey implements Stringable
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalizedValue = trim($value);

        if ($normalizedValue === '' || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalizedValue) !== 1) {
            throw new InvalidArgumentException(sprintf('Block variant key [%s] must be a kebab-case slug.', $value));
        }

        $this->value = $normalizedValue;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function from(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
