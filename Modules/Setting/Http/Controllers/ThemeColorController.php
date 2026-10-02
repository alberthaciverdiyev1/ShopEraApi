<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Setting\Services\ThemeColorService;

class ThemeColorController extends Controller
{
    public function __construct(private readonly ThemeColorService $service)
    {
        // Reading the palette is public (the storefront needs it); changing it is not.
        $this->middleware('auth:sanctum')->only('update');
        $this->middleware('permission:update theme')->only('update');
    }

    public function show()
    {
        return $this->service->all();
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'colors' => ['required', 'array'],
            'colors.*' => ['required', 'string', 'max:191'],
        ]);

        return $this->service->update($validated['colors']);
    }
}
