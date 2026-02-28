<?php

namespace App\Http\Controllers;

use App\Services\AresService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AresCompanyLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'string', 'max:255'],
        ]);

        $ico = preg_replace('/\D+/', '', $validated['company_id']) ?? '';

        if (! preg_match('/^\d{8}$/', $ico)) {
            throw ValidationException::withMessages([
                'company_id' => 'Company ID must be 8 digits.',
            ]);
        }

        try {
            $payload = AresService::find($ico);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Company lookup failed.',
            ], 500);
        }

        if (empty($payload)) {
            return response()->json([
                'message' => 'Company not found.',
            ], 404);
        }

        return response()->json($payload);
    }
}
