<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicApplicationRequest;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Services\MembershipService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Public, no-login membership application. Creates a pending member and an
 * application transaction (with the chosen map location) and emails the applicant.
 */
class PublicApplicationController extends Controller
{
    /** Humans need at least this many seconds to fill the form; bots usually post instantly. */
    private const MIN_FILL_SECONDS = 4;

    public function __construct(private MembershipService $service)
    {
    }

    public function create(): View
    {
        return view('public.apply', [
            'categories' => MembershipCategory::orderBy('name')->get(),
            'levels' => MembershipLevel::orderBy('fee')->get(),
            // Signed timestamp used as a time-trap against bots.
            'formToken' => Crypt::encryptString((string) now()->timestamp),
        ]);
    }

    public function store(PublicApplicationRequest $request): RedirectResponse
    {
        // Honeypot: real visitors never see or fill this field. Pretend success so bots learn nothing.
        if ($request->filled('website')) {
            return redirect()->route('membership.apply.thanks')->with('application', [
                'name' => 'Applicant', 'email' => null, 'reference' => null, 'membership_number' => null,
            ]);
        }

        if (! $this->filledLikeAHuman($request->input('form_token'))) {
            return back()->withInput()->withErrors([
                'form_token' => 'That was submitted too quickly. Please review your details and try again.',
            ]);
        }

        $data = $request->validated();
        $level = MembershipLevel::findOrFail($data['membership_level_id']);

        [$member, $transaction] = DB::transaction(function () use ($data, $level) {
            $member = Member::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'birthdate' => $data['birthdate'] ?? null,
                'membership_category_id' => $data['membership_category_id'],
                'membership_level_id' => $level->id,
                'status' => Member::STATUS_PENDING,
            ]);

            // Queues the confirmation email (after commit) to the applicant.
            $transaction = $this->service->createTransaction(
                $member,
                MembershipTransaction::TYPE_APPLICATION,
                $level,
                'Submitted online by the applicant.',
                null,
                isset($data['latitude']) ? (float) $data['latitude'] : null,
                isset($data['longitude']) ? (float) $data['longitude'] : null,
            );

            return [$member, $transaction];
        });

        return redirect()->route('membership.apply.thanks')->with('application', [
            'name' => $member->first_name,
            'email' => $member->email,
            'reference' => $transaction->reference_number,
            'membership_number' => $member->membership_number,
        ]);
    }

    public function thanks(): View|RedirectResponse
    {
        if (! session()->has('application')) {
            return redirect()->route('membership.apply');
        }

        return view('public.thanks', ['application' => session('application')]);
    }

    private function filledLikeAHuman(?string $token): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            $startedAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return false;
        }

        return now()->timestamp - $startedAt >= self::MIN_FILL_SECONDS;
    }
}
