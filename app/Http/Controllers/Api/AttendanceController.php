<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Services\AttendanceServiceInterface;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceServiceInterface $attendanceService)
    {
    }

    /**
     * GET /api/attendance — return the authenticated student's own attendance records.
     */
    public function index(): JsonResponse
    {
        $actor = auth('api')->user();
        $records = $this->attendanceService->getForStudent($actor->id, $actor);

        return ApiResponse::success($records, 'Attendance retrieved.');
    }

    /**
     * POST /api/attendance — create an attendance record (admin/teacher only).
     */
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $actor = auth('api')->user();
        $record = $this->attendanceService->create($request->validated(), $actor);

        return ApiResponse::created($record, 'Attendance record created.');
    }

    /**
     * PUT /api/attendance/{id} — update an attendance record (admin/teacher only).
     */
    public function update(UpdateAttendanceRequest $request, int $id): JsonResponse
    {
        $actor = auth('api')->user();
        $record = $this->attendanceService->update($id, $request->validated(), $actor);

        return ApiResponse::success($record, 'Attendance record updated.');
    }
}
