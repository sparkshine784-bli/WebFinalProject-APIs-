<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\User;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Property 20: Valid attendance submission persists and returns 201
 *
 * Validates: Requirements 5.1
 */
class ValidAttendanceSubmissionPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    private const ACTOR_ROLES = ['teacher', 'admin'];
    private const STATUSES = ['present', 'absent', 'late'];

    private int $callCount = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $seed = intval(getenv('ERIS_SEED') ?: (microtime(true) * 1000000));
        if ($seed < 0) {
            $seed *= -1;
        }
        $this->seed = $seed;
        $this->withRand('mt_rand');
        $this->callCount = 0;
    }

    private function registerAndLogin(string $role): array
    {
        $n        = $this->callCount++;
        $email    = 'actor_' . $role . '_' . $n . '_' . uniqid() . '@example.com';
        $password = 'Pass_Abc123!_' . $n;

        $this->postJson('/api/auth/register', [
            'name' => 'Actor ' . $n, 'email' => $email,
            'password' => $password, 'role' => $role,
        ])->assertStatus(201);

        $login = $this->postJson('/api/auth/login', [
            'email' => $email, 'password' => $password,
        ])->assertStatus(200)->json();

        return ['token' => $login['data']['token']];
    }

    /**
     * @test
     */
    public function valid_attendance_submission_persists_and_returns_201(): void
    {
        // Feature: jwt-api-auth-integration, Property 20: Valid attendance submission persists and returns 201

        $this->withoutMiddleware(ThrottleRequests::class);

        $iterationIndex = 0;

        $this
            ->limitTo(100)
            ->forAll(
                Generators::choose(0, 2)
            )
            ->then(function (int $statusIndex) use (&$iterationIndex): void {
                $status    = self::STATUSES[$statusIndex];
                $actorRole = self::ACTOR_ROLES[$iterationIndex % count(self::ACTOR_ROLES)];
                $n         = $this->callCount++;

                $student = User::create([
                    'name' => 'Student ' . $n,
                    'email' => 'student_' . $n . '_' . uniqid() . '@example.com',
                    'password' => bcrypt('password'),
                    'role' => 'student',
                ]);

                $course = Course::create(['name' => 'Course ' . $n]);
                $date   = '2024-' . str_pad((string) (($n % 12) + 1), 2, '0', STR_PAD_LEFT) . '-15';

                $actor = $this->registerAndLogin($actorRole);

                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $actor['token'],
                ])->postJson('/api/attendance', [
                    'student_id' => $student->id,
                    'course_id'  => $course->id,
                    'date'       => $date,
                    'status'     => $status,
                ]);

                $context = "Iteration {$iterationIndex}: status={$status}";

                $response->assertStatus(201, "Expected 201 — {$context}");

                $data = $response->json('data');
                $this->assertSame($status, $data['status'], "{$context}: status mismatch");
                $this->assertSame($student->id, $data['student_id'], "{$context}: student_id mismatch");
                $this->assertSame($course->id, $data['course_id'], "{$context}: course_id mismatch");

                $persisted = Attendance::find($data['id']);
                $this->assertNotNull($persisted, "{$context}: record not persisted");
                $this->assertSame($status, $persisted->status);

                $iterationIndex++;
            });
    }
}
