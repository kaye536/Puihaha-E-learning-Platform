<?php

namespace App\Http\Controllers;

use App\Http\Requests\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LessonController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:courses.manage'),
        ];
    }

    public function create(Course $course): View
    {
        return view('courses.lesson-form', [
            'course' => $course,
            'lesson' => new Lesson(['position' => ($course->lessons()->reorder()->max('position') ?? 0) + 1]),
        ]);
    }

    public function store(LessonRequest $request, Course $course): RedirectResponse
    {
        $data = $request->validated();
        $data['position'] ??= ($course->lessons()->reorder()->max('position') ?? 0) + 1;

        $course->lessons()->create($data);

        return redirect()->route('courses.show', $course)->with('success', 'Lesson added.');
    }

    public function edit(Lesson $lesson): View
    {
        return view('courses.lesson-form', ['course' => $lesson->course, 'lesson' => $lesson]);
    }

    public function update(LessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validated();
        $data['position'] ??= $lesson->position;

        $lesson->update($data);

        return redirect()->route('courses.show', $lesson->course_id)->with('success', 'Lesson updated.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $courseId = $lesson->course_id;
        $lesson->delete();

        return redirect()->route('courses.show', $courseId)->with('success', 'Lesson deleted.');
    }
}
