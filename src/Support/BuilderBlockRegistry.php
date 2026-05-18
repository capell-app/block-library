<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Support;

use Capell\ContentBlocks\Enums\BuilderBlockTarget;
use InvalidArgumentException;

final class BuilderBlockRegistry
{
    /** @var array<string, array<string, string>> */
    private array $definitions = [];

    public function register(string $name, BuilderBlockTarget|string $target, string $component): void
    {
        $normalizedName = trim($name);
        $normalizedTarget = $this->normalizeTarget($target);
        $normalizedComponent = trim($component);

        throw_if($normalizedName === '', InvalidArgumentException::class, 'Builder block name cannot be empty.');
        throw_if($normalizedTarget === '', InvalidArgumentException::class, 'Builder block target cannot be empty.');
        throw_if($normalizedComponent === '', InvalidArgumentException::class, 'Builder block component cannot be empty.');

        $this->definitions[$normalizedName][$normalizedTarget] = $normalizedComponent;
    }

    public function get(string $name, BuilderBlockTarget|string $target): ?string
    {
        $normalizedName = trim($name);
        $normalizedTarget = $this->normalizeTarget($target);

        throw_if($normalizedName === '', InvalidArgumentException::class, 'Builder block name cannot be empty.');
        throw_if($normalizedTarget === '', InvalidArgumentException::class, 'Builder block target cannot be empty.');

        return $this->definitions[$normalizedName][$normalizedTarget] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function allForTarget(BuilderBlockTarget|string $target): array
    {
        $normalizedTarget = $this->normalizeTarget($target);

        throw_if($normalizedTarget === '', InvalidArgumentException::class, 'Builder block target cannot be empty.');

        return collect($this->definitions)
            ->mapWithKeys(
                static fn (array $targets, string $name): array => isset($targets[$normalizedTarget])
                    ? [$name => $targets[$normalizedTarget]]
                    : [],
            )
            ->all();
    }

    private function normalizeTarget(BuilderBlockTarget|string $target): string
    {
        return $target instanceof BuilderBlockTarget ? $target->value : trim($target);
    }
}
