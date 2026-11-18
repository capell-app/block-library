<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\BuildBuilderBlockRenderDataAction;
use Capell\BlockLibrary\Actions\FormatPlainTextBlockAction;
use Capell\BlockLibrary\View\Components\BuilderBlock;

/** @param  array<string, mixed>  $state */
function renderBuilderBlock(string $type, array $state): string
{
    return new BuilderBlock($type, BuildBuilderBlockRenderDataAction::run($type, $state))->render()->render();
}

function blockDom(string $html): DOMDocument
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?><html><body>' . $html . '</body></html>');

    return $dom;
}

it('turns blank lines in plain text into paragraphs and single newlines into line breaks', function (): void {
    $html = FormatPlainTextBlockAction::run("First line\nsecond line\r\n\r\nSecond paragraph\n \nThird & fourth");

    expect($html)->toBe('<p>First line<br>' . "\n" . 'second line</p><p>Second paragraph</p><p>Third &amp; fourth</p>');
});

it('leaves values that already contain markup untouched', function (): void {
    expect(FormatPlainTextBlockAction::run("<p>One</p>\n\n<p>Two</p>"))->toBe("<p>One</p>\n\n<p>Two</p>")
        ->and(FormatPlainTextBlockAction::run(null))->toBe('')
        ->and(FormatPlainTextBlockAction::run("  \n "))->toBe('')
        ->and(FormatPlainTextBlockAction::run('1 < 2 and 3 > 2'))->toBe('<p>1 &lt; 2 and 3 &gt; 2</p>')
        ->and(FormatPlainTextBlockAction::run("if a <b then\n\nc"))->toBe('<p>if a &lt;b then</p><p>c</p>');
});

it('renders plain text summary, answer and content with their paragraph breaks', function (): void {
    $faq = blockDom(renderBuilderBlock('faq', [
        'summary' => "Intro one\n\nIntro two",
        'questions' => [['question' => 'Why?', 'answer' => "Because.\n\nAlso & this.\nOn a new line."]],
    ]));
    $accordion = blockDom(renderBuilderBlock('accordion', ['items' => [['heading' => 'One', 'content' => "A\n\nB"]]]));

    expect($faq->getElementsByTagName('p')->length)->toBe(4)
        ->and($faq->textContent)->toContain('Also & this.')
        ->and($faq->getElementsByTagName('br'))->toHaveCount(1)
        ->and($accordion->getElementsByTagName('p'))->toHaveCount(2);
});

it('keeps line breaks in plain description, bio and quote fields', function (string $type, array $state, string $needle): void {
    $html = renderBuilderBlock($type, $state);

    expect($html)->toContain($needle)->toContain('whitespace-pre-line');
})->with([
    'feature' => ['features', ['features' => [['heading' => 'F', 'description' => "One\nTwo"]]], "One\nTwo"],
    'team bio' => ['team', ['members' => [['name' => 'Ada', 'bio' => "Line one\n\nLine two"]]], "Line one\n\nLine two"],
    'quote' => ['testimonial', ['quote' => "Great\nproduct", 'author' => 'Ada'], "Great\nproduct"],
]);

it('falls back to the summary when a testimonial has no quote', function (): void {
    expect(renderBuilderBlock('testimonial', ['quote' => '', 'summary' => 'From summary', 'author' => 'Ada']))->toContain('From summary');
});

it('renders the hero heading at the configured level and defaults to h1', function (string $level, string $expected): void {
    $dom = blockDom(renderBuilderBlock('hero', ['title' => 'Welcome', 'heading_level' => $level]));

    expect($dom->getElementsByTagName($expected))->toHaveCount(1)
        ->and($dom->getElementsByTagName($expected)->item(0)?->textContent)->toBe('Welcome')
        ->and(BuildBuilderBlockRenderDataAction::run('hero', ['heading_level' => $level])->meta['heading_level'])->toBe($expected);
})->with([
    'h2' => ['h2', 'h2'],
    'h6' => ['h6', 'h6'],
    'empty' => ['', 'h1'],
    'unlisted tag' => ['script', 'h1'],
    'injection' => ['h2 onclick=alert(1)', 'h1'],
    'out of range' => ['h7', 'h1'],
]);

function firstAnchorText(DOMDocument $dom): ?string
{
    $anchor = $dom->getElementsByTagName('a')->item(0);

    return $anchor instanceof DOMElement ? trim($anchor->textContent) : null;
}

it('labels the hero button with the editable text and falls back to the read more string', function (): void {
    $custom = blockDom(renderBuilderBlock('hero', ['title' => 'Hi', 'url' => '/start', 'button_label' => 'Start free trial']));
    $fallback = blockDom(renderBuilderBlock('hero', ['title' => 'Hi', 'url' => '/start', 'button_label' => '  ']));
    $none = blockDom(renderBuilderBlock('hero', ['title' => 'Hi', 'button_label' => 'Orphan label']));

    expect(firstAnchorText($custom))->toBe('Start free trial')
        ->and(firstAnchorText($fallback))->toBe(__('capell-block-library::blocks.buttons.read_more'))
        ->and($none->getElementsByTagName('a'))->toHaveCount(0);
});

it('keeps authoring state out of the hero output', function (): void {
    $html = renderBuilderBlock('hero', ['title' => 'Hi', 'url' => '/start', 'heading_level' => 'h3', 'button_label' => 'Go']);

    expect($html)->not->toContain('heading_level')->not->toContain('button_label')->not->toContain('wire:');
});

it('still sanitises markup typed into a plain text field', function (): void {
    $dom = blockDom(renderBuilderBlock('faq', ['questions' => [['question' => 'Why?', 'answer' => "Line\n\n<script>alert(1)</script><a href=\"javascript:alert(1)\">x</a>"]]]));

    expect($dom->getElementsByTagName('script'))->toHaveCount(0)
        ->and($dom->saveHTML())->not->toContain('javascript:');
});
