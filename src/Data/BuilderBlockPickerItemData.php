<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Data;

final class BuilderBlockPickerItemData
{
    /**
     * @param  class-string  $builderBlockClass
     * @param  list<string>  $searchTerms
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly string $category,
        public readonly string $icon,
        public readonly array $searchTerms,
        public readonly string $builderBlockClass,
    ) {}

    /**
     * @return array{key: string, label: string, description: string, category: string, icon: string, searchTerms: list<string>, builderBlockClass: class-string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'category' => $this->category,
            'icon' => $this->icon,
            'searchTerms' => $this->searchTerms,
            'builderBlockClass' => $this->builderBlockClass,
        ];
    }
}
