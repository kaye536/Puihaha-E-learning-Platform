<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Services\MembershipService;
use Illuminate\View\View;

class DashboardController extends Controller implements HasMiddleware
{
    /** Role-based access control: see config/rbac.php for which roles hold each permission. */
    public static function middleware(): array
    {
        return [
            new Middleware('can:dashboard.view'),
        ];
    }

    public function __invoke(MembershipService $service): View
    {
        $service->syncExpirations();

        return view('dashboard', [
            'totalMembers' => Member::count(),
            'activeMembers' => Member::where('status', Member::STATUS_ACTIVE)->count(),
            'pendingTransactions' => MembershipTransaction::where('status', MembershipTransaction::STATUS_PENDING)->count(),
            'awaitingApproval' => MembershipTransaction::where('status', MembershipTransaction::STATUS_PAID)->count(),
            'collectedPayments' => (float) Payment::sum('amount'),
            'collectedThisMonth' => (float) Payment::whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'expiringSoon' => Member::with('level')
                ->where('status', Member::STATUS_ACTIVE)
                ->whereBetween('membership_expiry', [today(), today()->addDays(30)])
                ->orderBy('membership_expiry')
                ->limit(5)
                ->get(),
            'recentTransactions' => MembershipTransaction::with('member')->latest()->limit(6)->get(),
            'recentPayments' => Payment::with('transaction.member')->latest()->limit(6)->get(),
            'categories' => MembershipCategory::withCount('members')->orderBy('name')->get(),
            'levels' => MembershipLevel::withCount('members')->orderBy('fee')->get(),
            'publishedCourses' => Course::where('is_published', true)->count(),
            'learners' => Enrollment::distinct()->count('member_id'),
            'certificates' => Enrollment::whereNotNull('completed_at')->count(),
        ]);
    }
}
