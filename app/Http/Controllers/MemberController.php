<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\MemberRequest;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Services\MembershipService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Notifications\MemberRegistered;
use App\Services\PortalAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class MemberController extends Controller implements HasMiddleware
{
    /** Role-based access control: see config/rbac.php for which roles hold each permission. */
    public static function middleware(): array
    {
        return [
            new Middleware('can:members.view', only: ['index', 'show', 'history', 'record']),
            new Middleware('can:members.manage', only: ['create', 'store', 'edit', 'update', 'destroy', 'toggleStatus', 'invitePortal']),
        ];
    }

    public function __construct(private MembershipService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->service->syncExpirations();

        $members = Member::with(['category', 'level'])
            ->search($request->query('q'))
            ->when($request->filled('category'), fn ($q) => $q->where('membership_category_id', $request->query('category')))
            ->when($request->filled('level'), fn ($q) => $q->where('membership_level_id', $request->query('level')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('members.index', [
            'members' => $members,
            'categories' => MembershipCategory::orderBy('name')->get(),
            'levels' => MembershipLevel::orderBy('fee')->get(),
        ]);
    }

    public function create(): View
    {
        return view('members.form', [
            'member' => new Member(),
            'categories' => MembershipCategory::orderBy('name')->get(),
            'levels' => MembershipLevel::orderBy('fee')->get(),
            'suggestedNumber' => Member::generateMembershipNumber(),
        ]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['create_application', 'latitude', 'longitude']);
        $data['status'] = Member::STATUS_PENDING;

        if (blank($data['membership_number'] ?? null)) {
            unset($data['membership_number']); // generated automatically by the model
        }

        $member = DB::transaction(function () use ($data, $request) {
            $member = Member::create($data);

            if ($request->boolean('create_application')) {
                $this->service->createTransaction(
                    $member,
                    MembershipTransaction::TYPE_APPLICATION,
                    $member->level,
                    'Membership application created at registration.',
                    $request->user()->id,
                    $request->filled('latitude') ? (float) $request->validated('latitude') : null,
                    $request->filled('longitude') ? (float) $request->validated('longitude') : null,
                );
            } else {
                // No application transaction (it sends its own email), so send the welcome email.
                // Queued; delivered only after this database transaction commits.
                Notification::route('mail', $member->email)
                    ->notify(new MemberRegistered($member));
            }

            return $member;
        });

        return redirect()->route('members.show', $member)
            ->with('success', "Member {$member->full_name} registered with membership number {$member->membership_number}. A confirmation email is on its way to {$member->email}.");
    }

    public function show(Member $member): View
    {
        $this->service->syncExpirations();
        $member->refresh()->load(['category', 'level']);

        return view('members.show', [
            'member' => $member,
            'transactions' => $member->transactions()->with('level')->withSum('payments', 'amount')->latest()->limit(5)->get(),
        ]);
    }

    public function edit(Member $member): View
    {
        return view('members.form', [
            'member' => $member,
            'categories' => MembershipCategory::orderBy('name')->get(),
            'levels' => MembershipLevel::orderBy('fee')->get(),
            'suggestedNumber' => null,
        ]);
    }

    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        // The membership number is permanent once issued.
        $member->update($request->safe()->except(['create_application', 'membership_number', 'latitude', 'longitude']));

        return redirect()->route('members.show', $member)->with('success', 'Member details updated successfully.');
    }

    /** Deactivate an active/pending/expired member, or re-activate an inactive one. */
    /** Create (or re-send the invitation to) the member's portal login. */
    public function invitePortal(Member $member, PortalAccountService $accounts): RedirectResponse
    {
        if ($member->membership_expiry === null) {
            return back()->with('error', 'Portal access is available once the membership has been approved.');
        }

        return match ($accounts->invite($member, resend: true)) {
            PortalAccountService::CREATED => back()->with('success', "Portal account created. An invitation was emailed to {$member->email}."),
            PortalAccountService::RESENT => back()->with('success', "A new invitation was emailed to {$member->email}."),
            PortalAccountService::ALREADY_ACTIVE => back()->with('error', 'This member already signs in to the portal. They can use "Forgot password" if needed.'),
            PortalAccountService::EMAIL_TAKEN => back()->with('error', 'A staff account already uses this email address, so a member login cannot be created for it.'),
            default => back(),
        };
    }

    public function toggleStatus(Member $member): RedirectResponse
    {
        if ($member->isInactive()) {
            $member->update(['status' => $member->restoredStatus()]);
            $message = 'Member re-activated.';
        } else {
            $member->update(['status' => Member::STATUS_INACTIVE]);
            $message = 'Member deactivated.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Member $member): RedirectResponse
    {
        if ($member->transactions()->exists()) {
            return back()->with('error', 'This member has transaction records and cannot be deleted. Deactivate the member instead.');
        }

        $member->delete();

        return redirect()->route('members.index')->with('success', 'Member deleted successfully.');
    }

    /** Member transaction history page. */
    public function history(Member $member): View
    {
        $this->service->syncExpirations();

        return view('members.history', [
            'member' => $member->load(['category', 'level']),
            'transactions' => $member->transactions()
                ->with(['level', 'payments'])
                ->withSum('payments', 'amount')
                ->latest()
                ->paginate(10),
            'totalPaid' => (float) $member->payments()->sum('payments.amount'),
        ]);
    }

    /** Printable membership record. */
    public function record(Member $member): View
    {
        $this->service->syncExpirations();

        return view('members.record', [
            'member' => $member->refresh()->load(['category', 'level']),
            'approvedTransactions' => $member->transactions()
                ->whereIn('status', [MembershipTransaction::STATUS_APPROVED, MembershipTransaction::STATUS_EXPIRED])
                ->with('level')
                ->orderBy('period_start')
                ->get(),
        ]);
    }
}
