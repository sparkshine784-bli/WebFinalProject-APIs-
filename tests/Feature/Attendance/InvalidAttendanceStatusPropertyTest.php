<?php

namespace Tests\Feature\Attendance;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Property 24: Invalid attendance status returns 422
 *
 * Validates: Requirements 5.5
 */
class InvalidAttendanceStatusPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const INVALID_STATUSES = [
        'Present', 'ABSENT', 'excused', 'sick', '', 'on_leave', '0', 'late,present',
    ];

    private int $callCount = 0;

    private function registerTeacherToken(): string
    {
        $n        = $this->callCount++;
        $email    = 'teacher_' . $n . '_' . uniqid() . '@example.com';
        $password = 'Pass_Abc123!_' . $n;

        $this->postJson('/api/auth/register', [
            'name' => 'Teacher ' . $n, 'email' => $email,
            'password' => $password, 'role' => 'teacher',
        ])->assertStatus(201);

        $login = $this->postJson('/api/auth/login', [
            'email' => $email, 'password' => $password,
        ])->assertStatus(200)->json();

        return $login['data']['token'];
    }

    /**
     * @test
     */
    public function invalid_attendance_status_returns_422(): void
    {
        // Feature: jwt-api-auth-integration, Property 24: Invalid attendance status returns 422

        $this->withoutMiddleware(ThrottleRequests::class);

        for ($i = 0; $i < 100; $i++) {
            $invalidStatus = self::INVALID_STATUSES[$i % count(self::INVALID_STATUSES)];
            $token         = $this->registerTeacherToken();

            $student = User::create([
                'name' => 'Student', 'email' => 's_' . $i . '_' . uniqid() . '@example.com',
                'password' => bcrypt('x'), 'role' => 'student',
            ]);
            $course = Course::create(['name' => 'Course ' . $i]);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson('/api/attendance', [
                'student_id' => $student->id,
                'course_id'  => $course->id,
                'date'       => '2024-01-15',
                'status'     => $invalidStatus,
            ]);

            $response->assertStatus(422,
                "Iteration {$i}: invalid status '{$invalidStatus}' must return 422");

            $body = $response->json();
            $this->assertSame('error', $body['status']);
            $this->assertArrayHasKey('status', $body['errors']);
        }
    }
}
