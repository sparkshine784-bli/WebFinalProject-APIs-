<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class AttendanceService implements AttendanceServiceInterface
{
    /**
     * Create a new attendance record. Actor must be admin or teacher.
     *
     * @throws AuthorizationException
     */
    public function create(array $data, User $actor): Attendance
    {
        if (!in_array($actor->role, ['admin', 'teacher'])) {
            throw new AuthorizationException('Forbidden: insufficient permissions.');
        }

        return Attendance::create($data);
    }

    /**
     * Update an existing attendance record. Actor must be admin or teacher.
     *
     * @throws AuthorizationException
     */
    public function update(int $attendanceId, array $data, User $actor): Attendance
    {
        if (!in_array($actor->role, ['admin', 'teacher'])) {
            throw new AuthorizationException('Forbidden: insufficient permissions.');
        }

        $attendance = Attendance::findOrFail($attendanceId);
        $attendance->update($data);

        return $attendance->fresh();
    }

    /**
     * Retrieve attendance for a student. Students may only access their own records.
     *
     * @throws AuthorizationException
     */
    public function getForStudent(int $studentId, User $actor): Collection
    {
        if ($actor->role === 'student' && $actor->id !== $studentId) {
            throw new AuthorizationException('Forbidden: insufficient permissions.');
        }

        return Attendance::where('student_id', $studentId)->get();
    }
}
