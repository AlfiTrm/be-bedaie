<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesPageRequest;
use App\Models\SalesPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesPageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $salesPages = $request->user()
            ->salesPages()
            ->latest()
            ->get(['id', 'product_name', 'created_at']);

        return response()->json($salesPages);
    }

    public function store(StoreSalesPageRequest $request): JsonResponse
    {
        $salesPage = $request->user()
            ->salesPages()
            ->create($request->validated());

        return response()->json($salesPage, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $salesPage = $request->user()
            ->salesPages()
            ->findOrFail($id);

        return response()->json($salesPage);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $salesPage = $request->user()
            ->salesPages()
            ->findOrFail($id);

        $salesPage->delete();

        return response()->json([
            'message' => 'Sales page deleted successfully.',
        ]);
    }
}
