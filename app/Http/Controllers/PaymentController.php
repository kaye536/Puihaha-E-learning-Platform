<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\PaymentRequest;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Services\MembershipService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller implements HasMiddleware
{
    /** Role-based access control: see config/rbac.php for which roles hold each permission. */
    public static function middleware(): array
    {
        return [
            new Middleware('can:payments.view', only: ['index', 'show']),
            new Middleware('can:payments.record', only: ['create', 'store']),
        ];
    }

    public function __construct(private MembershipService $service)
    {
    }

    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q'));

        $payments = Payment::with('transaction.member')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('receipt_number', 'like', "%{$term}%")
                        ->orWhereHas('transaction', fn ($t) => $t->where('reference_number', 'like', "%{$term}%"))
                        ->orWhereHas('transaction.member', fn ($m) => $m->search($term));
                });
            })
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->query('method')))
            ->latest('payment_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'total' => (float) Payment::sum('amount'),
        ]);
    }

    public function create(Request $request): View
    {
        $payable = MembershipTransaction::with('member')
            ->withSum('payments', 'amount')
            ->where('status', MembershipTransaction::STATUS_PENDING)
            ->latest()
            ->get()
            ->filter(fn (MembershipTransaction $t) => $t->balance > 0)
            ->values();

        return view('payments.create', [
            'transactions' => $payable,
            'selected' => $request->query('transaction'),
        ]);
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $transaction = MembershipTransaction::findOrFail($request->validated('membership_transaction_id'));

        try {
            $payment = $this->service->recordPayment($transaction, $request->validated(), $request->user()->id);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', "Payment recorded. Official receipt {$payment->receipt_number} generated.");
    }

    /** Printable official receipt. */
    public function show(Payment $payment): View
    {
        return view('payments.receipt', [
            'payment' => $payment->load(['transaction.member', 'transaction.level', 'receiver']),
        ]);
    }
}
