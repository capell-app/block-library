@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $items = is_array($meta['items'] ?? null) ? $meta['items'] : [];
    $firstOpen = (bool) ($meta['first_open'] ?? false);
    $group = 'accordion-' . substr(hash('xxh128', (string) json_encode([$items, $meta['capell_block_library_instance'] ?? 0], JSON_INVALID_UTF8_SUBSTITUTE)), 0, 8);
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-accordion']) }}
    x-data="{ openPanel: {{ $firstOpen ? '0' : 'null' }} }"
>
    @if ($title || $summary)
        <header class="mb-6">
            @if ($title)
                <h2 class="text-3xl font-bold">{{ $title }}</h2>
            @endif

            @if ($summary)
                <div class="mt-3 text-lg opacity-80">
                    @safeBlockHtml($summary)
                </div>
            @endif
        </header>
    @endif

    <div
        class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white"
    >
        @foreach ($items as $item)
            <article class="p-5">
                <button
                    type="button"
                    class="flex w-full cursor-pointer items-center justify-between gap-4 text-left font-semibold"
                    :aria-expanded="openPanel === {{ $loop->index }} ? 'true' : 'false'"
                    @if (filled($item['content'] ?? null))
                        aria-controls="{{ $group }}-panel-{{ $loop->index }}"
                    @endif
                    @click="openPanel = openPanel === {{ $loop->index }} ? null : {{ $loop->index }}"
                >
                    <span>{{ $item['heading'] ?? '' }}</span>
                    <span
                        class="text-xl leading-none transition"
                        :class="{ 'rotate-45': openPanel === {{ $loop->index }} }"
                        aria-hidden="true"
                    >
                        +
                    </span>
                </button>

                @if (filled($item['content'] ?? null))
                    <div
                        id="{{ $group }}-panel-{{ $loop->index }}"
                        class="prose mt-4 max-w-none text-slate-700"
                        x-show="openPanel === {{ $loop->index }}"
                        x-cloak
                    >
                        @safeBlockHtml($item['content'])
                    </div>
                @endif
            </article>
        @endforeach
    </div>
</section>
