<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Support\Collection;

interface GradeServiceInterface
{
    public function create(array $data, User $actor): Grade;
    public function update(int $gradeId, array $data, User $actor): Grade;
    public function getForStudent(int $studentId, User $actor): Collection;
}
