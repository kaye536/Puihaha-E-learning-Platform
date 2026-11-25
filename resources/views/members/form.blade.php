@extends('layouts.app')
@php($isEdit = $member->exists)
@section('title', $isEdit ? 'Edit Member' : 'Register Member')

@section('content')
<div class="page-head">
    <div>
        <span class="eyebrow">{{ $isEdit ? 'Member record' : 'New member' }}</span>
        <h1>{{ $isEdit ? 'Edit Member' : 'Register Member' }}</h1>
        <p class="muted">Fields marked <span class="req">*</span> are required.@unless ($isEdit) A confirmation email is sent to the member's address.@endunless</p>
    </div>
    <a href="{{ $isEdit ? route('members.show', $member) : route('members.index') }}" class="btn btn-light">Cancel</a>
</div>

<div class="card form-card">
    <form method="POST" action="{{ $isEdit ? route('members.update', $member) : route('members.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="form-section">
        <div class="fs-head"><span class="fs-num">1</span><div><h2>Personal details</h2><p>Who is the member and how can we reach them?</p></div></div>
        <div class="form-grid">
            <div class="form-group full @error('membership_number') has-error @enderror">
                <label for="membership_number">Membership Number</label>
                @if ($isEdit)
                    <input type="text" id="membership_number" value="{{ $member->membership_number }}" disabled>
                    <span class="hint">The membership number is permanent once issued.</span>
                @else
                    <input type="text" id="membership_number" name="membership_number"
                           value="{{ old('membership_number', $suggestedNumber) }}" maxlength="30">
                    <span class="hint">Generated automatically. You may change it, but it must be unique.</span>
                @endif
                @error('membership_number')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('first_name') has-error @enderror">
                <label for="first_name">First Name <span class="req">*</span></label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $member->first_name) }}" required maxlength="100">
                @error('first_name')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group @error('last_name') has-error @enderror">
                <label for="last_name">Last Name <span class="req">*</span></label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $member->last_name) }}" required maxlength="100">
                @error('last_name')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('email') has-error @enderror">
                <label for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $member->email) }}" required maxlength="255">
                @error('email')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group @error('phone') has-error @enderror">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $member->phone) }}" maxlength="30">
                @error('phone')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('birthdate') has-error @enderror">
                <label for="birthdate">Birthdate</label>
                <input type="date" id="birthdate" name="birthdate" value="{{ old('birthdate', $member->birthdate?->format('Y-m-d')) }}" max="{{ now()->subDay()->format('Y-m-d') }}">
                @error('birthdate')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group @error('address') has-error @enderror">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="{{ old('address', $member->address) }}" maxlength="500">
                @error('address')<span class="error-text">{{ $message }}</span>@enderror
            </div>

        </div>
        </section>

        <section class="form-section">
        <div class="fs-head"><span class="fs-num">2</span><div><h2>Membership</h2><p>Choose the category and level. The fee comes from the level.</p></div></div>
        <div class="form-grid">
            <div class="form-group @error('membership_category_id') has-error @enderror">
                <label for="membership_category_id">Membership Category <span class="req">*</span></label>
                <select id="membership_category_id" name="membership_category_id" required>
                    <option value="">— Select category —</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('membership_category_id', $member->membership_category_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('membership_category_id')<span class="error-text">{{ $message }}</span>@enderror
            </div>
            <div class="form-group @error('membership_level_id') has-error @enderror">
                <label for="membership_level_id">Membership Level <span class="req">*</span></label>
                <select id="membership_level_id" name="membership_level_id" required>
                    <option value="">— Select level —</option>
                    @foreach ($levels as $l)
                        <option value="{{ $l->id }}" @selected(old('membership_level_id', $member->membership_level_id) == $l->id)>
                            {{ $l->name }} — @money($l->fee) / {{ $l->validity_months }} mo.
                        </option>
                    @endforeach
                </select>
                @error('membership_level_id')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>
        </section>

        @unless ($isEdit)
        <section class="form-section">
            <div class="fs-head"><span class="fs-num">3</span><div><h2>Application &amp; location</h2><p>Optionally open the application now and pin where the applicant is.</p></div></div>
            <div class="form-grid">
                <div class="form-group full">
                    <label class="check-card">
                        <input type="hidden" name="create_application" value="0">
                        <input type="checkbox" name="create_application" value="1" @checked(old('create_application', '1') == '1')>
                        <span>
                            <strong>Create the membership application now</strong>
                            <span class="hint">A pending transaction is created using the level's fee, and the member receives an application email with their membership number and reference number.</span>
                        </span>
                    </label>
                </div>

                <div class="form-group full @error('latitude') has-error @enderror @error('longitude') has-error @enderror">
                    <label>Applicant location <span class="hint" style="font-weight:500">(optional)</span></label>
                    @include('partials.location-map', ['editable' => true])
                    @error('latitude')<span class="error-text">{{ $message }}</span>@enderror
                    @error('longitude')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>
        @endunless

        <div class="form-actions">
            <span class="note">{{ $isEdit ? 'Changes apply immediately.' : 'The member is registered as Pending until approved.' }}</span>
            <button type="submit" class="btn btn-lg">{{ $isEdit ? 'Save Changes' : 'Register Member' }}</button>
        </div>
    </form>
</div>
@endsection
