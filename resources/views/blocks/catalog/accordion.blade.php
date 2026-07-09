@props (['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $items = is_array($meta['items'] ?? null) ? $meta['items'] : [];
    $firstOpen = (bool) ($meta['first_open'] ?? false);
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
                <div class="mt-3 text-lg opacity-80">{!! $summary !!}</div>
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
                    aria-controls="accordion-panel-{{ $loop->index }}"
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
                        id="accordion-panel-{{ $loop->index }}"
                        class="prose mt-4 max-w-none text-slate-700"
                        x-show="openPanel === {{ $loop->index }}"
                        x-cloak
                    >
                        {!! $item['content'] !!}
                    </div>
                @endif
            </article>
        @endforeach
    </div>
</section>
