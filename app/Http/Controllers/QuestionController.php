<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionRequest;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/** Multiple-choice questions that make up a course's final quiz. */
class QuestionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:courses.manage'),
        ];
    }

    public function create(Course $course): View
    {
        return view('courses.question-form', [
            'course' => $course,
            'question' => new Question([
                'options' => ['', '', '', ''],
                'correct_option' => 0,
                'position' => ($course->questions()->reorder()->max('position') ?? 0) + 1,
            ]),
        ]);
    }

    public function store(QuestionRequest $request, Course $course): RedirectResponse
    {
        $data = $request->payload();
        $data['position'] ??= ($course->questions()->reorder()->max('position') ?? 0) + 1;

        $course->questions()->create($data);

        return redirect()->route('courses.show', $course)->with('success', 'Question added.');
    }

    public function edit(Question $question): View
    {
        return view('courses.question-form', ['course' => $question->course, 'question' => $question]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $data = $request->payload();
        $data['position'] ??= $question->position;

        $question->update($data);

        return redirect()->route('courses.show', $question->course_id)->with('success', 'Question updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $courseId = $question->course_id;
        $question->delete();

        return redirect()->route('courses.show', $courseId)->with('success', 'Question deleted.');
    }
}
