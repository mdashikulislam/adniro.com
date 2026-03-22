@php
    $category    ??= null;
    $city        ??= null;
    $catName     = data_get($category, 'name', '');
    $catPlural   = $catName ? $catName . '' : '';
    $cityName    = data_get($city, 'name', '');
    $cityIn      = $cityName ? 'in ' . $cityName : '';
    $countryName = config('country.name', '');
@endphp
<div class="container">
    <div class="row">
        <div class="col-lg-12">

            {{-- ── HERO BANNER ── --}}
            <div class="seo-hero">
                <h1>Discover {{ $catPlural }} {{ $cityIn }}</h1>
                <p>
                    Looking for trusted {{ $catPlural }} {{ $cityIn }}{{ $cityName ? ', ' : '' }}{{ $countryName }}?
                    Adniro connects you with real listings, verified sellers, and the best deals — all in one place.
                </p>
            </div>

            {{-- ── INTRO ── --}}
            <div class="mb-4">
                <h2 class="h4 fw-bold mb-2" style="color:#111827;">
                    Explore the Best {{ $catPlural }} Marketplace
                </h2>
                <p class="text-muted" style="max-width:760px; line-height:1.75;">
                    Adniro is designed to help users easily find, compare, and connect for {{ $catPlural }} {{ $cityIn }}.
                    Whether you are searching for the latest listings or planning to post your own, our platform provides a seamless experience.
                </p>
                <p class="text-muted" style="max-width:760px; line-height:1.75;">
                    With growing demand in {{ $countryName }}, more people are turning to online platforms to explore opportunities in {{ $catPlural }}.
                </p>
            </div>

            {{-- ── FEATURE CARDS ── --}}
            <div class="row g-3 mb-5">

                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="icon-wrap" style="background:#eff6ff;">🔎</div>
                        <h6>Smart Discovery</h6>
                        <p>Search thousands of {{ $catPlural }} listings using filters like price, condition, and location.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="icon-wrap" style="background:#fef9c3;">⚡</div>
                        <h6>Quick Connections</h6>
                        <p>Contact sellers instantly and make faster decisions with real-time communication.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="icon-wrap" style="background:#fce7f3;">📢</div>
                        <h6>Easy Listing</h6>
                        <p>Post your {{ $catName }} listing in minutes and reach buyers {{ $cityIn }}.</p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <div class="icon-wrap" style="background:#dcfce7;">🌍</div>
                        <h6>Wide Reach</h6>
                        <p>Expand visibility and connect with users across {{ $countryName }}.</p>
                    </div>
                </div>

            </div>

            {{-- ── WHY ADNIRO ── --}}
            <div class="why-section">
                <h2 class="h5 fw-bold mb-3" style="color:#111827;">Why Choose Adniro?</h2>
                <p class="text-muted mb-3" style="max-width:680px; line-height:1.75;">
                    Adniro focuses on simplicity, speed, and trust — making it easier to browse listings and connect with real users.
                </p>
                <ul class="why-check-list">
                    <li>
                        <span class="check-icon"><i class="bi bi-check-lg"></i></span>
                        Fast and simple listing system
                    </li>
                    <li>
                        <span class="check-icon"><i class="bi bi-check-lg"></i></span>
                        Local and global reach
                    </li>
                    <li>
                        <span class="check-icon"><i class="bi bi-check-lg"></i></span>
                        Secure and transparent platform
                    </li>
                    <li>
                        <span class="check-icon"><i class="bi bi-check-lg"></i></span>
                        Mobile-friendly design
                    </li>
                </ul>
            </div>

            {{-- ── DEMAND BLOCK ── --}}
            <div class="demand-block">
                <h2 class="h5 fw-bold mb-2" style="color:#111827;">
                    Demand for {{ $catPlural }} {{ $cityIn }}
                </h2>
                <p class="text-muted mb-0" style="line-height:1.75;">
                    The demand for {{ $catPlural }} {{ $cityIn }} is growing as more users prefer online marketplaces.
                    Adniro helps buyers find better deals while giving sellers faster exposure.
                </p>
            </div>

            {{-- ── MAP ── --}}
            <div class="map-wrapper mb-5">
                <iframe
                    src="https://www.google.com/maps/embed/v1/place?q={{ urlencode($cityName) }},{{ urlencode($countryName) }}&key=AIzaSyDu7a50sYbSrfgaOtdXLDPzBhKOxcM2gss"
                    width="100%" height="340" style="border:0;" loading="lazy" allowfullscreen>
                </iframe>
            </div>

            {{-- ── FAQ ── --}}
            <div class="mb-5">
                <h2 class="h5 fw-bold mb-3" style="color:#111827;">Frequently Asked Questions</h2>

                <div class="accordion faq-modern" id="faqAccordion">

                    {{-- 1 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true">
                                Is it free to post {{ $catName }} listings {{ $cityIn }}?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, Adniro allows users to post {{ $catName }} listings for free. Premium options are available to boost visibility.
                            </div>
                        </div>
                    </div>

                    {{-- 2 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false">
                                How can I find the best {{ $catPlural }} {{ $cityIn }}?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Use filters like price, condition, and location to quickly discover the most relevant {{ $catPlural }} listings.
                            </div>
                        </div>
                    </div>

                    {{-- 3 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false">
                                Can I contact sellers directly?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, each listing provides direct contact options so you can communicate with sellers easily.
                            </div>
                        </div>
                    </div>

                    {{-- 4 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false">
                                Are the {{ $catPlural }} listings verified?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We encourage verified profiles and provide tools to help users identify trusted listings.
                            </div>
                        </div>
                    </div>

                    {{-- 5 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false">
                                What types of {{ $catPlural }} can I find?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                You can find a wide variety of {{ $catPlural }}, depending on the category, including new, used, and service-related listings.
                            </div>
                        </div>
                    </div>

                    {{-- 6 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq6" aria-expanded="false">
                                How do I post a {{ $catName }} ad?
                            </button>
                        </h2>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Simply create an account, click on "Post Ad", and fill in the details of your {{ $catName }} listing.
                            </div>
                        </div>
                    </div>

                    {{-- 7 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq7" aria-expanded="false">
                                How long do listings stay active?
                            </button>
                        </h2>
                        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Listings usually remain active for a set period, after which they can be renewed or updated.
                            </div>
                        </div>
                    </div>

                    {{-- 8 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq8" aria-expanded="false">
                                Can businesses post listings on Adniro?
                            </button>
                        </h2>
                        <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, businesses can post listings and promote their products or services to a larger audience.
                            </div>
                        </div>
                    </div>

                    {{-- 9 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq9" aria-expanded="false">
                                How can I improve my listing visibility?
                            </button>
                        </h2>
                        <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Use clear titles, detailed descriptions, and high-quality images. You can also upgrade to featured listings.
                            </div>
                        </div>
                    </div>

                    {{-- 10 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq10" aria-expanded="false">
                                Is Adniro available in {{ $countryName }}?
                            </button>
                        </h2>
                        <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, Adniro supports listings across multiple regions including {{ $countryName }}.
                            </div>
                        </div>
                    </div>

                    {{-- 11 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq11" aria-expanded="false">
                                Can I edit or delete my listing?
                            </button>
                        </h2>
                        <div id="faq11" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, you can manage your listings anytime from your dashboard.
                            </div>
                        </div>
                    </div>

                    {{-- 12 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq12" aria-expanded="false">
                                What should I check before contacting a seller?
                            </button>
                        </h2>
                        <div id="faq12" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Always review the listing details, images, and seller information before making contact.
                            </div>
                        </div>
                    </div>

                    {{-- 13 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq13" aria-expanded="false">
                                Are there any safety tips for buyers?
                            </button>
                        </h2>
                        <div id="faq13" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Meet sellers in safe locations and avoid making payments before verifying the listing.
                            </div>
                        </div>
                    </div>

                    {{-- 14 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq14" aria-expanded="false">
                                How often are new listings added?
                            </button>
                        </h2>
                        <div id="faq14" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                New {{ $catPlural }} listings are added regularly, so check back frequently for updates.
                            </div>
                        </div>
                    </div>

                    {{-- 15 --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq15" aria-expanded="false">
                                Why choose Adniro over other platforms?
                            </button>
                        </h2>
                        <div id="faq15" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Adniro offers a modern interface, better search tools, and a growing marketplace for faster and easier transactions.
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@push('after_helpers_styles_stack')
    @parent
    <style>
        .seo-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0d9488 100%);
            border-radius: 1.25rem;
            padding: 3.5rem 2.5rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 2.5rem;
        }
        .seo-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,.05);
            border-radius: 50%;
            pointer-events: none;
        }
        .seo-hero::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 220px; height: 220px;
            background: rgba(255,255,255,.04);
            border-radius: 50%;
            pointer-events: none;
        }
        .seo-hero h1 {
            color: #fff;
            font-size: clamp(1.6rem, 3.5vw, 2.4rem);
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -.02em;
            margin-bottom: .9rem;
            position: relative;
        }
        .seo-hero p {
            color: rgba(255,255,255,.75);
            font-size: 1.05rem;
            max-width: 680px;
            margin-bottom: 0;
            line-height: 1.75;
            position: relative;
        }

        /* ── Feature Cards ── */
        .feature-card {
            border: 1px solid #f1f5f9;
            border-radius: 1rem;
            padding: 1.75rem 1.5rem;
            background: #fff;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 40px rgba(26,86,219,.1);
            border-color: #bfdbfe;
        }
        .feature-card .icon-wrap {
            width: 52px; height: 52px;
            border-radius: .75rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.1rem;
        }
        .feature-card h6 {
            font-size: .95rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: .5rem;
        }
        .feature-card p {
            font-size: .875rem;
            color: #6b7280;
            line-height: 1.65;
            margin: 0;
        }

        /* ── Why Section ── */
        .why-section {
            background: linear-gradient(135deg, #f8faff 0%, #eef4ff 100%);
            border: 1px solid #dbeafe;
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            margin-bottom: 2.5rem;
        }
        .why-check-list {
            list-style: none;
            padding: 0; margin: 0;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .65rem 1.5rem;
        }
        .why-check-list li {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            font-size: .9rem;
            color: #374151;
            line-height: 1.55;
        }
        .why-check-list li .check-icon {
            width: 20px; height: 20px;
            background: #dbeafe;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            margin-top: .15rem;
        }
        .why-check-list li .check-icon i {
            font-size: .65rem;
            color: #0d9488;
            font-weight: 900;
        }
        @media (max-width: 576px) {
            .why-check-list { grid-template-columns: 1fr; }
        }

        /* ── Demand Block ── */
        .demand-block {
            border-left: 4px solid #0d9488;
            background: #fff;
            border-radius: 0 .9rem .9rem 0;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.05);
            margin-bottom: 2.5rem;
        }

        /* ── Map ── */
        .map-wrapper {
            border-radius: 1.1rem;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2.5rem;
        }
        .map-wrapper iframe { display: block; }

        /* ── FAQ ── */
        .faq-modern .accordion-item {
            border: 1px solid #e5e7eb !important;
            border-radius: .85rem !important;
            margin-bottom: .65rem;
            overflow: hidden;
        }
        .faq-modern .accordion-button {
            font-size: .93rem;
            font-weight: 600;
            color: #111827;
            background: #fff;
            padding: 1.1rem 1.35rem;
            box-shadow: none !important;
        }
        .faq-modern .accordion-button:not(.collapsed) {
            background: #eff6ff;
            color: #0d9488;
        }
        .faq-modern .accordion-button:not(.collapsed)::after {
            filter: invert(28%) sepia(95%) saturate(600%) hue-rotate(205deg);
        }
        .faq-modern .accordion-body {
            font-size: .88rem;
            color: #4b5563;
            line-height: 1.7;
            padding: .85rem 1.35rem 1.1rem;
            background: #fff;
        }
    </style>
@endpush