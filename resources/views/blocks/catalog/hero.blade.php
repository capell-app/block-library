@props(['asset', 'meta' => [], 'summary' => null, 'title' => null, 'url' => null])

@php
    $alignment = $meta['alignment'] ?? 'center';
    $headingLevel = \Capell\BlockLibrary\Enums\BlockHeadingLevel::tryFrom((string) ($meta['heading_level'] ?? ''))?->value ?? 'h1';
    $buttonLabel = filled($meta['button_label'] ?? null) ? $meta['button_label'] : __('capell-block-library::blocks.buttons.read_more');
@endphp

<section
    @class([
    'capell-section-hero',
    'section section-hero rounded-lg bg-slate-950 p-10 text-white',
    'text-left' => $alignment === 'start',
    'text-center' => $alignment === 'center',
    'text-right' => $alignment === 'end',
    $attributes->get('class'),
])
>
    @if ($title)
        <{{ $headingLevel }}
            class="text-5xl font-bold tracking-tight"
            >{{ $title }}</{{ $headingLevel }}
        >
    @endif

    @if ($summary)
        <div class="mx-auto mt-5 max-w-3xl text-xl text-slate-200">
            @safeBlockHtml($summary)
        </div>
    @endif

    @if ($url)
        <a
            href="{{ $url }}"
            class="mt-8 inline-flex rounded bg-white px-5 py-3 font-semibold text-slate-950"
        >
            {{ $buttonLabel }}
        </a>
    @endif
</section>
