<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStationRequest;
use App\Http\Requests\UpdateStationRequest;
use App\Http\Resources\StationResource;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Station::query();

        if ($request->filled('river_basin')) {
            $query->where('river_basin', $request->query('river_basin'));
        }

        if ($request->filled('status')) {
            $query->where('alert_status', $request->query('status'));
        }

        $totalRecords = (clone $query)->count();

        $stations = $query
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar stasiun pos pantau berhasil diambil.',
            'data' => StationResource::collection($stations),
            'meta' => [
                'page' => (int) $request->query('page', 1),
                'limit' => (int) $request->query('limit', 10),
                'total_records' => $totalRecords,
            ],
        ]);
    }

    public function store(StoreStationRequest $request): JsonResponse
    {
        $station = Station::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Pos pantau stasiun sungai baru berhasil didaftarkan.',
            'data' => new StationResource($station),
        ], 201);
    }

    public function show(string $station): JsonResponse
    {
        $stationModel = Station::find($station);

        if ($stationModel === null) {
            return $this->notFound($station);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail stasiun pos pantau ditemukan.',
            'data' => new StationResource($stationModel),
        ]);
    }

    public function update(UpdateStationRequest $request, string $station): JsonResponse
    {
        $stationModel = Station::find($station);

        if ($stationModel === null) {
            return $this->notFound($station);
        }

        $stationModel->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Data stasiun pos pantau berhasil diperbarui.',
            'data' => new StationResource($stationModel->refresh()),
        ], 200);
    }

    public function destroy(string $station): JsonResponse
    {
        $stationModel = Station::find($station);

        if ($stationModel === null) {
            return $this->notFound($station);
        }

        $stationModel->delete();

        return response()->json(null, 204);
    }

    private function notFound(string $station): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => "Stasiun pos pantau dengan ID '{$station}' tidak ditemukan.",
            'errors' => [
                [
                    'field' => 'id',
                    'issue' => 'Resource station tidak ada dalam basis data.',
                ],
            ],
        ], 404);
    }
}
