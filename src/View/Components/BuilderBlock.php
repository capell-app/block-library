<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\View\Components;

use Capell\BlockLibrary\Data\BuilderBlockRenderData;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Override;

final class BuilderBlock extends Component
{
    private const string INSTANCE_ATTRIBUTE = 'capell.block-library.instance';

    public function __construct(public readonly string $type, public readonly BuilderBlockRenderData $renderData) {}

    #[Override]
    public function render(): View
    {
        $view = DefaultBlockCatalog::viewName($this->type);
        if (! view()->exists($view)) {
            throw new InvalidArgumentException("Builder block view [{$view}] not found.");
        }

        // Identical blocks on one page still need distinct element ids, so each render gets a request-scoped sequence number.
        $request = request();
        $instance = $request->attributes->getInt(self::INSTANCE_ATTRIBUTE) + 1;
        $request->attributes->set(self::INSTANCE_ATTRIBUTE, $instance);

        return view($view, [
            'asset' => null,
            'title' => $this->renderData->title,
            'summary' => $this->renderData->summary,
            'url' => $this->renderData->url,
            'linkText' => $this->renderData->linkText,
            'meta' => $this->renderData->meta + ['capell_block_library_instance' => $instance],
            'attributes' => new ComponentAttributeBag,
        ]);
    }
}
