<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use App\Services\GradeServiceInterface;
use Illuminate\Http\JsonResponse;

class GradeController extends Controller
{
    public function __construct(private GradeServiceInterface $gradeService)
    {
    }

    /**
     * GET /api/grades — return the authenticated student's own grades.
     */
    public function index(): JsonResponse
    {
        $actor = auth('api')->user();
        $grades = $this->gradeService->getForStudent($actor->id, $actor);

        return ApiResponse::success($grades, 'Grades retrieved.');
    }

    /**
     * POST /api/grades — create a grade record (admin/teacher only).
     */
    public function store(StoreGradeRequest $request): JsonResponse
    {
        $actor = auth('api')->user();
        $grade = $this->gradeService->create($request->validated(), $actor);

        return ApiResponse::created($grade, 'Grade created.');
    }

    /**
     * PUT /api/grades/{id} — update a grade record (admin/teacher only).
     */
    public function update(UpdateGradeRequest $request, int $id): JsonResponse
    {
        $actor = auth('api')->user();
        $grade = $this->gradeService->update($id, $request->validated(), $actor);

        return ApiResponse::success($grade, 'Grade updated.');
    }
}
