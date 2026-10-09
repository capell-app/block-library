<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    />
    <title>Content block registry</title>
    <style>
        body { background: #f8fafc; color: #172033; font: 16px/1.5 system-ui, sans-serif; margin: 0; }
        main { margin: 0 auto; max-width: 1180px; padding: 56px 32px; }
        .eyebrow { color: #475569; font-size: .8rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        h1 { font-size: 2.4rem; margin: .25rem 0 .5rem; }
        .intro { color: #475569; max-width: 720px; }
        .registry { display: grid; gap: 16px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 32px; }
        .block-registry-item { background: white; border: 1px solid #cbd5e1; border-radius: 14px; padding: 20px; }
        .block-registry-item h2 { font-size: 1.1rem; margin: 0 0 8px; }
        .block-registry-item p { color: #475569; margin: 0; }
        @media(max-width: 760px)
        { .registry { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <main>
        <p class="eyebrow">Block Library</p>
        <h1>Content block registry</h1>
        <p class="intro">Registered reusable content blocks available to the page editor.</p>
        <section
            class="registry"
            aria-label="Registered content blocks"
        >
            @foreach ($definitions as $definition)
                <article class="block-registry-item">
                    <h2>{{ $definition->label }}</h2>
                    <p>{{ $definition->description }}</p>
                </article>
            @endforeach
        </section>
    </main>
</body>
</html>
