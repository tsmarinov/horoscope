<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitySearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $cities = City::search($q)
            ->limit(8)
            ->get();

        return response()->json(
            $cities->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'country_code' => $c->country_code,
            ])
        );
    }
}
