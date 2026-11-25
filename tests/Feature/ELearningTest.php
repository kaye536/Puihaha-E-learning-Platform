<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\Question;
use App\Models\User;

beforeEach(function () {
    $this->category = MembershipCategory::create(['name' => 'Regular']);
    $this->basic = MembershipLevel::create(['name' => 'Basic', 'fee' => 500, 'validity_months' => 12]);
    $this->silver = MembershipLevel::create(['name' => 'Silver', 'fee' => 1500, 'validity_months' => 12]);
});

/** A member with a portal login. Active by default, on the Basic level. */
function learnerMember(array $overrides = []): Member
{
    $member = Member::create(array_merge([
        'first_name' => 'Lia', 'last_name' => 'Cruz', 'email' => 'lia'.uniqid().'@example.test',
        'membership_category_id' => test()->category->id, 'membership_level_id' => test()->basic->id,
        'status' => Member::STATUS_ACTIVE,
        'membership_start' => today()->subMonths(2), 'membership_expiry' => today()->addMonths(10),
    ], $overrides));

    User::factory()->role('member')->create([
        'name' => $member->full_name, 'email' => $member->email,
        'member_id' => $member->id, 'portal_activated_at' => now(),
    ]);

    return $member->fresh();
}

/** A published course with $lessons lessons and, optionally, a two-question quiz (correct answers are index 1). */
function publishedCourse(int $lessons = 2, bool $withQuiz = true, array $overrides = []): Course
{
    $course = Course::create(array_merge(['title' => 'Intro Course', 'pass_mark' => 50, 'is_published' => true], $overrides));

    foreach (range(1, $lessons) as $n) {
        $course->lessons()->create(['title' => "Lesson {$n}", 'content' => "Content {$n}", 'position' => $n]);
    }

    if ($withQuiz) {
        foreach ([1, 2] as $n) {
            $course->questions()->create(['prompt' => "Question {$n}?", 'options' => ['Wrong', 'Right', 'Also wrong'], 'correct_option' => 1, 'position' => $n]);
        }
    }

    return $course;
}

function finishLessons(Course $course): void
{
    foreach ($course->lessons as $lesson) {
        test()->post(route('learn.lesson.complete', [$course, $lesson]));
    }
}

// ---------------------------------------------------------------- staff side

test('administrators and instructors can build a course, lessons and quiz questions', function (string $role) {
    $this->actingAs(User::factory()->role($role)->create());

    $this->post(route('courses.store'), ['title' => 'Safety Basics', 'pass_mark' => 80, 'is_published' => '1'])
        ->assertRedirect();
    $course = Course::firstWhere('title', 'Safety Basics');
    expect($course->is_published)->toBeTrue()->and($course->pass_mark)->toBe(80);

    $this->post(route('courses.lessons.store', $course), ['title' => 'First lesson', 'content' => 'Hello'])->assertRedirect(route('courses.show', $course));
    expect($course->lessons()->count())->toBe(1)->and($course->lessons()->first()->position)->toBe(1);

    $this->post(route('courses.questions.store', $course), [
        'prompt' => 'Pick the right one', 'options' => ['A', '', 'C', 'D'], 'correct_option' => 2,
    ])->assertRedirect(route('courses.show', $course));

    // The blank choice is dropped, so "C" (index 2) is now stored at index 1.
    $question = $course->questions()->first();
    expect($question->options)->toBe(['A', 'C', 'D'])->and($question->correct_option)->toBe(1);
})->with(['admin', 'instructor']);

test('staff roles only see the e-learning screens their permissions allow', function () {
    $course = publishedCourse();

    $this->actingAs(User::factory()->role('viewer')->create());
    $this->get(route('courses.index'))->assertOk();
    $this->get(route('courses.show', $course))->assertOk();
    $this->get(route('courses.create'))->assertForbidden();
    $this->post(route('courses.store'), ['title' => 'Nope', 'pass_mark' => 70])->assertForbidden();
    $this->delete(route('courses.destroy', $course))->assertForbidden();
    $this->get(route('courses.lessons.create', $course))->assertForbidden();
    $this->get(route('courses.questions.create', $course))->assertForbidden();

    $this->actingAs(User::factory()->role('cashier')->create());
    $this->get(route('courses.index'))->assertForbidden();
});

test('lesson and question validation rejects incomplete input', function () {
    $this->actingAs(User::factory()->role('instructor')->create());
    $course = publishedCourse(1, false);

    // no text and no video
    $this->post(route('courses.lessons.store', $course), ['title' => 'Empty'])->assertSessionHasErrors('content');
    // video links must be https
    $this->post(route('courses.lessons.store', $course), ['title' => 'Video', 'video_url' => 'http://example.com/v'])->assertSessionHasErrors('video_url');
    // fewer than two choices
    $this->post(route('courses.questions.store', $course), ['prompt' => 'Q?', 'options' => ['Only one', '', '', ''], 'correct_option' => 0])->assertSessionHasErrors('options');
    // the choice marked correct is empty
    $this->post(route('courses.questions.store', $course), ['prompt' => 'Q?', 'options' => ['A', 'B', '', ''], 'correct_option' => 3])->assertSessionHasErrors('correct_option');
});

test('a course with learners or a level used by a course cannot be deleted', function () {
    $this->actingAs(User::factory()->create());
    $course = publishedCourse(1, false, ['membership_level_id' => $this->silver->id]);

    $this->delete(route('levels.destroy', $this->silver))->assertSessionHas('error');
    expect(MembershipLevel::find($this->silver->id))->not->toBeNull();

    Enrollment::create(['course_id' => $course->id, 'member_id' => learnerMember(['membership_level_id' => $this->silver->id])->id, 'enrolled_at' => now()]);
    $this->delete(route('courses.destroy', $course))->assertSessionHas('error');
    expect(Course::find($course->id))->not->toBeNull();

    $empty = publishedCourse(1, false, ['title' => 'Unused']);
    $this->delete(route('courses.destroy', $empty))->assertRedirect(route('courses.index'));
    expect(Course::find($empty->id))->toBeNull();
});

// --------------------------------------------------------------- member side

test('a member enrols, finishes the lessons, passes the quiz and earns a certificate', function () {
    $course = publishedCourse();
    $member = learnerMember();
    $this->actingAs($member->user);

    $this->get(route('learn.index'))->assertOk()->assertSee('Intro Course');
    $this->post(route('learn.enroll', $course))->assertRedirect(route('learn.lesson', [$course, $course->lessons->first()]));

    // the quiz is locked until every lesson is done
    $this->get(route('learn.quiz', $course))->assertRedirect(route('learn.show', $course));

    finishLessons($course);
    $enrollment = Enrollment::first();
    expect($enrollment->completions()->count())->toBe(2)->and($enrollment->isComplete())->toBeFalse();

    // failing attempt: nothing is issued
    $questions = $course->questions;
    $this->post(route('learn.quiz.submit', $course), ['answers' => [$questions[0]->id => 0, $questions[1]->id => 0]])
        ->assertRedirect();
    $failed = $enrollment->attempts()->first();
    expect($failed->score)->toBe(0)->and($failed->passed)->toBeFalse()->and($enrollment->fresh()->isComplete())->toBeFalse();
    $this->get(route('learn.certificate', $course))->assertNotFound();

    // passing attempt
    $this->post(route('learn.quiz.submit', $course), ['answers' => [$questions[0]->id => 1, $questions[1]->id => 1]])
        ->assertRedirect();
    $enrollment->refresh();
    expect($enrollment->isComplete())->toBeTrue()
        ->and($enrollment->certificate_number)->toStartWith('CRT-'.now()->format('Y').'-');

    $this->get(route('learn.certificate', $course))->assertOk()->assertSee($enrollment->certificate_number)->assertSee('Lia Cruz');
    $this->get(route('learn.quiz.result', [$course, $enrollment->attempts()->latest('id')->first()]))->assertOk()->assertSee('Passed');
});

test('a course without a quiz completes when the last lesson is done', function () {
    $course = publishedCourse(2, false);
    $this->actingAs(learnerMember()->user);

    $this->post(route('learn.enroll', $course));
    finishLessons($course);

    expect(Enrollment::first()->isComplete())->toBeTrue();
});

test('the quiz must be fully answered', function () {
    $course = publishedCourse(1);
    $this->actingAs(learnerMember()->user);
    $this->post(route('learn.enroll', $course));
    finishLessons($course);

    $this->post(route('learn.quiz.submit', $course), ['answers' => [$course->questions[0]->id => 1]])
        ->assertSessionHas('error');
    expect(Enrollment::first()->attempts()->count())->toBe(0);
});

test('draft and lesson-less courses are hidden from members', function () {
    $draft = publishedCourse(1, false, ['title' => 'Draft one', 'is_published' => false]);
    $empty = Course::create(['title' => 'No lessons', 'pass_mark' => 70, 'is_published' => true]);
    $this->actingAs(learnerMember()->user);

    $this->get(route('learn.index'))->assertOk()->assertDontSee('Draft one')->assertDontSee('No lessons');
    $this->get(route('learn.show', $draft))->assertNotFound();
    $this->get(route('learn.show', $empty))->assertNotFound();
    $this->post(route('learn.enroll', $draft))->assertNotFound();
});

test('a course can require a minimum membership level', function () {
    $course = publishedCourse(1, false, ['title' => 'Silver only', 'membership_level_id' => $this->silver->id]);

    $this->actingAs(learnerMember()->user);
    $this->post(route('learn.enroll', $course))->assertSessionHas('error');
    expect(Enrollment::count())->toBe(0);

    $this->actingAs(learnerMember(['membership_level_id' => $this->silver->id])->user);
    $this->post(route('learn.enroll', $course))->assertRedirect();
    expect(Enrollment::count())->toBe(1);
});

test('only members with an active membership can learn', function (string $status) {
    $course = publishedCourse(1, false);
    $this->actingAs(learnerMember(['status' => $status])->user);

    $this->post(route('learn.enroll', $course))->assertSessionHas('error');
    expect(Enrollment::count())->toBe(0);
})->with([Member::STATUS_PENDING, Member::STATUS_EXPIRED, Member::STATUS_INACTIVE]);

test('lapsed members cannot continue a course but keep their certificate', function () {
    $course = publishedCourse(1, false);
    $member = learnerMember();
    $this->actingAs($member->user);
    $this->post(route('learn.enroll', $course));
    finishLessons($course);
    expect(Enrollment::first()->isComplete())->toBeTrue();

    $member->update(['status' => Member::STATUS_EXPIRED]);
    $this->actingAs($member->user()->first()); // a fresh user instance, so the member record is reloaded
    $this->get(route('learn.lesson', [$course, $course->lessons->first()]))->assertRedirect(route('learn.index'));
    $this->get(route('learn.certificate', $course))->assertOk();
});

test('members cannot open lessons without enrolling or reach another member\'s results', function () {
    $course = publishedCourse(1);
    $lesson = $course->lessons->first();

    $this->actingAs(learnerMember()->user);
    $this->get(route('learn.lesson', [$course, $lesson]))->assertRedirect(route('learn.show', $course));

    // another member takes the quiz
    $other = learnerMember();
    $this->actingAs($other->user);
    $this->post(route('learn.enroll', $course));
    finishLessons($course);
    $this->post(route('learn.quiz.submit', $course), ['answers' => [$course->questions[0]->id => 1, $course->questions[1]->id => 1]]);
    $attempt = Enrollment::firstWhere('member_id', $other->id)->attempts()->first();

    // a third member who is enrolled too cannot see it
    $third = learnerMember();
    $this->actingAs($third->user);
    $this->post(route('learn.enroll', $course));
    $this->get(route('learn.quiz.result', [$course, $attempt]))->assertNotFound();
});

test('a lesson from another course is not reachable through this one', function () {
    $course = publishedCourse(1, false);
    $foreign = publishedCourse(1, false, ['title' => 'Other'])->lessons->first();

    $this->actingAs(learnerMember()->user);
    $this->post(route('learn.enroll', $course));
    $this->get(route('learn.lesson', [$course, $foreign]))->assertNotFound();
    $this->post(route('learn.lesson.complete', [$course, $foreign]))->assertNotFound();
});

test('staff accounts cannot use the member learning area and members cannot use course management', function () {
    $this->actingAs(User::factory()->create());
    $this->get(route('learn.index'))->assertForbidden();

    $this->actingAs(learnerMember()->user);
    $this->get(route('courses.index'))->assertForbidden();
    $this->get(route('courses.create'))->assertForbidden();
});

test('lesson text is rendered as safe markdown', function () {
    $lesson = new Lesson(['content' => "## Heading\n\n<script>alert(1)</script>\n\n[bad](javascript:alert(1))"]);
    $html = $lesson->renderedContent();

    expect($html)->toContain('<h2>Heading</h2>')->not->toContain('<script>')->not->toContain('javascript:');
});

test('video links are turned into embeddable players', function () {
    expect((new Lesson(['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']))->embedUrl())->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and((new Lesson(['video_url' => 'https://youtu.be/dQw4w9WgXcQ']))->embedUrl())->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and((new Lesson(['video_url' => 'https://vimeo.com/123456']))->embedUrl())->toBe('https://player.vimeo.com/video/123456')
        ->and((new Lesson(['video_url' => 'https://example.com/clip.mp4']))->embedUrl())->toBeNull();
});
