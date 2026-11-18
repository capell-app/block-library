@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $questions = is_array($meta['questions'] ?? null) ? $meta['questions'] : [];
    $firstOpen = (bool) ($meta['first_open'] ?? false);
    $group = 'faq-' . substr(hash('xxh128', (string) json_encode([$questions, $meta['capell_block_library_instance'] ?? 0], JSON_INVALID_UTF8_SUBSTITUTE)), 0, 8);
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-faq']) }}
    x-data="{ openQuestion: {{ $firstOpen ? '0' : 'null' }} }"
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

    <div class="space-y-3">
        @foreach ($questions as $question)
            <article class="rounded-lg border border-slate-200 bg-white p-5">
                <button
                    type="button"
                    class="w-full cursor-pointer text-left font-semibold"
                    :aria-expanded="openQuestion === {{ $loop->index }} ? 'true' : 'false'"
                    @if (filled($question['answer'] ?? null))
                        aria-controls="{{ $group }}-panel-{{ $loop->index }}"
                    @endif
                    @click="openQuestion = openQuestion === {{ $loop->index }} ? null : {{ $loop->index }}"
                >
                    {{ $question['question'] ?? '' }}
                </button>

                @if (filled($question['answer'] ?? null))
                    <div
                        id="{{ $group }}-panel-{{ $loop->index }}"
                        class="prose mt-3 max-w-none text-slate-700"
                        x-show="openQuestion === {{ $loop->index }}"
                        x-cloak
                    >
                        @safeBlockHtml($question['answer'])
                    </div>
                @endif
            </article>
        @endforeach
    </div>
</section>
