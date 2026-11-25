@extends('layouts.app')
@section('title', 'New Transaction')

@section('content')
<div class="page-head">
    <div><span class="eyebrow">Transactions</span><h1>New Transaction</h1><p class="muted">Create a membership application or a renewal. The member is emailed a confirmation.</p></div>
    <a href="{{ route('transactions.index') }}" class="btn btn-light">Cancel</a>
</div>

<div class="card form-card form-narrow">
    <form method="POST" action="{{ route('transactions.store') }}">
        @csrf
        <section class="form-section">
        <div class="fs-head"><span class="fs-num">1</span><div><h2>Transaction details</h2><p>Pick the member, the type and the level.</p></div></div>
        <div class="form-grid">
            <div class="form-group full @error('member_id') has-error @enderror">
                <label for="member_id">Member <span class="req">*</span></label>
                <select id="member_id" name="member_id" required>
                    <option value="">— Select member —</option>
                    @foreach ($members as $m)
                        <option value="{{ $m->id }}"
                                data-renewal="{{ $m->membership_expiry ? '1' : '0' }}"
                                data-level="{{ $m->membership_level_id }}"
                                @selected(old('member_id', $selectedMember) == $m->id)>
                            {{ $m->full_name }} ({{ $m->membership_number }}) — {{ ucfirst($m->status) }}
                        </option>
                    @endforeach
                </select>
                @error('member_id')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('type') has-error @enderror">
                <label for="type">Transaction Type <span class="req">*</span></label>
                <select id="type" name="type" required>
                    @foreach (\App\Models\MembershipTransaction::TYPES as $ty)
                        <option value="{{ $ty }}" @selected(old('type') === $ty)>{{ ucfirst($ty) }}</option>
                    @endforeach
                </select>
                <span class="hint">New members use "Application"; members with an approved membership use "Renewal".</span>
                @error('type')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('membership_level_id') has-error @enderror">
                <label for="membership_level_id">Membership Level <span class="req">*</span></label>
                <select id="membership_level_id" name="membership_level_id" required>
                    <option value="">— Select level —</option>
                    @foreach ($levels as $l)
                        <option value="{{ $l->id }}" data-fee="{{ $l->fee }}" data-months="{{ $l->validity_months }}"
                                @selected(old('membership_level_id') == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
                @error('membership_level_id')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group full">
                <label>Amount Due</label>
                <div class="fee-box">
                    <div id="fee-preview" class="total">—</div>
                    <span class="hint">Taken from the selected level.</span>
                </div>
            </div>
        </div>
        </section>

        <section class="form-section">
        <div class="fs-head"><span class="fs-num">2</span><div><h2>Location &amp; notes</h2><p>Pin the applicant's location and add any remarks.</p></div></div>
        <div class="form-grid">

            <div class="form-group full @error('latitude') has-error @enderror @error('longitude') has-error @enderror">
                <label>Applicant location <span class="hint" style="font-weight:500">(optional)</span></label>
                @include('partials.location-map', ['editable' => true])
                @error('latitude')<span class="error-text">{{ $message }}</span>@enderror
                @error('longitude')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group full @error('remarks') has-error @enderror">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" maxlength="1000">{{ old('remarks') }}</textarea>
                @error('remarks')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>
        </section>

        <div class="form-actions">
            <span class="note">The transaction starts as Pending.</span>
            <button type="submit" class="btn btn-lg">Create Transaction</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var member = document.getElementById('member_id');
    var type = document.getElementById('type');
    var level = document.getElementById('membership_level_id');
    var preview = document.getElementById('fee-preview');
    var currency = @json(config('membership.currency'));
    var hadOldInput = @json(old('type') !== null);

    function updateFee() {
        var opt = level.options[level.selectedIndex];
        if (opt && opt.dataset.fee !== undefined) {
            preview.textContent = currency + parseFloat(opt.dataset.fee).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})
                + ' · valid for ' + opt.dataset.months + ' month(s)';
        } else { preview.textContent = '—'; }
    }

    function suggestFromMember() {
        var opt = member.options[member.selectedIndex];
        if (!opt || !opt.value) { return; }
        if (!hadOldInput) { type.value = opt.dataset.renewal === '1' ? 'renewal' : 'application'; }
        if (!level.value) { level.value = opt.dataset.level; }
        updateFee();
    }

    member.addEventListener('change', function () { hadOldInput = false; level.value = ''; suggestFromMember(); });
    level.addEventListener('change', updateFee);
    suggestFromMember();
    updateFee();
});
</script>
@endsection
