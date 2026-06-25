<?php

namespace App\Http\Controllers;

use App\Services\GeoEngine\Engine;
use Illuminate\Http\Request;

/**
 * 放置：项目根 /app/Http/Controllers/GeoEngineController.php
 * 纯新增，不影响任何现有控制器。
 */
class GeoEngineController extends Controller
{
    public function index()
    {
        return view('geoengine.generate', [
            'packs' => Engine::packs(),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'keyword' => 'required|string|max:50',
            'pack' => 'required|string',
            'subject' => 'nullable|string|max:50',
            'count' => 'required|integer|min:1|max:10',
        ]);

        try {
            $result = Engine::generate(
                $data['keyword'],
                (int) $data['count'],
                $data['pack'],
                $data['subject'] ?? ''
            );

            return response()->json(['ok' => true] + $result);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
