<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class GradeService implements GradeServiceInterface
{
    /**
     * Create a new grade record. Actor must be admin or teacher.
     *
     * @throws AuthorizationException
     */
    public function create(array $data, User $actor): Grade
    {
        if (!in_array($actor->role, ['admin', 'teacher'])) {
            throw new AuthorizationException('Forbidden: insufficient permissions.');
        }

        return Grade::create($data);
    }

    /**
     * Update an existing grade record. Actor must be admin or teacher.
     *
     * @throws AuthorizationException
     */
    public function update(int $gradeId, array $data, User $actor): Grade
    {
        if (!in_array($actor->role, ['admin', 'teacher'])) {
            throw new AuthorizationException('Forbidden: insufficient permissions.');
        }

        $grade = Grade::findOrFail($gradeId);
        $grade->update($data);

        return $grade->fresh();
    }

    /**
     * Retrieve grades for a student. Students may only access their own records.
     *
     * @throws AuthorizationException
     */
    public function getForStudent(int $studentId, User $actor): Collection
    {
        if ($actor->role === 'student' && $actor->id !== $studentId) {
            throw new AuthorizationException('Forbidden: insufficient permissions.');
        }

        return Grade::where('student_id', $studentId)->get();
    }
}
