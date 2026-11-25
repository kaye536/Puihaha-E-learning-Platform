<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#810000">
    <title>{{ config('app.name') }} – Launch and grow your membership</title>
    <meta name="description" content="Applications, renewals, payments and official receipts for your members, all in one place.">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="lp">

<header class="lp-nav">
    <div class="lp-wrap">
        <a href="{{ url('/') }}" style="text-decoration:none"><x-logo :size="36" word tone="maroon" /></a>
        <nav aria-label="Sections">
            <a href="#features">Features</a>
            <a href="#why">Why one tool</a>
            <a href="#start">Get started</a>
        </nav>
        <div class="cta">
            <a class="login" href="{{ route('login') }}">Staff log in</a>
            <a class="btn" href="{{ route('membership.apply') }}">Apply for membership</a>
        </div>
    </div>
</header>

<main>
<section class="lp-hero">
    <div class="lp-wrap">
        <span class="lp-badge"><i></i> Membership management, made simple</span>
        <h1>Launch and grow your <em>membership</em></h1>
        <p class="lead">Everything you need to run your register, from applications and renewals to payments and official receipts.</p>
        <div class="btns">
            <a class="btn btn-lg" href="{{ route('membership.apply') }}">Apply for membership</a>
            <a class="btn btn-light btn-lg" href="{{ route('login') }}">Staff sign in</a>
        </div>

        <div class="marquee" aria-hidden="true">
            <div class="marquee-track">
                @foreach (range(1, 2) as $i)
                    <span>Clubs</span><span>Associations</span><span>Schools</span><span>Cooperatives</span><span>Communities</span><span>Churches</span><span>Alumni groups</span><span>Sports leagues</span>
                @endforeach
            </div>
        </div>

        <div class="mock" aria-hidden="true">
            <div class="mock-bar"><i></i><i></i><i></i></div>
            <div class="mock-body">
                <div class="mock-side"><b class="on">Dashboard</b><b>Members</b><b>Categories</b><b>Levels</b><b>Transactions</b><b>Payments</b></div>
                <div class="mock-main">
                    <div class="mock-stats">
                        <div><small>Total members</small><strong>128</strong></div>
                        <div><small>Active members</small><strong>97</strong></div>
                        <div><small>Collected</small><strong>&#8369;48,250</strong></div>
                    </div>
                    <div class="row"><span class="av">MS</span><span class="grow">Maria Santos<small>Gold &middot; MEM-2026-00001</small></span><span class="badge badge-active">Active</span></div>
                    <div class="row"><span class="av">JC</span><span class="grow">Jun dela Cruz<small>Silver &middot; MEM-2026-00002</small></span><span class="badge badge-pending">Pending</span></div>
                    <div class="row"><span class="av">AR</span><span class="grow">Ana Reyes<small>Basic &middot; MEM-2026-00003</small></span><span class="badge badge-expired">Expired</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="lp-sec" id="features">
    <div class="lp-wrap">
        <div class="head">
            <h2>All-in-one membership register</h2>
            <p>Perfect for clubs, associations, schools and any group that needs to know who its members are and what they&rsquo;ve paid.</p>
        </div>
        <div class="bento">
            <article class="bcard">
                <div><h3>Members</h3><p>All of your member data in one place, searchable by name, number, category or status.</p></div>
                <div class="ui">
                    <div class="row"><span class="av">KO</span><span class="grow">Koray Okumus</span><span class="badge badge-active">Active</span></div>
                    <div class="row"><span class="av">LS</span><span class="grow">Lana Steiner</span><span class="badge badge-expired">Expired</span></div>
                    <div class="row"><span class="av">ZS</span><span class="grow">Zaid Schwartz</span><span class="badge badge-pending">Pending</span></div>
                </div>
            </article>
            <article class="bcard">
                <div><h3>Payments &amp; receipts</h3><p>Record payments and print an official receipt with your organization&rsquo;s seal in one click.</p></div>
                <div class="ui">
                    <div class="row"><span class="grow mono">OR-20261102-0001<small>Maria Santos</small></span><strong>&#8369;1,500.00</strong></div>
                    <div class="row"><span class="grow mono">OR-20261101-0002<small>Jun dela Cruz</small></span><strong>&#8369;1,500.00</strong></div>
                    <div class="row"><span class="grow mono">OR-20261030-0003<small>Ana Reyes</small></span><strong>&#8369;500.00</strong></div>
                </div>
            </article>
            <article class="bcard">
                <div><h3>Levels &amp; categories</h3><p>Set the fee, validity and benefits of every membership level, and group people your way.</p></div>
                <div class="tiers">
                    <div class="tier"><small>Basic</small><b>&#8369;500</b></div>
                    <div class="tier"><small>Silver</small><b>&#8369;1,000</b></div>
                    <div class="tier on"><small>Gold</small><b>&#8369;1,500</b></div>
                </div>
            </article>
            <article class="bcard">
                <div><h3>Applications &amp; renewals</h3><p>Every sign-up and renewal follows the same clear path, so nothing slips through.</p></div>
                <div class="steps"><span class="done">Applied</span><i></i><span class="done">Paid</span><i></i><span class="done">Approved</span><i></i><span>Renewal</span></div>
            </article>
            <article class="bcard wide dark">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:end">
                    <div>
                        <h3>Reporting you can read at a glance</h3>
                        <p>See collections, active members and who&rsquo;s expiring in the next 30 days the moment you sign in.</p>
                        <div class="big" style="margin-top:1.4rem">&#8369;48,250</div>
                        <p class="small" style="margin-top:.4rem">collected to date</p>
                    </div>
                    <div class="chart" aria-hidden="true"><i style="height:35%"></i><i style="height:52%"></i><i style="height:44%"></i><i style="height:68%"></i><i style="height:58%"></i><i style="height:82%"></i><i class="hi" style="height:100%"></i></div>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="lp-sec" id="why">
    <div class="lp-wrap">
        <div class="head">
            <h2>Quit juggling spreadsheets</h2>
            <p>Running a membership shouldn&rsquo;t mean five files, three chat threads and a shoebox of receipts.</p>
        </div>
        <div class="ba">
            <div class="col">
                <h3>Before</h3>
                <ul>
                    <li>Member lists scattered across spreadsheets</li>
                    <li>Handwritten receipts that go missing</li>
                    <li>No idea who is about to expire</li>
                    <li>Renewals tracked in someone&rsquo;s head</li>
                </ul>
            </div>
            <div class="col after">
                <h3>After</h3>
                <ul>
                    <li>One register for every member</li>
                    <li>Printable official receipts, numbered for you</li>
                    <li>An expiring-soon list on your dashboard</li>
                    <li>Applications and renewals with a clear status</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="lp-wrap" id="start">
    <div class="lp-cta">
        <h2>Get your register in order today</h2>
        <p>Sign in to add your first member, set your levels and start collecting.</p>
        <a class="btn btn-lg" href="{{ route('login') }}">Sign in to your dashboard</a>
    </div>
</section>
</main>

<footer class="lp-foot">
    <div class="lp-wrap">
        <x-logo :size="30" word tone="maroon" />
        <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
    </div>
</footer>
</body>
</html>
