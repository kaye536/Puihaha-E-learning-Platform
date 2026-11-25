<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Member;
use App\Models\QuizAttempt;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Business rules of the e-learning module:
 *  - only members with an active membership can learn, and a course can require a minimum level;
 *  - a lesson is marked complete by the learner; the final quiz unlocks after every lesson is done;
 *  - a course is completed (and a numbered certificate issued) once all lessons are done and, when the
 *    course has a quiz, the pass mark has been reached.
 */
class LearningService
{
    /** Why this member cannot take this course right now, or null when they can. */
    public function accessProblem(Member $member, Course $course): ?string
    {
        if (! $course->is_published) {
            return 'This course is not available.';
        }

        if ($member->status !== Member::STATUS_ACTIVE) {
            return 'E-learning is available to members with an active membership.';
        }

        $required = $course->level;
        if ($required && (float) $member->level->fee < (float) $required->fee) {
            return "This course is for {$required->name} members and above.";
        }

        return null;
    }

    public function enroll(Member $member, Course $course): Enrollment
    {
        if ($problem = $this->accessProblem($member, $course)) {
            throw new DomainException($problem);
        }

        if (! $course->lessons()->exists()) {
            throw new DomainException('This course has no lessons yet.');
        }

        return Enrollment::firstOrCreate(
            ['course_id' => $course->id, 'member_id' => $member->id],
            ['enrolled_at' => now()],
        );
    }

    public function completeLesson(Enrollment $enrollment, Lesson $lesson): Enrollment
    {
        if ($lesson->course_id !== $enrollment->course_id) {
            throw new DomainException('That lesson does not belong to this course.');
        }

        $enrollment->completions()->firstOrCreate(
            ['lesson_id' => $lesson->id],
            ['completed_at' => now()],
        );

        return $this->finalize($enrollment);
    }

    /**
     * Grade a quiz attempt. $answers maps question id => chosen option index.
     */
    public function submitQuiz(Enrollment $enrollment, array $answers): QuizAttempt
    {
        $course = $enrollment->course;
        $questions = $course->questions()->get();

        if ($questions->isEmpty()) {
            throw new DomainException('This course has no quiz.');
        }

        if (! $this->allLessonsDone($enrollment)) {
            throw new DomainException('Finish every lesson before taking the quiz.');
        }

        $given = [];
        $correct = 0;
        foreach ($questions as $question) {
            $choice = (int) ($answers[$question->id] ?? -1);
            $given[$question->id] = $choice;
            if ($choice === $question->correct_option) {
                $correct++;
            }
        }

        $score = (int) round($correct / $questions->count() * 100);

        return DB::transaction(function () use ($enrollment, $given, $score, $course) {
            $attempt = $enrollment->attempts()->create([
                'score' => $score,
                'passed' => $score >= $course->pass_mark,
                'answers' => $given,
            ]);

            $this->finalize($enrollment);

            return $attempt;
        });
    }

    public function allLessonsDone(Enrollment $enrollment): bool
    {
        $total = $enrollment->course->lessons()->reorder()->count();

        return $total > 0 && $this->completedLessonIds($enrollment)->count() >= $total;
    }

    /** @return \Illuminate\Support\Collection<int, int> lesson ids this learner has finished (that still exist) */
    public function completedLessonIds(Enrollment $enrollment)
    {
        return $enrollment->completions()
            ->whereIn('lesson_id', $enrollment->course->lessons()->pluck('id'))
            ->pluck('lesson_id');
    }

    /** @return array{done:int,total:int,percent:int} */
    public function progress(Enrollment $enrollment): array
    {
        $total = $enrollment->course->lessons()->reorder()->count();
        $done = min($total, $this->completedLessonIds($enrollment)->count());

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }

    public function bestScore(Enrollment $enrollment): ?int
    {
        return $enrollment->attempts()->max('score');
    }

    public function hasPassed(Enrollment $enrollment): bool
    {
        return $enrollment->attempts()->where('passed', true)->exists();
    }

    /** Mark the course completed and issue the certificate once every requirement is met. */
    private function finalize(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->isComplete() || ! $this->allLessonsDone($enrollment)) {
            return $enrollment;
        }

        if ($enrollment->course->questions()->exists() && ! $this->hasPassed($enrollment)) {
            return $enrollment;
        }

        $enrollment->update([
            'completed_at' => now(),
            'certificate_number' => Enrollment::generateCertificateNumber(),
        ]);

        return $enrollment;
    }
}
