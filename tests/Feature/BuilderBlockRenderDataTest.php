<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\BuildBuilderBlockRenderDataAction;
use Capell\BlockLibrary\Filament\BuilderBlocks\BuilderBlockMediaFields;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\BuilderBlockFields;
use Capell\BlockLibrary\View\Components\BuilderBlock;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

it('keeps the render allowlist aligned with every builder schema', function (): void {
    foreach (resolve(BuilderBlockDiscovery::class)->filamentBlocks() as $block) {
        $type = $block->getName();
        if ($type === 'content') {
            continue;
        }

        $fields = [];
        $components = $block->getDefaultChildComponents();
        if ($components instanceof Schema) {
            $components = $components->getComponents();
        }

        foreach ($components as $field) {
            if (! $field instanceof Field) {
                continue;
            }

            $fields[] = $field->getName();
            if ($field instanceof Repeater) {
                $nested = [];
                $children = $field->getDefaultChildComponents();
                if ($children instanceof Schema) {
                    $children = $children->getComponents();
                }

                foreach ($children as $child) {
                    if ($child instanceof Field) {
                        $nested[] = $child->getName();
                    }
                }

                expect($nested)->toEqualCanonicalizing(BuilderBlockFields::repeaters($type)[$field->getName()]);
            }
        }

        expect($fields)->toEqualCanonicalizing(['title', 'summary', ...BuilderBlockFields::scalars($type), ...array_keys(BuilderBlockFields::repeaters($type))]);
    }
});

it('normalises UUID repeaters, sanitises rich text and rejects unsafe links and enums', function (): void {
    $data = BuildBuilderBlockRenderDataAction::run('features', [
        'title' => ['invalid'], 'summary' => '<p>Safe</p><script>alert(1)</script>',
        'columns' => 'private-class', '__capell' => ['id' => 'secret'],
        'features' => ['private-uuid' => ['heading' => 'Feature', 'description' => 'Text', 'url' => 'javascript:alert(1)', 'id' => 42]],
    ]);
    expect($data->title)->toBe('')->and($data->meta['columns'])->toBe('3')
        ->and($data->meta['features'])->toBe([['heading' => 'Feature', 'description' => 'Text', 'url' => null]]);
    $dom = new DOMDocument;
    @$dom->loadHTML('<html><body>' . $data->summary . '</body></html>');
    expect($dom->getElementsByTagName('script'))->toHaveCount(0)
        ->and($dom->getElementsByTagName('p')->item(0)?->textContent)->toBe('Safe');
});

it('allowlists public URL schemes', function (string $url, string $expected): void {
    expect(BuildBuilderBlockRenderDataAction::make()->url($url))->toBe($expected);
})->with([
    ['/about', '/about'], ['https://example.test/path', 'https://example.test/path'],
    ['mailto:hi@example.test', 'mailto:hi@example.test'], ['tel:+441234', 'tel:+441234'], ['#plans', '#plans'], ['/catalogue?key=gallery', '/catalogue?key=gallery'],
    ['javascript:alert(1)', ''], ['data:text/html,evil', ''], ['//evil.test', ''],
    ["java\nscript:alert(1)", ''], ['/admin/pages?signature=secret', ''], ['/_capell/editor', ''],
]);

it('removes signed editor URLs and identifiers from nested rich text', function (): void {
    $data = BuildBuilderBlockRenderDataAction::run('accordion', ['summary' => '<section id="private-instance"><a href="/admin/pages?signature=secret">Read</a></section>', 'items' => [
        'private-uuid' => ['heading' => 'Question', 'content' => '<a href="/_capell/editor?signature=secret">Answer</a><img src="/photo?token=secret">'],
    ]]);
    $dom = new DOMDocument;
    @$dom->loadHTML('<html><body>' . $data->summary . '</body></html>');
    expect($dom->getElementsByTagName('section')->item(0)?->hasAttribute('id'))->toBeFalse()
        ->and($dom->getElementsByTagName('a')->item(0)?->hasAttribute('href'))->toBeFalse();
    expect(json_encode($data, JSON_THROW_ON_ERROR))->not->toContain('secret')->not->toContain('private-instance')->not->toContain('private-uuid')->not->toContain('/_capell');
});

it('renders hydrated catalog data without any database query', function (): void {
    $data = BuildBuilderBlockRenderDataAction::run('logos', ['logos' => [['name' => 'Ada', 'image' => 'private-media-reference']]], [
        'private-media-reference' => ['url' => '/images/ada.png', 'alt' => 'Ada at work'],
    ]);
    $queries = [];
    DB::listen(static function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    $component = new BuilderBlock('logos', $data);
    $html = $component->render()->render();
    $dom = new DOMDocument;
    @$dom->loadHTML('<html><body>' . $html . '</body></html>');
    expect($dom->getElementsByTagName('img')->item(0)?->getAttribute('alt'))->toBe('Ada at work')
        ->and($queries)->toBe([]);
});

it('never emits an empty href for logos and actions whose link was rejected', function (string $type, array $state): void {
    $html = new BuilderBlock($type, BuildBuilderBlockRenderDataAction::run($type, $state))->render()->render();
    $dom = new DOMDocument;
    @$dom->loadHTML('<html><body>' . $html . '</body></html>');
    foreach ($dom->getElementsByTagName('a') as $anchor) {
        expect($anchor->getAttribute('href'))->not->toBe('')->and($anchor->getAttribute('href'))->not->toContain('javascript');
    }

    expect($dom->getElementsByTagName('a')->length)->toBeGreaterThan(0);
})->with([
    'logos' => ['logos', ['logos' => [['name' => 'Initech', 'url' => 'javascript:alert(1)']]]],
    'call to action' => ['call_to_action', ['actions' => [['label' => 'Start', 'url' => 'data:text/html,x']]]],
]);

it('rejects credential query families in structured links rich text and hydrated images', function (string $parameter): void {
    $url = 'https://cdn.example.test/photo?' . $parameter . '=' . ($parameter === 'key' ? 'a31f9d7c0e82b4569f12d38ac4e670bd' : 'private-credential');
    $data = BuildBuilderBlockRenderDataAction::run('logos', [
        'url' => $url,
        'summary' => '<a href="' . $url . '">Read</a><img src="' . $url . '">',
        'logos' => [['name' => 'Partner', 'url' => $url, 'image' => 'image-reference']],
    ], ['image-reference' => ['url' => $url, 'alt' => 'Partner']]);
    $html = new BuilderBlock('logos', $data)->render()->render();
    expect($data->url)->toBe('')->and(data_get($data->meta, 'logos.0.url'))->toBeNull()
        ->and(data_get($data->meta, 'logos.0.image'))->toBeNull()
        ->and($html)->not->toContain('private-credential')->not->toContain('href=""');
})->with(['access_token', 'id_token', 'refresh_token', 'token', 'api_key', 'apikey', 'key', 'sig', 'signature', 'X-Amz-Signature', 'X-Amz-Credential', 'X-Amz-Security-Token', 'X-Amz-Expires', 'X-Amz-Date', 'X-Goog-Signature', 'X-Goog-Credential', 'Expires', 'se', 'sp', 'sv', 'sr', 'policy', 'key-pair-id', 'hmac', 'auth', 'authorization', 'password', 'secret', 'bearer', 'session', 'AcCeSs_ToKeN', '%61ccess_token', 'X%2dAmz%2dSignature', '%2573ig', 'authorization%5Bvalue%5D', 'client_secret', 'oauth_token', 'credentials%5Btoken%5D']);

it('enforces configured upload bytes as kilobytes through the real media field validation', function (): void {
    config(['files.max_upload_mb' => 2]);
    [, $field] = BuilderBlockMediaFields::uploads();
    $schema = Schema::make(new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    })->statePath('data')->components([$field]);
    $schema->fill();

    $rules = $field->getValidationRules();
    Storage::fake('local');
    $uploads = [];
    foreach ([1024, 3072] as $size) {
        $name = 'upload-size=' . ($size * 1024) . '.png';
        Storage::disk('local')->put('livewire-tmp/' . $name, UploadedFile::fake()->image('upload.png')->getContent());
        $uploads[] = new TemporaryUploadedFile($name, 'local');
    }

    [$below, $above] = $uploads;
    expect(Validator::make(['file' => [$below]], ['file' => $rules])->passes())->toBeTrue()
        ->and(Validator::make(['file' => [$above]], ['file' => $rules])->passes())->toBeFalse();
});

it('preserves component boundaries while rejecting encoded fragment camelcase and nested credentials', function (string $url): void {
    $data = BuildBuilderBlockRenderDataAction::run('logos', [
        'url' => $url,
        'summary' => '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">Read</a><img src="' . htmlspecialchars($url, ENT_QUOTES) . '">',
        'logos' => [['name' => 'Partner', 'url' => $url, 'image' => 'image-reference']],
    ], ['image-reference' => ['url' => $url, 'alt' => 'Partner']]);
    $html = new BuilderBlock('logos', $data)->render()->render();
    $document = new DOMDocument;
    $document->loadHTML('<html><body>' . $html . '</body></html>', LIBXML_NOERROR | LIBXML_NOWARNING);
    expect($data->url)->toBe('')->and(data_get($data->meta, 'logos.0.url'))->toBeNull()
        ->and(data_get($data->meta, 'logos.0.image'))->toBeNull()
        ->and(new DOMXPath($document)->query('//a[@href and @href!="#"] | //img[@src]'))->toHaveCount(0)
        ->and($html)->not->toContain('private');
})->with([
    '/photo?caption=%23&access_token=private',
    '/photo?caption=%2523&X-Amz-Signature=private',
    '/photo?caption=%23&credentials%5Btoken%5D=private',
    '#access_token=private',
    '/photo?accessToken=private',
    '/photo?redirect=https%3A%2F%2Fexample.test%2Fimage%3Faccess_token%3Dprivate',
    '/photo?redirect=%252Fimage%253Fcredentials%255Btoken%255D%253Dprivate',
    '/photo#route?credentials%5Btoken%5D=private',
    '#access_token=private?caption=x',
    '/photo?redirect=' . rawurlencode('/photo?caption=%23&access_token=private'),
    '/photo?redirect=' . rawurlencode('/photo?redirect=' . rawurlencode('/photo?accessToken=private')),
]);

it('retains legitimate public route segments and descriptive catalogue keys', function (string $url): void {
    expect(BuildBuilderBlockRenderDataAction::make()->url($url))->toBe($url);
})->with(['/guide/administration', '/catalogue?key=gallery-of-coastal-birds', '/photo?v=123456&width=1200&height=800&fit=cover&format=webp&quality=80&dpr=2', '/photo?key=' . str_repeat('a', 32)]);

it('rejects actual protected segments and high entropy keys', function (string $url): void {
    expect(BuildBuilderBlockRenderDataAction::make()->url($url))->toBe('');
})->with(['/guide/admin/pages', '/%61dmin/pages', '/photo?key=a31f9d7c0e82b4569f12d38ac4e670bd', '/photo?credentials[key]=private']);

it('rejects deeply encoded nested credentials at the decode budget boundary', function (int $layers): void {
    $nested = 'https://cdn.example.test/image?access_token=private';
    for ($layer = 0; $layer < $layers; $layer++) {
        $nested = rawurlencode($nested);
    }

    expect(BuildBuilderBlockRenderDataAction::make()->url('/photo?redirect=' . $nested))->toBe('');
})->with([7, 8, 9, 10]);

it('accepts legitimate encoded redirects', function (int $layers): void {
    $nested = 'https://cdn.example.test/image?width=1200&fit=cover';
    for ($layer = 0; $layer < $layers; $layer++) {
        $nested = rawurlencode($nested);
    }

    $url = '/photo?redirect=' . $nested;
    expect(BuildBuilderBlockRenderDataAction::make()->url($url))->toBe($url);
})->with([1, 2]);
