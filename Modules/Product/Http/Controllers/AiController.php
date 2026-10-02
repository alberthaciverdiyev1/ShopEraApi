<?php

namespace Modules\Product\Http\Controllers;

use App\Jobs\ProcessPhotoAi;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\AiPhoto;

class AiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image',
        ]);

        $paths = [];
        $directory = TenantContext::storagePath('photos');
        foreach ($request->file('images') as $file) {
            $paths[] = $file->store($directory, 'public');
        }

        $photo = AiPhoto::create([
            'image_path' => $paths,
            'status' => 'pending',
        ]);

        ProcessPhotoAi::dispatch($photo->id);

        return response()->json(['id' => $photo->id, 'status' => 'Added to queue']);
    }

    public function check($id)
    {
        $photo = AiPhoto::findOrFail($id);

        return response()->json([
            'id' => $photo->id,
            'status' => $photo->status,
            'progress' => $photo->status === 'completed' ? 100 : 0,
        ]);
    }

    public function getData($id)
    {
        $photo = AiPhoto::findOrFail($id);

        if ($photo->status !== 'completed') {
            return response()->json(['message' => 'Analise not completed.'], 422);
        }

        return response()->json([
            'data' => $photo,
        ]);
    }
}
