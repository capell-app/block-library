<?php

declare(strict_types=1);

use Capell\BlockLibrary\Support\BlockRegistry;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('/screenshot-fixtures/catalogue/block-library/content-block-registry-list', static fn (): Response => response()->view('capell-block-library::registry', [
    'definitions' => resolve(BlockRegistry::class)->all(),
]));
