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
 * Property 22: Student retrieves only their own attendance records
 *
 * Validates: Requirements 5.3
 */
class StudentReadsOwnAttendancePropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

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

    private function registerAndLoginStudent(): array
    {
        $n        = $this->callCount++;
        $email    = 'student_' . $n . '_' . uniqid() . '@example.com';
        $password = 'Pass_Abc123!_' . $n;

        $this->postJson('/api/auth/register', [
            'name' => 'Student ' . $n, 'email' => $email,
            'password' => $password, 'role' => 'student',
        ])->assertStatus(201);

        $login = $this->postJson('/api/auth/login', [
            'email' => $email, 'password' => $password,
        ])->assertStatus(200)->json();

        return [
            'user'  => User::where('email', $email)->firstOrFail(),
            'token' => $login['data']['token'],
        ];
    }

    /**
     * @test
     */
    public function student_retrieves_only_their_own_attendance(): void
    {
        // Feature: jwt-api-auth-integration, Property 22: Student retrieves only their own attendance records

        $this->withoutMiddleware(ThrottleRequests::class);

        $this
            ->limitTo(100)
            ->forAll(
                Generators::choose(1, 4)
            )
            ->then(function (int $recordCount): void {
                $student = $this->registerAndLoginStudent();
                $user    = $student['user'];

                $expectedIds = [];
                for ($i = 0; $i < $recordCount; $i++) {
                    $n      = $this->callCount++;
                    $course = Course::create(['name' => 'Course_' . $n]);
                    $record = Attendance::create([
                        'student_id' => $user->id,
                        'course_id'  => $course->id,
                        'date'       => '2024-06-' . str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT),
                        'status'     => ['present', 'absent', 'late'][$i % 3],
                    ]);
                    $expectedIds[] = $record->id;
                }

                $other = User::create([
                    'name' => 'Other', 'email' => 'other_' . uniqid() . '@example.com',
                    'password' => bcrypt('x'), 'role' => 'student',
                ]);
                $otherCourse = Course::create(['name' => 'OtherCourse']);
                Attendance::create([
                    'student_id' => $other->id,
                    'course_id'  => $otherCourse->id,
                    'date'       => '2024-06-01',
                    'status'     => 'present',
                ]);

                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $student['token'],
                ])->getJson('/api/attendance');

                $response->assertStatus(200);
                $returned = $response->json('data');
                $this->assertCount($recordCount, $returned);

                $returnedIds = array_column($returned, 'id');
                sort($expectedIds);
                sort($returnedIds);
                $this->assertSame($expectedIds, $returnedIds);

                foreach ($returned as $row) {
                    $this->assertEquals($user->id, $row['student_id']);
                }
            });
    }
}
