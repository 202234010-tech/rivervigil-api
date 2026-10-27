<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStationRequest;
use App\Http\Requests\UpdateStationRequest;
use App\Http\Resources\StationResource;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', Rule::in(['id', 'name', 'river_basin', 'location', 'current_level_cm', 'flow_rate_m3s', 'alert_status', 'created_at'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'river_basin' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['normal', 'siaga_3', 'siaga_2', 'siaga_1'])],
        ]);

        if ($validator->fails()) {
            $formattedErrors = [];
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $formattedErrors[] = [
                        'field' => $field,
                        'issue' => $message,
                    ];
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Validasi query parameter gagal. Periksa kembali parameter yang dikirimkan.',
                'errors' => $formattedErrors,
            ], 422);
        }

        $validated = $validator->validated();
        $query = Station::query();

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if (! empty($validated['river_basin'])) {
            $query->where('river_basin', $validated['river_basin']);
        }

        if (! empty($validated['status'])) {
            $query->where('alert_status', $validated['status']);
        }

        $sort = $validated['sort'] ?? 'id';
        $direction = $validated['direction'] ?? 'asc';
        $perPage = $validated['per_page'] ?? $validated['limit'] ?? 10;

        $paginated = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar stasiun pos pantau berhasil diambil.',
            'data' => StationResource::collection($paginated),
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'from' => $paginated->firstItem(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'to' => $paginated->lastItem(),
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'limit' => $paginated->perPage(),
                'total_records' => $paginated->total(),
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
