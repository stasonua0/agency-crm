<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiAutofillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientAiAutofillController extends Controller
{
    public function __invoke(Request $request, AiAutofillService $autofill): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'min:10', 'max:5000'],
        ], [
            'text.required' => 'Вставьте текст для разбора.',
            'text.min' => 'Текст слишком короткий для разбора.',
            'text.max' => 'Текст не должен быть длиннее 5000 символов.',
        ]);

        return response()->json([
            'data' => $autofill->parseClientText($validated['text']),
        ]);
    }
}
