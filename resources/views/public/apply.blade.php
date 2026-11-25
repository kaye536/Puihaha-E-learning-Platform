@extends('layouts.public')
@section('title', 'Apply for membership')

@section('content')
<section class="apply-hero">
    <span class="lp-badge"><i></i> Open for applications</span>
    <h1>Apply for <em>membership</em></h1>
    <p class="lead">It takes about two minutes. We’ll email you a confirmation with your membership number and reference number right away.</p>
</section>

@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>Please fix the following:</strong>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="apply-grid">
    <form method="POST" action="{{ route('membership.apply.store') }}" class="card form-card">
        @csrf
        <input type="hidden" name="form_token" value="{{ $formToken }}">
        {{-- Honeypot: hidden from people, tempting for bots --}}
        <div class="hp" aria-hidden="true">
            <label for="website">Leave this field empty</label>
            <input type="text" id="website" name="website" value="" tabindex="-1" autocomplete="off">
        </div>

        <section class="form-section">
            <div class="fs-head"><span class="fs-num">1</span><div><h2>About you</h2><p>We’ll send your confirmation to this email.</p></div></div>
            <div class="form-grid">
                <div class="form-group @error('first_name') has-error @enderror">
                    <label for="first_name">First name <span class="req">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="100" autocomplete="given-name">
                    @error('first_name')<span class="error-text">{{ $message }}</span>@enderror
                </div>
                <div class="form-group @error('last_name') has-error @enderror">
                    <label for="last_name">Last name <span class="req">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required maxlength="100" autocomplete="family-name">
                    @error('last_name')<span class="error-text">{{ $message }}</span>@enderror
                </div>
                <div class="form-group @error('email') has-error @enderror">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="you@gmail.com">
                    @error('email')<span class="error-text">{{ $message }}</span>@enderror
                </div>
                <div class="form-group @error('phone') has-error @enderror">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
                    @error('phone')<span class="error-text">{{ $message }}</span>@enderror
                </div>
                <div class="form-group @error('birthdate') has-error @enderror">
                    <label>Birthdate</label>
                    @include('partials.birthdate-picker', ['name' => 'birthdate', 'value' => old('birthdate'), 'max' => now()->subDay()->format('Y-m-d')])
                    @error('birthdate')<span class="error-text">{{ $message }}</span>@enderror
                </div>
                <div class="form-group @error('address') has-error @enderror">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="{{ old('address') }}" maxlength="500" autocomplete="street-address">
                    @error('address')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="form-section">
            <div class="fs-head"><span class="fs-num">2</span><div><h2>Choose your membership</h2><p>Pick a category, then the level that suits you.</p></div></div>
            <div class="form-grid">
                <div class="form-group full @error('membership_category_id') has-error @enderror">
                    <label for="membership_category_id">Category <span class="req">*</span></label>
                    <select id="membership_category_id" name="membership_category_id" required>
                        <option value="">— Select category —</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('membership_category_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('membership_category_id')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group full @error('membership_level_id') has-error @enderror">
                    <label>Level <span class="req">*</span></label>
                    <div class="level-grid">
                        @foreach ($levels as $l)
                            <label class="level-card">
                                <input type="radio" name="membership_level_id" value="{{ $l->id }}" @checked(old('membership_level_id') == $l->id) required>
                                <span class="level-body">
                                    <span class="level-name">{{ $l->name }}</span>
                                    <span class="level-fee">@money($l->fee)</span>
                                    <span class="level-meta">valid for {{ $l->validity_months }} month(s)</span>
                                    @if ($l->benefits)<span class="level-benefits">{{ \Illuminate\Support\Str::limit($l->benefits, 90) }}</span>@endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('membership_level_id')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="form-section">
            <div class="fs-head"><span class="fs-num">3</span><div><h2>Where are you?</h2><p>Optional. Search your address and we’ll pin it on the map and show the current weather there.</p></div></div>
            <div class="form-group @error('latitude') has-error @enderror @error('longitude') has-error @enderror">
                @include('partials.location-map', ['editable' => true, 'addressInput' => '#address'])
                @error('latitude')<span class="error-text">{{ $message }}</span>@enderror
                @error('longitude')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group @error('consent') has-error @enderror" style="margin-top:1.2rem">
                <label class="check-card">
                    <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                    <span>
                        <strong>I agree to my details being used to process this application</strong>
                        <span class="hint">This includes my contact details and, if I choose one, my map location. They are used only for my membership.</span>
                    </span>
                </label>
                @error('consent')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </section>

        <div class="form-actions">
            <span class="note">Your application starts as <strong>Pending</strong> until our staff review it.</span>
            <button type="submit" class="btn btn-lg">Submit application</button>
        </div>
    </form>

    <aside class="apply-aside">
        <div class="card next-steps">
            <h2>What happens next</h2>
            <ol>
                <li><span>1</span><div><strong>You apply</strong><small>Fill in the form and submit.</small></div></li>
                <li><span>2</span><div><strong>Confirmation email</strong><small>Your membership and reference numbers arrive in your inbox.</small></div></li>
                <li><span>3</span><div><strong>Staff review</strong><small>We verify your details and the fee.</small></div></li>
                <li><span>4</span><div><strong>You’re a member</strong><small>Your membership becomes active once approved and paid.</small></div></li>
            </ol>
        </div>
    </aside>
</div>
@endsection
