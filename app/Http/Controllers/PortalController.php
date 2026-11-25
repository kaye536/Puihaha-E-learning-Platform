<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Services\MembershipService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Member portal. Every query is scoped to the signed-in member's own record:
 * nothing here accepts a member id from the request.
 */
class PortalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:portal.view'),
            new Middleware('can:portal.renew', only: ['renewForm', 'renew']),
            new Middleware('can:portal.profile', only: ['updateProfile']),
        ];
    }

    public function __construct(private MembershipService $service)
    {
    }

    public function index(): View
    {
        $this->service->syncExpirations();
        $member = $this->member()->load(['category', 'level']);

        return view('portal.index', [
            'member' => $member,
            'transactions' => $member->transactions()->with('level')->latest('id')->limit(5)->get(),
            'open' => $member->transactions()->whereIn('status', [MembershipTransaction::STATUS_PENDING, MembershipTransaction::STATUS_PAID])->latest('id')->first(),
            'renewal' => $this->renewalState($member),
        ]);
    }

    public function history(): View
    {
        $member = $this->member()->load(['category', 'level']);

        return view('portal.history', [
            'member' => $member,
            'transactions' => $member->transactions()->with('level')->latest('id')->get(),
            'payments' => $member->payments()->with('transaction')->latest('payment_date')->latest('payments.id')->get(),
        ]);
    }

    public function receipt(Payment $payment): View
    {
        $member = $this->member();

        // A receipt that is not the member's own does not exist as far as they are concerned.
        abort_unless($payment->transaction()->where('member_id', $member->id)->exists(), 404);

        return view('payments.receipt', [
            'payment' => $payment->load(['transaction.member', 'transaction.level', 'receiver']),
            'backUrl' => route('portal.history'),
            'backLabel' => 'Back to my history',
        ]);
    }

    public function renewForm(): View
    {
        $member = $this->member()->load('level');

        return view('portal.renew', [
            'member' => $member,
            'levels' => MembershipLevel::orderBy('fee')->get(),
            'renewal' => $this->renewalState($member),
        ]);
    }

    public function renew(Request $request): RedirectResponse
    {
        $member = $this->member();
        $data = $request->validate(['membership_level_id' => ['required', 'integer', 'exists:membership_levels,id']]);

        $state = $this->renewalState($member);
        if (! $state['allowed']) {
            return back()->with('error', $state['reason']);
        }

        try {
            $transaction = $this->service->createTransaction(
                $member,
                MembershipTransaction::TYPE_RENEWAL,
                MembershipLevel::findOrFail($data['membership_level_id']),
                'Requested online by the member.',
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('portal.index')->with('success',
            "Renewal requested (reference {$transaction->reference_number}). Please pay at the membership office and quote this reference; your membership is extended once the payment is approved.");
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
        ], ['phone.regex' => 'The phone number may only contain digits, spaces, + - and parentheses.']);

        $this->member()->update($data);

        return back()->with('success', 'Your contact details were updated.');
    }

    /** The signed-in user's own member record. Staff accounts have none and are turned away. */
    private function member(): Member
    {
        $member = auth()->user()->member;
        abort_unless($member, 403, 'The member portal is only for members.');

        return $member;
    }

    /** Can this member ask for a renewal right now, and if not, why? */
    private function renewalState(Member $member): array
    {
        $window = (int) config('membership.renewal_window_days');
        $expiry = $member->membership_expiry;

        $reason = match (true) {
            $member->isInactive() => 'Your membership is deactivated. Please contact the membership office.',
            $expiry === null => 'Your application is still being processed. You can renew once it has been approved.',
            $member->hasOpenTransaction() => 'You already have a request in progress. Please complete it at the membership office first.',
            $expiry->gt(today()->addDays($window)) => "Renewal opens {$window} days before your membership expires (".$expiry->format('M d, Y').').',
            default => null,
        };

        return ['allowed' => $reason === null, 'reason' => $reason];
    }
}
