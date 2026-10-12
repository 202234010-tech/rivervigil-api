<?php

namespace App\Http\Controllers;

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
            ->get()
            ->map(fn (Station $station): array => $this->stationData($station));

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar stasiun pos pantau berhasil diambil.',
            'data' => $stations,
            'meta' => [
                'page' => (int) $request->query('page', 1),
                'limit' => (int) $request->query('limit', 10),
                'total_records' => $totalRecords,
            ],
        ]);
    }

    public function show(string $station): JsonResponse
    {
        $stationModel = Station::find($station);

        if ($stationModel === null) {
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

        return response()->json([
            'status' => 'success',
            'message' => 'Detail stasiun pos pantau ditemukan.',
            'data' => $this->stationData($stationModel),
        ]);
    }

    private function stationData(Station $station): array
    {
        return [
            'id' => (string) $station->id,
            'name' => (string) $station->name,
            'river_basin' => (string) $station->river_basin,
            'location' => (string) $station->location,
            'current_level_cm' => (float) $station->current_level_cm,
            'flow_rate_m3s' => (float) $station->flow_rate_m3s,
            'alert_status' => (string) $station->alert_status,
            'thresholds_cm' => is_array($station->thresholds_cm)
                ? $station->thresholds_cm
                : json_decode((string) $station->thresholds_cm, true),
            'updated_at' => $station->updated_at?->toISOString(),
        ];
    }
}
