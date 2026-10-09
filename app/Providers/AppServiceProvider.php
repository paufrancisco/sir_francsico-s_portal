<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\ClassmateMessage;
use App\Models\GradeCorrection;
use App\Models\Message;
use App\Models\StudentActivity;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('portal-verify', function (\Illuminate\Http\Request $request) {
            $sn = (string) $request->input('student_number', $request->input('student_id', 'none'));

            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('sn:' . $sn . '|' . $request->ip()),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(200)->by('ip:' . $request->ip()),
            ];
        });

        Vite::prefetch(concurrency: 3);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->registerStudentActivityHooks();
    }

    private function registerStudentActivityHooks(): void
    {
        GradeCorrection::created(function (GradeCorrection $gc) {
            $student = $gc->student;
            $name = $student?->full_name ?? 'A student';
            $what = $gc->period ? ucfirst($gc->period) . ' grades' : 'grades';

            if ($gc->type === 'confirmed') {
                StudentActivity::record(
                    'grades_confirmed',
                    $student,
                    "{$name} confirmed their {$what}.",
                    ['grade_correction_id' => $gc->id, 'period' => $gc->period]
                );

                return;
            }

            $notes = $gc->notes ? ': ' . Str::limit($gc->notes, 120) : '.';

            StudentActivity::record(
                'grades_recheck',
                $student,
                "{$name} filed a complaint on their {$what}{$notes}",
                ['grade_correction_id' => $gc->id, 'period' => $gc->period]
            );
        });

        Appointment::created(function (Appointment $a) {
            $student = $a->student;
            $name = $student?->full_name ?? 'A student';
            $when = trim(($a->appointment_date?->format('M d, Y') ?? '') . ' ' . substr((string) $a->start_time, 0, 5));
            $reason = $a->reason ? ': ' . Str::limit($a->reason, 100) : '.';

            StudentActivity::record(
                'appointment_booked',
                $student,
                "{$name} booked an appointment" . ($when !== '' ? " for {$when}" : '') . $reason,
                ['appointment_id' => $a->id]
            );
        });

        ClassmateMessage::created(function (ClassmateMessage $m) {
            $student = $m->sender;
            $name = $student?->full_name ?? 'A student';

            StudentActivity::record(
                'classmate_message',
                $student,
                "{$name} posted on the classmate wall: " . Str::limit((string) $m->body, 120),
                ['classmate_message_id' => $m->id]
            );
        });

        Message::created(function (Message $m) {
            if ($m->sender !== 'student') {
                return;
            }

            $student = $m->student;
            $name = $student?->full_name ?? 'A student';

            StudentActivity::record(
                'chat_message',
                $student,
                "{$name} chatted with the AI assistant: \"" . Str::limit((string) $m->body, 100) . '"',
                ['message_id' => $m->id],
                30
            );
        });
    }
}
