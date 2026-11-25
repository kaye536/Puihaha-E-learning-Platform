<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PortalSetupController;
use App\Http\Controllers\PlaceSearchController;
use App\Http\Controllers\PublicApplicationController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route(auth()->user()->homeRoute()) : view('welcome'))->name('home');

// Public membership application (no login): form, submit (rate limited), confirmation
Route::get('/membership/apply', [PublicApplicationController::class, 'create'])->name('membership.apply');
Route::post('/membership/apply', [PublicApplicationController::class, 'store'])
    ->middleware('throttle:membership-apply')->name('membership.apply.store');
Route::get('/membership/apply/thanks', [PublicApplicationController::class, 'thanks'])->name('membership.apply.thanks');

// Live weather for a map location. Public (used by the application form) but rate limited;
// the OpenWeather key stays on the server.
Route::get('/weather', [WeatherController::class, 'show'])->middleware('throttle:30,1')->name('weather.show');

// Place search + reverse geocoding for the map pin (public, rate limited, no API key needed).
Route::get('/places/search', [PlaceSearchController::class, 'search'])->middleware('throttle:60,1')->name('places.search');
Route::get('/places/reverse', [PlaceSearchController::class, 'reverse'])->middleware('throttle:60,1')->name('places.reverse');

// Member portal invitation: a signed, expiring link emailed when the application is approved.
Route::get('/portal/setup/{user}', [PortalSetupController::class, 'show'])->middleware('signed')->name('portal.setup');
Route::post('/portal/setup/{user}', [PortalSetupController::class, 'store'])->middleware(['signed', 'throttle:10,1'])->name('portal.setup.store');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');

    // Forgot / reset password (staff and members)
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Everything below needs a signed-in, active account. Each controller then enforces
// its own permissions (see config/rbac.php and each controller's middleware()).
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Member portal: a member's own record only (see PortalController)
    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'index'])->name('index');
        Route::get('/history', [PortalController::class, 'history'])->name('history');
        Route::get('/receipts/{payment}', [PortalController::class, 'receipt'])->name('receipt');
        Route::get('/renew', [PortalController::class, 'renewForm'])->name('renew');
        Route::post('/renew', [PortalController::class, 'renew'])->name('renew.store');
        Route::patch('/profile', [PortalController::class, 'updateProfile'])->name('profile.update');
    });

    // E-learning, member side: browse courses, study lessons, take the final quiz, print the certificate
    Route::prefix('learn')->name('learn.')->group(function () {
        Route::get('/', [LearningController::class, 'index'])->name('index');
        Route::get('/{course}', [LearningController::class, 'show'])->name('show');
        Route::post('/{course}/enroll', [LearningController::class, 'enroll'])->name('enroll');
        Route::get('/{course}/lessons/{lesson}', [LearningController::class, 'lesson'])->name('lesson');
        Route::post('/{course}/lessons/{lesson}/complete', [LearningController::class, 'complete'])->name('lesson.complete');
        Route::get('/{course}/quiz', [LearningController::class, 'quiz'])->name('quiz');
        Route::post('/{course}/quiz', [LearningController::class, 'submitQuiz'])->name('quiz.submit');
        Route::get('/{course}/quiz/{attempt}', [LearningController::class, 'result'])->name('quiz.result');
        Route::get('/{course}/certificate', [LearningController::class, 'certificate'])->name('certificate');
    });

    // E-learning, staff side: courses, their lessons and quiz questions (+ learner progress on the course page)
    Route::resource('courses', CourseController::class);
    Route::resource('courses.lessons', LessonController::class)->shallow()->except(['index', 'show']);
    Route::resource('courses.questions', QuestionController::class)->shallow()->except(['index', 'show']);

    // Members
    Route::post('members/{member}/portal-invite', [MemberController::class, 'invitePortal'])->name('members.portal-invite');
    Route::get('members/{member}/history', [MemberController::class, 'history'])->name('members.history');
    Route::get('members/{member}/record', [MemberController::class, 'record'])->name('members.record');
    Route::patch('members/{member}/toggle-status', [MemberController::class, 'toggleStatus'])->name('members.toggle-status');
    Route::resource('members', MemberController::class);

    // Categories and levels
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('levels', LevelController::class)->except('show');

    // Transactions (applications and renewals)
    Route::post('transactions/{transaction}/approve', [TransactionController::class, 'approve'])->name('transactions.approve');
    Route::post('transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'show']);

    // Staff accounts and roles (administrators only; enforced in UserController)
    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::resource('users', UserController::class)->except(['show', 'destroy']);

    // Payments (+ printable official receipt on show)
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show']);
});
