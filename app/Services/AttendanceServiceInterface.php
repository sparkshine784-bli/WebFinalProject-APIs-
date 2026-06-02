<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Collection;

interface AttendanceServiceInterface
{
    public function create(array $data, User $actor): Attendance;
    public function update(int $attendanceId, array $data, User $actor): Attendance;
    public function getForStudent(int $studentId, User $actor): Collection;
}
