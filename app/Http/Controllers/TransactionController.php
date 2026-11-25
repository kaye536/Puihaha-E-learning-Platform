<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\TransactionRequest;
use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Services\MembershipService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller implements HasMiddleware
{
    /** Role-based access control: see config/rbac.php for which roles hold each permission. */
    public static function middleware(): array
    {
        return [
            new Middleware('can:transactions.view', only: ['index', 'show']),
            new Middleware('can:transactions.create', only: ['create', 'store']),
            new Middleware('can:transactions.approve', only: ['approve', 'cancel']),
        ];
    }

    public function __construct(private MembershipService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->service->syncExpirations();

        $term = trim((string) $request->query('q'));

        $transactions = MembershipTransaction::with(['member', 'level'])
            ->withSum('payments', 'amount')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('reference_number', 'like', "%{$term}%")
                        ->orWhereHas('member', fn ($m) => $m->search($term));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('transactions.index', ['transactions' => $transactions]);
    }

    public function create(Request $request): View
    {
        return view('transactions.create', [
            'members' => Member::where('status', '!=', Member::STATUS_INACTIVE)
                ->orderBy('last_name')->orderBy('first_name')->get(),
            'levels' => MembershipLevel::orderBy('fee')->get(),
            'selectedMember' => $request->query('member'),
        ]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        $member = Member::findOrFail($request->validated('member_id'));
        $level = MembershipLevel::findOrFail($request->validated('membership_level_id'));

        try {
            $transaction = $this->service->createTransaction(
                $member,
                $request->validated('type'),
                $level,
                $request->validated('remarks'),
                $request->user()->id,
                $request->filled('latitude') ? (float) $request->validated('latitude') : null,
                $request->filled('longitude') ? (float) $request->validated('longitude') : null,
            );
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['member_id' => $e->getMessage()]);
        }

        return redirect()->route('transactions.show', $transaction)
            ->with('success', "Transaction {$transaction->reference_number} created.");
    }

    public function show(MembershipTransaction $transaction): View
    {
        $this->service->syncExpirations();

        return view('transactions.show', [
            'transaction' => $transaction->refresh()->load(['member.category', 'level', 'payments.receiver', 'processor']),
        ]);
    }

    public function approve(MembershipTransaction $transaction): RedirectResponse
    {
        try {
            $this->service->approve($transaction);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transaction approved. The membership is now active.');
    }

    public function cancel(MembershipTransaction $transaction): RedirectResponse
    {
        try {
            $this->service->cancel($transaction);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transaction cancelled.');
    }
}
