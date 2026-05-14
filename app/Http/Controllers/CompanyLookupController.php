<?php

namespace App\Http\Controllers;

use App\Services\CompanyLookup\CompanyLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyLookupController extends Controller
{
    public function __invoke(Request $request, CompanyLookupService $lookup): JsonResponse
    {
        $validated = $request->validate([
            'inn' => ['required', 'string', 'regex:/^\d{10}(\d{2})?$/'],
        ], [
            'inn.regex' => 'ИНН должен состоять из 10 или 12 цифр.',
        ]);

        $company = $lookup->findByInn($validated['inn']);

        if (! $company) {
            return response()->json([
                'message' => 'Компания по ИНН не найдена.',
            ], 404);
        }

        return response()->json([
            'data' => $company,
        ]);
    }
}
