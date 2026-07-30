@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $tabs = is_array($meta['tabs'] ?? null) ? array_values($meta['tabs']) : [];
    $group = 'section-tabs-' . substr(hash('xxh128', (string) json_encode($tabs, JSON_INVALID_UTF8_SUBSTITUTE)), 0, 8);
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-tabs']) }}
    x-data="{ activeTab: 0 }"
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

    <div class="rounded-lg border border-slate-200 bg-white p-2">
        <div
            class="flex flex-wrap gap-2"
            role="tablist"
        >
            @foreach ($tabs as $tab)
                <button
                    type="button"
                    class="rounded px-4 py-2 font-semibold text-slate-600"
                    :class="{ 'bg-slate-950 text-white': activeTab === {{ $loop->index }} }"
                    id="{{ $group }}-tab-{{ $loop->index }}"
                    role="tab"
                    :aria-selected="activeTab === {{ $loop->index }} ? 'true' : 'false'"
                    aria-controls="{{ $group }}-panel-{{ $loop->index }}"
                    @click="activeTab = {{ $loop->index }}"
                >
                    {{ $tab['label'] ?? '' }}
                </button>
            @endforeach
        </div>

        <div class="mt-4 space-y-3">
            @foreach ($tabs as $tab)
                <article
                    id="{{ $group }}-panel-{{ $loop->index }}"
                    class="rounded bg-slate-50 p-5"
                    role="tabpanel"
                    aria-labelledby="{{ $group }}-tab-{{ $loop->index }}"
                    x-show="activeTab === {{ $loop->index }}"
                    x-cloak
                >
                    <h3 class="mb-2 font-semibold">
                        {{ $tab['label'] ?? '' }}
                    </h3>
                    <div class="prose max-w-none">
                        @safeBlockHtml($tab['content'] ?? '')
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
