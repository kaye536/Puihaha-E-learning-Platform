<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Member;
use App\Models\QuizAttempt;
use App\Services\LearningService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Member side of e-learning. Like the portal, every query is scoped to the signed-in member:
 * nothing here accepts a member id from the request.
 */
class LearningController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:learning.access'),
        ];
    }

    public function __construct(private LearningService $learning)
    {
    }

    public function index(): View
    {
        $member = $this->member()->load('level');

        $courses = Course::available()->with('level')->withCount(['lessons', 'questions'])->orderBy('title')->get();
        $enrollments = Enrollment::where('member_id', $member->id)->get()->keyBy('course_id');

        return view('learn.index', [
            'member' => $member,
            'courses' => $courses,
            'enrollments' => $enrollments,
            'progress' => $enrollments->map(fn (Enrollment $enrollment) => $this->learning->progress($enrollment)),
            'problems' => $courses->mapWithKeys(fn (Course $course) => [$course->id => $this->learning->accessProblem($member, $course)]),
        ]);
    }

    public function show(Course $course): View
    {
        $member = $this->member()->load('level');
        $this->ensureVisible($course);

        $course->load(['level', 'lessons', 'questions']);
        $enrollment = $this->enrollmentFor($member, $course);

        return view('learn.show', [
            'course' => $course,
            'enrollment' => $enrollment,
            'problem' => $this->learning->accessProblem($member, $course),
            'doneIds' => $enrollment ? $this->learning->completedLessonIds($enrollment)->all() : [],
            'progress' => $enrollment ? $this->learning->progress($enrollment) : null,
            'bestScore' => $enrollment ? $this->learning->bestScore($enrollment) : null,
            'attempts' => $enrollment ? $enrollment->attempts()->latest('id')->get() : collect(),
            'quizUnlocked' => $enrollment ? $this->learning->allLessonsDone($enrollment) : false,
        ]);
    }

    public function enroll(Course $course): RedirectResponse
    {
        $member = $this->member()->load('level');
        $this->ensureVisible($course);

        try {
            $enrollment = $this->learning->enroll($member, $course);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('learn.lesson', [$course, $this->resumeLesson($enrollment)])
            ->with('success', "You're enrolled in {$course->title}. Let's begin!");
    }

    public function lesson(Course $course, Lesson $lesson): View|RedirectResponse
    {
        $enrollment = $this->gate($course);
        if ($enrollment instanceof RedirectResponse) {
            return $enrollment;
        }
        abort_unless($lesson->course_id === $course->id, 404);

        $lessons = $course->lessons()->get();
        $position = $lessons->search(fn (Lesson $l) => $l->id === $lesson->id);

        return view('learn.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'previous' => $position > 0 ? $lessons->get($position - 1) : null,
            'next' => $lessons->get($position + 1),
            'doneIds' => $this->learning->completedLessonIds($enrollment)->all(),
            'progress' => $this->learning->progress($enrollment),
            'hasQuiz' => $course->questions()->exists(),
        ]);
    }

    public function complete(Course $course, Lesson $lesson): RedirectResponse
    {
        $enrollment = $this->gate($course);
        if ($enrollment instanceof RedirectResponse) {
            return $enrollment;
        }
        abort_unless($lesson->course_id === $course->id, 404);

        $wasComplete = $enrollment->isComplete();
        $enrollment = $this->learning->completeLesson($enrollment, $lesson);

        $lessons = $course->lessons()->get();
        $next = $lessons->get($lessons->search(fn (Lesson $l) => $l->id === $lesson->id) + 1);

        if ($next) {
            return redirect()->route('learn.lesson', [$course, $next])->with('success', 'Lesson completed.');
        }

        if ($enrollment->isComplete() && ! $wasComplete) {
            return redirect()->route('learn.show', $course)
                ->with('success', 'Congratulations! You completed the course and earned a certificate.');
        }

        if ($this->learning->allLessonsDone($enrollment) && $course->questions()->exists() && ! $enrollment->isComplete()) {
            return redirect()->route('learn.quiz', $course)->with('success', 'All lessons done. Take the final quiz to finish the course.');
        }

        return redirect()->route('learn.show', $course)->with('success', 'Lesson completed.');
    }

    public function quiz(Course $course): View|RedirectResponse
    {
        $enrollment = $this->gate($course);
        if ($enrollment instanceof RedirectResponse) {
            return $enrollment;
        }

        if (! $course->questions()->exists()) {
            return redirect()->route('learn.show', $course)->with('error', 'This course has no quiz.');
        }

        if (! $this->learning->allLessonsDone($enrollment)) {
            return redirect()->route('learn.show', $course)->with('error', 'Finish every lesson to unlock the quiz.');
        }

        return view('learn.quiz', [
            'course' => $course,
            'questions' => $course->questions()->get(),
            'bestScore' => $this->learning->bestScore($enrollment),
        ]);
    }

    public function submitQuiz(Request $request, Course $course): RedirectResponse
    {
        $enrollment = $this->gate($course);
        if ($enrollment instanceof RedirectResponse) {
            return $enrollment;
        }

        $questions = $course->questions()->get();
        $answers = (array) $request->input('answers', []);

        foreach ($questions as $question) {
            $choice = $answers[$question->id] ?? null;
            if (! is_numeric($choice) || (int) $choice < 0 || (int) $choice >= count($question->options)) {
                return back()->withInput()->with('error', 'Please answer every question before submitting.');
            }
        }

        try {
            $attempt = $this->learning->submitQuiz($enrollment, $answers);
        } catch (DomainException $e) {
            return redirect()->route('learn.show', $course)->with('error', $e->getMessage());
        }

        return redirect()->route('learn.quiz.result', [$course, $attempt]);
    }

    public function result(Course $course, QuizAttempt $attempt): View|RedirectResponse
    {
        $enrollment = $this->gate($course);
        if ($enrollment instanceof RedirectResponse) {
            return $enrollment;
        }

        // Someone else's attempt does not exist as far as this member is concerned.
        abort_unless($attempt->enrollment_id === $enrollment->id, 404);

        return view('learn.result', [
            'course' => $course,
            'attempt' => $attempt,
            'enrollment' => $enrollment->fresh(),
            'questions' => $course->questions()->get(),
        ]);
    }

    public function certificate(Course $course): View
    {
        $member = $this->member();
        $enrollment = $this->enrollmentFor($member, $course);

        // A certificate stays available even if the membership lapses later.
        abort_unless($enrollment && $enrollment->isComplete(), 404);

        return view('learn.certificate', [
            'course' => $course,
            'member' => $member,
            'enrollment' => $enrollment,
            'bestScore' => $this->learning->bestScore($enrollment),
        ]);
    }

    /** The signed-in user's own member record. Staff accounts have none and are turned away. */
    private function member(): Member
    {
        $member = auth()->user()->member;
        abort_unless($member, 403, 'E-learning is only for members.');

        return $member;
    }

    private function ensureVisible(Course $course): void
    {
        abort_unless($course->is_published && $course->lessons()->exists(), 404);
    }

    private function enrollmentFor(Member $member, Course $course): ?Enrollment
    {
        return Enrollment::where('member_id', $member->id)->where('course_id', $course->id)->first();
    }

    /**
     * Everything needed to study: the member is enrolled and still allowed to take the course.
     * Returns the enrollment, or a redirect explaining what is missing.
     */
    private function gate(Course $course): Enrollment|RedirectResponse
    {
        $member = $this->member()->load('level');
        $this->ensureVisible($course);

        $enrollment = $this->enrollmentFor($member, $course);
        if (! $enrollment) {
            return redirect()->route('learn.show', $course)->with('error', 'Enrol in this course to start learning.');
        }

        if ($problem = $this->learning->accessProblem($member, $course)) {
            return redirect()->route('learn.index')->with('error', $problem);
        }

        return $enrollment;
    }

    /** First lesson the learner has not finished, or the first lesson when they have finished them all. */
    private function resumeLesson(Enrollment $enrollment): Lesson
    {
        $done = $this->learning->completedLessonIds($enrollment)->all();
        $lessons = $enrollment->course->lessons()->get();

        return $lessons->first(fn (Lesson $lesson) => ! in_array($lesson->id, $done, true)) ?? $lessons->first();
    }
}
