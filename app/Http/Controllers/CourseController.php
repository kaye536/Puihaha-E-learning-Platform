<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\MembershipLevel;
use App\Services\LearningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/** Staff side of e-learning: build courses and watch learner progress. */
class CourseController extends Controller implements HasMiddleware
{
    /** Role-based access control: see config/rbac.php for which roles hold each permission. */
    public static function middleware(): array
    {
        return [
            new Middleware('can:courses.view', only: ['index', 'show']),
            new Middleware('can:courses.manage', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(): View
    {
        return view('courses.index', [
            'courses' => Course::with('level')
                ->withCount(['lessons', 'questions', 'enrollments'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('courses.form', [
            'course' => new Course(['pass_mark' => 70]),
            'levels' => MembershipLevel::orderBy('fee')->get(),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = Course::create($request->payload() + ['created_by' => $request->user()->id]);

        return redirect()->route('courses.show', $course)
            ->with('success', 'Course created. Now add its lessons and, optionally, a final quiz.');
    }

    public function show(Course $course, LearningService $learning): View
    {
        $course->load(['level', 'lessons', 'questions']);

        $enrollments = $course->enrollments()
            ->with('member')
            ->withCount('completions')
            ->latest('enrolled_at')
            ->get();

        return view('courses.show', [
            'course' => $course,
            'enrollments' => $enrollments,
            'learning' => $learning,
        ]);
    }

    public function edit(Course $course): View
    {
        return view('courses.form', [
            'course' => $course,
            'levels' => MembershipLevel::orderBy('fee')->get(),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->payload());

        return redirect()->route('courses.show', $course)->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        if ($course->enrollments()->exists()) {
            return back()->with('error', 'Members are enrolled in this course, so it cannot be deleted. Unpublish it instead.');
        }

        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Course deleted successfully.');
    }
}
