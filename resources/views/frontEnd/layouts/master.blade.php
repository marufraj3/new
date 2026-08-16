<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', optional($generalsetting)->name ?? 'Shop')</title>

    <link rel="shortcut icon" href="{{ asset(optional($generalsetting)->favicon ?? 'public/logo.png') }}" />

    {{-- Fonts: Manrope (Latin) + Hind Siliguri (Bengali) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet" />

    @stack('seo')
    @stack('css')

    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/owl.carousel.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/owl.theme.default.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/select2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/backEnd/assets/css/toastr.min.css') }}" />
    {{-- ===== STOREFRONT DESIGN SYSTEM (overrides everything) ===== --}}
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/storefront.css') }}?v=3" />

    <style>
        :root{
            --c-primary: {{ optional($generalsetting)->primary_color ?? '#1C2A50' }};
            --c-accent: {{ optional($generalsetting)->secodery_color ?? '#E02B20' }};
            --c-primary-600: color-mix(in srgb, var(--c-primary) 85%, #000);
            --c-accent-600: color-mix(in srgb, var(--c-accent) 85%, #000);
            --c-primary-50: color-mix(in srgb, var(--c-primary) 9%, #fff);
            --c-accent-50: color-mix(in srgb, var(--c-accent) 8%, #fff);
        }
    </style>

    {{-- ========== DataLayer + GTM + Pixels (kept intact) ========== --}}
    @php
        $dl_page_type = Request::is('/') ? 'home'
            : (Request::is('product/*') ? 'product_detail'
            : (Request::is('category/*') || Request::is('subcategory/*') || Request::is('products/*') ? 'category'
            : (Request::is('cart') ? 'cart'
            : (Request::is('checkout') ? 'checkout'
            : (Request::is('customer/*') ? 'customer' : 'other')))));
    @endphp
    <script>
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({
            event: 'site_page_data',
            page_type: {{ json_encode($dl_page_type) }},
            page_url: {{ json_encode(url()->current()) }},
            currency: 'BDT',
            site_name: {{ json_encode(optional($generalsetting)->name ?? '') }}
        });
    </script>
    @foreach($gtm_code ?? [] as $gtm)
        @php $gtm_container_id = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-' . trim($gtm->code); @endphp
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtm_container_id }}');</script>
    @endforeach
    @if(isset($pixels) && $pixels->count() > 0)
        <script>
            !(function (f, b, e, v, n, t, s) {
                if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
                if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = "2.0"; n.queue = [];
                t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
            })(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");
            @foreach($pixels as $pixel)
            fbq('init', '{{{ $pixel->code }}}');
            @endforeach
            fbq('track', 'PageView');
        </script>
    @endif
</head>
<body class="gotop">

@foreach($gtm_code ?? [] as $gtm)
    @php $gtm_noscript_id = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-'.trim($gtm->code); @endphp
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm_noscript_id }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endforeach

@php
    $cartCount = Cart::instance('shopping')->count();
    $cartSubtotal = Cart::instance('shopping')->subtotal();
    $customer = Auth::guard('customer')->user();
@endphp

{{-- ================================================================
     MOBILE DRAWER (categories + account)
     ================================================================ --}}
<div class="sf-drawer-ovl" id="sfDrawerOvl" data-drawer-close></div>
<aside class="sf-drawer" id="sfDrawer" aria-label="Mobile menu">
    <div class="sf-drawer__head">
        <img src="{{ asset(optional($generalsetting)->white_logo ?? optional($generalsetting)->dark_logo ?? 'public/logo.png') }}" alt="{{ optional($generalsetting)->name }}" onerror="this.style.display='none'">
        <b>{{ optional($generalsetting)->name }}</b>
        <button class="sf-drawer__close" data-drawer-close aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="sf-drawer__body">
        <div class="sf-drawer__acct">
            @if($customer)
                <a href="{{ route('customer.account') }}"><i class="fa-solid fa-user"></i> My Account</a>
                <a href="{{ route('customer.orders') }}"><i class="fa-solid fa-box"></i> My Orders</a>
            @else
                <a href="{{ route('customer.login') }}"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                <a href="{{ route('customer.register') }}"><i class="fa-solid fa-user-plus"></i> Register</a>
            @endif
        </div>

        <div class="sf-drawer__ttl">Categories</div>
        <nav>
            @foreach($menucategories ?? [] as $scategory)
                <div class="sf-dcat">
                    <div class="sf-dcat__top">
                        @if(!empty($scategory->image))<img src="{{ asset($scategory->image) }}" alt="{{ $scategory->name }}">@else<span class="cat-ico"><i class="fa-solid fa-tag"></i></span>@endif
                        <a href="{{ url('category/'.$scategory->slug) }}">{{ $scategory->name }}</a>
                        @if($scategory->subcategories && $scategory->subcategories->count())<span class="tgl"><i class="fa-solid fa-chevron-down"></i></span>@endif
                    </div>
                    @if($scategory->subcategories && $scategory->subcategories->count())
                        <div class="sf-dcat__sub">
                            @foreach($scategory->subcategories as $sub)
                                <a href="{{ url('subcategory/'.$sub->slug) }}">{{ $sub->subcategoryName }}</a>
                                @foreach($sub->childcategories ?? [] as $child)
                                    <a href="{{ url('products/'.$child->slug) }}" style="padding-left:14px;color:var(--c-faint)">↳ {{ $child->childcategoryName }}</a>
                                @endforeach
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>

        <div class="sf-drawer__ttl">Quick Links</div>
        <nav class="sf-drawer__nav">
            <a href="{{ route('home') }}"><i class="fa-solid fa-house"></i> Home</a>
            <a href="{{ route('flashsales') }}" class="sale"><i class="fa-solid fa-bolt"></i> Flash Sale</a>
            <a href="{{ route('hotdeals') }}"><i class="fa-solid fa-fire"></i> Hot Deals</a>
            <a href="{{ route('shop') }}"><i class="fa-solid fa-store"></i> All Products</a>
            <a href="{{ route('customer.order_track') }}"><i class="fa-solid fa-truck"></i> Track Order</a>
            @if(($generalsetting?->vendor_enabled ?? 1) == 1)
                <a href="{{ route('sellers') }}"><i class="fa-solid fa-shop"></i> Sellers</a>
            @endif
            <a href="{{ route('blogs') }}"><i class="fa-solid fa-newspaper"></i> Blog</a>
            <a href="{{ route('contact') }}"><i class="fa-solid fa-headset"></i> Contact</a>
        </nav>
    </div>
</aside>

{{-- ================================================================
     MOBILE HEADER
     ================================================================ --}}
<div class="sf-mobile-head">
    <div class="sf-mobile-head__in">
        <button class="sf-icon-btn" data-drawer-open aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
        <a class="sf-logo" href="{{ route('home') }}">
            <span class="sf-logo__mark">SG</span>
            <span class="sf-logo__txt"><b>{{ optional($generalsetting)->name }}</b></span>
        </a>
        <div class="sf-mobile-head__acts">
            <button class="sf-icon-btn" id="sfMSearchBtn" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
            <a class="sf-icon-btn" href="{{ route('customer.checkout') }}" aria-label="Cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="sf-dot mobilecart-qty">{{ $cartCount }}</span>
            </a>
        </div>
    </div>
</div>
<div class="sf-msearch" id="sfMSearch">
    <form class="sf-search" action="{{ route('search') }}" method="GET">
        <div class="sf-search__box">
            <input type="text" name="keyword" class="msearch_keyword msearch_click" placeholder="Search products…" autocomplete="off" />
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
        <div class="sf-search-drop">
            <div class="sf-search-drop__head">Products</div>
            <div class="sf-search-drop__body"></div>
        </div>
    </form>
</div>

{{-- ================================================================
     DESKTOP HEADER
     ================================================================ --}}
<header class="sf-header" id="sfHeader">

    {{-- Topbar --}}
    <div class="sf-topbar">
        <div class="sf-container sf-topbar__in">
            <div class="sf-topbar__grp">
                @if(!empty(optional($contact)->hotline))
                    <a class="sf-topbar__hot" href="tel:{{ $contact->hotline }}"><i class="fa-solid fa-phone"></i> Hotline: {{ $contact->hotline }}</a>
                @endif
                @if(!empty(optional($generalsetting)->top_headline))
                    <span class="sf-hide-mobile" style="display:none">·</span>
                    <a href="{{ route('shop') }}" class="sf-hide-mobile"><i class="fa-solid fa-bullhorn"></i> {{ Str::limit(strip_tags(optional($generalsetting)->top_headline), 70) }}</a>
                @endif
            </div>
            <div class="sf-topbar__grp">
                <a href="{{ route('customer.order_track') }}"><i class="fa-solid fa-truck-fast"></i> Track Order</a>
                <a href="{{ route('contact') }}" class="sf-hide-mobile" style="display:inline-flex"><i class="fa-solid fa-headset"></i> Help Center</a>
                @if($customer)
                    <a href="{{ route('customer.account') }}" class="sf-hide-mobile" style="display:inline-flex"><i class="fa-solid fa-user"></i> Hi, {{ Str::limit($customer->name, 14) }}</a>
                @else
                    <a href="{{ route('customer.login') }}" class="sf-hide-mobile" style="display:inline-flex"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Main row: logo / search / actions --}}
    <div class="sf-header-main">
        <div class="sf-container sf-header-main__in">
            <a class="sf-logo" href="{{ route('home') }}">
                @if(!empty(optional($generalsetting)->dark_logo))
                    <img src="{{ asset(optional($generalsetting)->dark_logo) }}" alt="{{ optional($generalsetting)->name }}" />
                @else
                    <span class="sf-logo__mark">SG</span>
                    <span class="sf-logo__txt">
                        <b>{{ Str::before(optional($generalsetting)->name ?? 'Shop', ' ') }} <span>{{ Str::after(optional($generalsetting)->name ?? 'Genie', ' ') }}</span></b>
                        <small>Online Store</small>
                    </span>
                @endif
            </a>

            <form class="sf-search" action="{{ route('search') }}" method="GET" role="search">
                <div class="sf-search__box">
                    <input type="text" name="keyword" value="{{ request('keyword') }}" class="search_keyword search_click" placeholder="Search products, brands and more…" autocomplete="off" />
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i><span class="sf-hide-mobile">Search</span></button>
                </div>
                <div class="sf-search__trend">
                    <span>Trending:</span>
                    @foreach(['T-Shirt','Shoes','Watch','Headphone'] as $trend)
                        <a href="{{ route('search', ['keyword' => $trend]) }}">{{ $trend }}</a>
                    @endforeach
                </div>
                <div class="sf-search-drop">
                    <div class="sf-search-drop__head">Products</div>
                    <div class="sf-search-drop__body"></div>
                    <div class="sf-search-drop__foot"><a href="{{ route('search') }}">View all results <i class="fa-solid fa-arrow-right"></i></a></div>
                </div>
            </form>

            <div class="sf-head-actions">
                <div class="sf-head-user">
                    <button class="sf-head-user__btn" id="sfUserBtn" type="button">
                        @if($customer && $customer->image)
                            <span class="ava"><img src="{{ asset($customer->image) }}" alt=""></span>
                        @else
                            <span class="ava"><i class="fa-regular fa-user"></i></span>
                        @endif
                        <span class="sf-hide-mobile">{{ $customer ? Str::limit($customer->name, 10) : 'Account' }}</span>
                        <i class="fa-solid fa-chevron-down" style="font-size:10px"></i>
                    </button>
                    <div class="sf-head-user__menu" id="sfUserMenu">
                        @if($customer)
                            <a href="{{ route('customer.account') }}"><i class="fa-regular fa-user"></i> My Dashboard</a>
                            <a href="{{ route('customer.orders') }}"><i class="fa-solid fa-box"></i> My Orders</a>
                            <a href="{{ route('customer.refunds') }}"><i class="fa-solid fa-rotate-left"></i> Refunds</a>
                            <a href="{{ route('customer.profile_edit') }}"><i class="fa-solid fa-pen"></i> Edit Profile</a>
                            <hr>
                            <a class="danger" href="{{ route('customer.logout') }}" onclick="event.preventDefault();document.getElementById('sfLogoutForm').submit();"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                            <form id="sfLogoutForm" action="{{ route('customer.logout') }}" method="POST" style="display:none">@csrf</form>
                        @else
                            <a href="{{ route('customer.login') }}"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                            <a href="{{ route('customer.register') }}"><i class="fa-solid fa-user-plus"></i> Create Account</a>
                            <hr>
                            <a href="{{ route('customer.order_track') }}"><i class="fa-solid fa-truck-fast"></i> Track Order</a>
                        @endif
                    </div>
                </div>

                <a class="sf-head-cart" href="{{ route('customer.checkout') }}" id="cart-qty">
                    <span class="sf-head-cart__ico">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <b class="cart_count">{{ $cartCount }}</b>
                    </span>
                    <span class="sf-head-cart__txt">
                        <small>My Cart</small>
                        <b>৳{{ number_format((float) str_replace(',', '', $cartSubtotal)) }}</b>
                    </span>
                </a>
            </div>
        </div>
    </div>

    {{-- Navbar --}}
    <nav class="sf-navbar" aria-label="Main navigation">
        <div class="sf-container sf-navbar__in">

            <div class="sf-navbar__item" style="position:relative">
                <button class="sf-cats-trigger" id="sfCatsTrigger" type="button"><i class="fa-solid fa-bars-staggered"></i> All Categories</button>
                <div class="sf-cats-panel" id="sfCatsPanel">
                    @foreach($menucategories ?? [] as $cat)
                        <div class="sf-cats-panel__item">
                            @if(!empty($cat->image))<img src="{{ asset($cat->image) }}" alt="">@else<span class="cat-ico"><i class="fa-solid fa-tag"></i></span>@endif
                            <a href="{{ route('category', $cat->slug) }}">{{ $cat->name }}</a>
                            @if($cat->subcategories && $cat->subcategories->count())
                                <i class="fa-solid fa-angle-right"></i>
                                <div class="sf-cats-panel__sub">
                                    <h5><a href="{{ route('category', $cat->slug) }}">{{ $cat->name }}</a> — All Items</h5>
                                    <div class="sf-cats-panel__cols">
                                        @foreach($cat->subcategories as $sub)
                                            <div class="sf-cats-panel__subblock">
                                                <h5 style="margin-bottom:4px"><a href="{{ route('subcategory', $sub->slug) }}">{{ $sub->subcategoryName }}</a></h5>
                                                @foreach($sub->childcategories ?? [] as $child)
                                                    <a href="{{ route('products', $child->slug) }}">{{ $child->childcategoryName }}</a>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sf-navbar__item"><a href="{{ route('home') }}">Home</a></div>

            @foreach(($menucategories ?? [])->take(5) as $cat)
                <div class="sf-navbar__item">
                    <a href="{{ route('category', $cat->slug) }}">
                        {{ $cat->name }}
                        @if($cat->subcategories && $cat->subcategories->count())<i class="fa-solid fa-angle-down"></i>@endif
                    </a>
                    @if($cat->subcategories && $cat->subcategories->count())
                        <div class="sf-navbar__mega">
                            <div class="sf-navbar__mega__grid">
                                @foreach($cat->subcategories->take(4) as $sub)
                                    <div>
                                        <h6><a href="{{ route('subcategory', $sub->slug) }}">{{ $sub->subcategoryName }}</a></h6>
                                        @foreach($sub->childcategories ?? [] as $child)
                                            <a class="lvl2" href="{{ route('products', $child->slug) }}">{{ $child->childcategoryName }}</a>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <div class="sf-navbar__mega__foot">
                                <span class="sf-faint">Everything in {{ $cat->name }} at the best price</span>
                                <a class="sf-btn sf-btn--accent sf-btn--sm" href="{{ route('category', $cat->slug) }}" style="background:var(--c-accent);color:#fff">Shop Now <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if(($menucategories ?? collect())->count() > 5)
                <div class="sf-navbar__item">
                    <a href="{{ route('shop') }}">More <i class="fa-solid fa-angle-down"></i></a>
                    <div class="sf-navbar__mega" style="width:auto;min-width:560px">
                        <div class="sf-navbar__mega__grid" style="grid-template-columns:repeat(3,1fr)">
                            @foreach(($menucategories ?? [])->skip(5) as $cat)
                                <div>
                                    <h6><a href="{{ route('category', $cat->slug) }}">{{ $cat->name }}</a></h6>
                                    @foreach($cat->subcategories->take(5) ?? [] as $sub)
                                        <a class="lvl2" href="{{ route('subcategory', $sub->slug) }}">{{ $sub->subcategoryName }}</a>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if(($generalsetting?->vendor_enabled ?? 1) == 1)
                <div class="sf-navbar__item"><a href="{{ route('sellers') }}">Sellers</a></div>
            @endif
            <div class="sf-navbar__item"><a href="{{ route('blogs') }}">Blog</a></div>
            <div class="sf-navbar__item"><a href="{{ route('contact') }}">Contact</a></div>

            <div class="sf-navbar__right">
                <a href="{{ route('flashsales') }}"><i class="fa-solid fa-bolt"></i> Flash Sale</a>
                <a href="{{ route('hotdeals') }}"><i class="fa-solid fa-fire"></i> Hot Deals</a>
            </div>
        </div>
    </nav>
</header>

{{-- ================================================================
     PAGE CONTENT
     ================================================================ --}}
<main id="content">
    @yield('content')
</main>

{{-- ================================================================
     FOOTER
     ================================================================ --}}
<footer class="sf-footer">
    <div class="sf-footer__cta">
        <div class="sf-container sf-footer__cta-in">
            <div class="sf-footer__cta-txt">
                <h4><i class="fa-regular fa-envelope" style="margin-right:8px;color:#ffd9d4"></i>Get offers straight to your inbox</h4>
                <p>Subscribe for exclusive deals, new arrivals & voucher codes.</p>
            </div>
            <form class="sf-footer__cta-form" action="{{ route('frontend.newsletter.subscribe') }}" method="POST">
                @csrf
                <input type="email" name="email" placeholder="Enter your email address" required />
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </div>

    <div class="sf-footer__main">
        <div class="sf-container sf-footer__grid">
            <div class="sf-footer__brand">
                <img src="{{ asset(optional($generalsetting)->white_logo ?? optional($generalsetting)->dark_logo ?? 'public/logo.png') }}" alt="{{ optional($generalsetting)->name }}" />
                <p>{{ optional($generalsetting)->footer_about_text ?? 'Your trusted online shopping destination — quality products, honest prices and fast delivery across Bangladesh.' }}</p>
                <div class="sf-footer__contact">
                    @if(!empty(optional($contact)->hotline))<a href="tel:{{ $contact->hotline }}"><i class="fa-solid fa-phone"></i> {{ $contact->hotline }}</a>@endif
                    @if(!empty(optional($contact)->email))<a href="mailto:{{ $contact->email }}"><i class="fa-regular fa-envelope"></i> {{ $contact->email }}</a>@endif
                    @if(!empty(optional($contact)->address))<a href="#"><i class="fa-solid fa-location-dot"></i> {{ Str::limit($contact->address, 60) }}</a>@endif
                </div>
                <div class="sf-footer__social">
                    @foreach($socialicons ?? [] as $value)
                        <a href="{{ $value->link }}" target="_blank" rel="noopener" aria-label="Social"><i class="{{ $value->icon }}"></i></a>
                    @endforeach
                </div>
            </div>

            <div class="sf-footer__col">
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('shop') }}">All Products</a></li>
                    <li><a href="{{ route('flashsales') }}">Flash Sale</a></li>
                    <li><a href="{{ route('hotdeals') }}">Hot Deals</a></li>
                    <li><a href="{{ route('customer.order_track') }}">Track Order</a></li>
                    <li><a href="{{ route('complaint') }}">Complaints</a></li>
                    @foreach($pages ?? [] as $page)
                        <li><a href="{{ route('page', ['slug' => $page->slug]) }}">{{ $page->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="sf-footer__col">
                <h5>Customer Service</h5>
                <ul>
                    <li><a href="{{ route('customer.account') }}">My Account</a></li>
                    <li><a href="{{ route('customer.orders') }}">My Orders</a></li>
                    <li><a href="{{ route('customer.refunds') }}">Return & Refund</a></li>
                    <li><a href="{{ route('blogs') }}">Blog</a></li>
                    <li><a href="{{ route('contact') }}">Contact Us</a></li>
                    @foreach($pagesright ?? [] as $value)
                        <li><a href="{{ route('page', ['slug' => $value->slug]) }}">{{ $value->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="sf-footer__col">
                <h5>We Accept</h5>
                <div class="sf-footer__pay">
                    <img src="{{ asset('public/frontEnd/images/bkash-logo.png') }}" alt="bKash" />
                    <img src="{{ asset('public/frontEnd/images/nagad-logo.png') }}" alt="Nagad" />
                    <img src="{{ asset('public/frontEnd/images/rocket.png') }}" alt="Rocket" />
                    <img src="{{ asset('public/frontEnd/images/cod_logo.webp') }}" alt="Cash on Delivery" />
                </div>
                <div style="margin-top:16px">
                    <a href="{{ optional($generalsetting)->google_play_link ?? '#' }}" target="_blank" rel="noopener"><img src="{{ asset('public/uploads/play.svg') }}" alt="Google Play" style="height:44px;width:auto;margin:0 8px 8px 0" /></a>
                    <a href="{{ optional($generalsetting)->app_store_link ?? '#' }}" target="_blank" rel="noopener"><img src="{{ asset('public/uploads/app.png') }}" alt="App Store" style="height:44px;width:auto" /></a>
                </div>
            </div>
        </div>
    </div>

    <div class="sf-footer__bottom">
        <div class="sf-container sf-footer__bottom-in">
            <span>&copy; {{ date('Y') }} <a href="{{ route('home') }}">{{ optional($generalsetting)->name ?? config('app.name') }}</a> — All rights reserved.</span>
            <span>Designed by <a href="https://www.creativedesign.com.bd" target="_blank" rel="noopener">Creative Design</a></span>
        </div>
    </div>
</footer>

{{-- ================================================================
     WIDGETS
     ================================================================ --}}

{{-- Marketing popup (admin controlled) --}}
@php
    $popup = \Illuminate\Support\Facades\Cache::remember('active_frontend_popup', 300, function () {
        return \App\Models\Popup::where('status', 1)->latest()->first();
    });
@endphp
@if($popup && !empty(trim($popup->image ?? '')))
    <div id="sfPopup" class="sf-popup" aria-hidden="true">
        <div class="sf-popup__card">
            <button type="button" class="sf-popup__close" onclick="sfClosePopup()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            <a href="{{ !empty(trim($popup->link ?? '')) ? $popup->link : 'javascript:void(0)' }}" {{ !empty(trim($popup->link ?? '')) ? 'target="_blank"' : '' }}>
                <img src="{{ asset($popup->image) }}" alt="Offer" />
                @if(!empty(trim($popup->description ?? '')) || !empty(trim($popup->btn_text ?? '')))
                    <div class="sf-popup__body">
                        @if(!empty(trim($popup->description ?? '')))<p>{!! nl2br(e($popup->description)) !!}</p>@endif
                        @if(!empty(trim($popup->btn_text ?? '')))<span class="sf-btn sf-btn--primary sf-btn--sm">{{ $popup->btn_text }}</span>@endif
                    </div>
                @endif
            </a>
        </div>
    </div>
    <style>
        .sf-popup{position:fixed;inset:0;z-index:1500;display:none;align-items:center;justify-content:center;background:rgba(10,15,30,.62);padding:20px;backdrop-filter:blur(3px)}
        .sf-popup.show{display:flex}
        .sf-popup__card{position:relative;max-width:480px;width:100%;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,.4);animation:sfUp .35s cubic-bezier(.2,.8,.2,1)}
        .sf-popup__card img{width:100%;display:block}
        .sf-popup__body{padding:18px 20px;text-align:center;font-size:14px;color:var(--c-muted)}
        .sf-popup__body p{margin-bottom:12px}
        .sf-popup__close{position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:50%;border:0;background:rgba(255,255,255,.9);color:var(--c-text);cursor:pointer;z-index:2;font-size:14px;box-shadow:var(--sh-sm)}
    </style>
    <script>
        function sfClosePopup() { var p = document.getElementById('sfPopup'); if (p) p.classList.remove('show'); try { localStorage.setItem('sf_popup_closed', Date.now()); } catch (e) {} }
        document.addEventListener('DOMContentLoaded', function () {
            var p = document.getElementById('sfPopup');
            if (!p) return;
            var closed = null;
            try { closed = localStorage.getItem('sf_popup_closed'); } catch (e) {}
            if (closed && Date.now() - Number(closed) < 864e5) return;
            setTimeout(function () { p.classList.add('show'); }, 1800);
            p.addEventListener('click', function (e) { if (e.target === p) sfClosePopup(); });
        });
    </script>
@endif

{{-- Back to top --}}
<button class="sf-gotop" id="sfGotop" aria-label="Back to top"><i class="fa-solid fa-arrow-up"></i></button>

{{-- Chat widget --}}
<div class="sf-chat">
    <div class="sf-chat__opts" id="sfChatOpts">
        @if(!empty(optional($generalsetting)->facebook_page_username))
            <a class="sf-chat__btn sf-chat__btn--msg" href="https://m.me/{{ $generalsetting->facebook_page_username }}" target="_blank" rel="noopener" aria-label="Messenger"><i class="fab fa-facebook-messenger"></i></a>
        @endif
        @if(!empty(optional($contact)->whatsapp))
            <a class="sf-chat__btn sf-chat__btn--wa" href="https://wa.me/{{ $contact->whatsapp }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        @endif
        @if(!empty(optional($contact)->hotline))
            <a class="sf-chat__btn sf-chat__btn--call" href="tel:{{ $contact->hotline }}" aria-label="Call"><i class="fa-solid fa-phone"></i></a>
        @endif
    </div>
    <button class="sf-chat__btn sf-chat__btn--main" id="sfChatMain" aria-label="Chat with us"><i class="fa-solid fa-comment-dots"></i></button>
</div>

{{-- Cart drawer --}}
<div class="sf-cartdrawer-ovl" id="sfCartDrawerOvl" onclick="closeSidebarCart()"></div>
<div class="sf-cartdrawer" id="sfCartDrawer">
    <div id="sidebarCartContent">
        {{-- loaded via AJAX --}}
    </div>
</div>

{{-- Quick order modal --}}
@include('frontEnd.layouts.partials.quick-order-modal')

{{-- Legacy modal hooks --}}
<div id="custom-modal"></div>
<div id="page-overlay"></div>
<div id="loading"><div class="custom-loader"></div></div>

{{-- Mobile bottom nav --}}
<nav class="sf-bottom-nav" aria-label="Mobile navigation">
    <div class="sf-bottom-nav__in">
        <a href="{{ route('home') }}" class="{{ Route::is('home') ? 'active' : '' }}"><i class="fa-solid fa-house"></i> Home</a>
        <a href="javascript:void(0)" data-drawer-open><i class="fa-solid fa-table-cells-large"></i> Categories</a>
        <a href="{{ route('customer.checkout') }}" class="fab"><span class="fab-btn"><i class="fa-solid fa-cart-shopping"></i></span></a>
        <a href="{{ route('customer.order_track') }}" class="{{ Route::is('customer.order_track') ? 'active' : '' }}"><i class="fa-solid fa-truck-fast"></i> Track</a>
        @if($customer)
            <a href="{{ route('customer.account') }}" class="{{ Route::is('customer.account') ? 'active' : '' }}"><i class="fa-solid fa-user"></i> Account</a>
        @else
            <a href="{{ route('customer.login') }}"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
        @endif
    </div>
</nav>

{{-- ================================================================
     SCRIPTS
     ================================================================ --}}
<script>window.SF = window.SF || {}; SF.searchUrl = "{{ route('livesearch') }}"; SF.cartSidebarUrl = "{{ route('cart.sidebar') }}";</script>
<script src="{{ asset('public/frontEnd/js/jquery-3.6.3.min.js') }}"></script>
<script src="{{ asset('public/frontEnd/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('public/frontEnd/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('public/frontEnd/js/storefront.js') }}?v=3"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
<script>feather.replace();</script>
<script src="{{ asset('public/backEnd/assets/js/toastr.min.js') }}"></script>
{!! Toastr::message() !!}

<script>
    $(function () {
        $("#loading").hide();
        setTimeout(function () { $("#loading").hide(); }, 2500);
    });

    /* ---------- Sidebar cart drawer ---------- */
    function openSidebarCart() {
        if (window.SF) { SF.openCartDrawer(); }
        else { $("#sfCartDrawer").addClass("show"); $("#sfCartDrawerOvl").addClass("show"); $("body").addClass("sf-locked"); }
        sidebarCartRefresh();
    }
    function closeSidebarCart() {
        if (window.SF) { SF.closeCartDrawer(); }
        else { $("#sfCartDrawer").removeClass("show"); $("#sfCartDrawerOvl").removeClass("show"); $("body").removeClass("sf-locked"); }
    }
    function sidebarCartRefresh() {
        $.get("{{ route('cart.sidebar') }}", function (html) {
            $("#sidebarCartContent").html(html);
            if (typeof feather !== "undefined") feather.replace();
        });
    }
    /* Any button carrying [data-open-cart] opens the drawer */
    $(document).on("click", "[data-open-cart]", function (e) {
        e.preventDefault();
        openSidebarCart();
    });

    /* ---------- Quick view ---------- */
    $(document).on("click", ".quick_view", function () {
        var id = $(this).data("id");
        $("#loading").show();
        if (id) {
            $.ajax({
                type: "GET", data: { id: id }, url: "{{ route('quickview') }}",
                success: function (data) {
                    if (data) {
                        $("#custom-modal").html(data);
                        $("#custom-modal").show().css("display", "flex");
                        $("#page-overlay").show().addClass("show");
                        $("#loading").hide();
                    }
                }
            });
        }
    });
    $(document).on("click", "#page-overlay", function () {
        $("#custom-modal").hide();
        $("#page-overlay").hide().removeClass("show");
    });

    /* ---------- Add to cart (classic buttons) ---------- */
    function runFlyToCart($sourceEl, onComplete) {
        var $flyImg = null;
        if ($sourceEl && $sourceEl.closest && $sourceEl.closest('.sf-modal').length)
            $flyImg = $sourceEl.closest('.sf-modal').find('.sf-modal__head img').first();
        if (!$flyImg || !$flyImg.length)
            $flyImg = $sourceEl.closest('.sf-card, .sf-pd, .product_item, .wist_item').find('img').first();
        if (!$flyImg || !$flyImg.length) { if (typeof onComplete === 'function') onComplete(); return; }
        var rect = $flyImg[0].getBoundingClientRect();
        var $clone = $flyImg.clone().addClass('fly-to-cart-img').css({
            position: 'fixed', width: 80, height: 100, left: rect.left, top: rect.top,
            margin: 0, padding: 0, zIndex: 99999, borderRadius: 12,
            objectFit: 'cover', border: '2px solid #fff', boxShadow: '0 8px 30px rgba(0,0,0,.35)', pointerEvents: 'none'
        }).appendTo('body');
        var $target = $('.sf-head-cart').first();
        if (!$target.length || !$target.is(':visible')) $target = $('.sf-mobile-head__acts a[href*="checkout"]').first();
        var destRect = $target.length && $target.is(':visible') ? $target[0].getBoundingClientRect() : { left: $(window).width() - 60, top: $(window).height() / 2 - 40 };
        var endLeft = destRect.left + ($target.length ? (destRect.width || 0) / 2 - 18 : 0);
        var endTop = destRect.top + ($target.length ? (destRect.height || 0) / 2 - 22 : 0);
        $clone.animate({ left: endLeft, top: endTop, width: 36, height: 44, opacity: 0.5 }, 550, 'swing', function () {
            $clone.remove();
            if ($target && $target.length) { $target.addClass('cart-bump-animate'); setTimeout(function () { $target.removeClass('cart-bump-animate'); }, 450); }
            if (typeof onComplete === 'function') onComplete();
        });
    }

    $(document).on("click", ".addcartbutton", function (e) {
        var $btn = $(this);
        var id = $btn.data("id");
        var checkout = $btn.data("checkout");
        if (id) {
            e.preventDefault();
            $.ajax({
                cache: "false", type: "GET", url: "{{ url('add-to-cart') }}/" + id + "/1", dataType: "json",
                success: function (data) {
                    if (data) {
                        toastr.success('Success', 'Product added to cart');
                        cart_count(); mobile_cart();
                        if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                        runFlyToCart($btn, function () { openSidebarCart(); });
                    }
                }
            });
        }
        if (checkout) { window.location.href = '{{ route('customer.checkout') }}'; }
    });

    $(document).on("click", ".cart_store", function (e) {
        var $btn = $(this);
        var $form = $btn.closest('form');
        if (!$form.length) return;
        var id = $btn.data("id") || $form.find("input[name=id]").val();
        if (!id) return;
        e.preventDefault();
        $form.addClass('cart-ajax-submit');
        $.ajax({
            type: "POST", data: $form.serialize(), url: $form.attr('action'),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }, dataType: "json",
            success: function (data) {
                if (data && data.success) {
                    toastr.success('Success', 'Product added to cart');
                    cart_count(); mobile_cart();
                    if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                    runFlyToCart($btn, function () { openSidebarCart(); });
                } else {
                    toastr.error(data && data.message ? data.message : 'Failed');
                }
            },
            error: function (xhr) {
                try { var d = xhr.responseJSON; if (d && !d.success) { toastr.error(d.message || 'Failed'); return; } } catch (err) {}
                $form.submit();
            },
            complete: function () { $form.removeClass('cart-ajax-submit'); }
        });
    });

    $(document).on("click", ".cart_remove", function () {
        var id = $(this).data("id");
        if (id) {
            $.ajax({
                type: "GET", data: { id: id }, url: "{{ route('cart.remove') }}",
                success: function (data) {
                    if (data) {
                        $(".cartlist").html(data);
                        cart_count(); mobile_cart(); cart_summary();
                        if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                    }
                }
            });
        }
    });
    $(document).on("click", ".cart_increment", function () {
        var id = $(this).data("id");
        if (id) {
            $.ajax({
                type: "GET", data: { id: id }, url: "{{ route('cart.increment') }}",
                success: function (data) {
                    if (data) {
                        $(".cartlist").html(data);
                        cart_count(); mobile_cart();
                        if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                    }
                }
            });
        }
    });
    $(document).on("click", ".cart_decrement", function () {
        var id = $(this).data("id");
        if (id) {
            $.ajax({
                type: "GET", data: { id: id }, url: "{{ route('cart.decrement') }}",
                success: function (data) {
                    if (data) {
                        $(".cartlist").html(data);
                        cart_count(); mobile_cart();
                        if (typeof sidebarCartRefresh === "function") sidebarCartRefresh();
                    }
                }
            });
        }
    });

    function cart_count() {
        $.ajax({
            type: "GET", url: "{{ route('cart.count') }}",
            success: function (data) { if (data) $("#cart-qty").html(data); else $("#cart-qty").empty(); }
        });
    }
    function mobile_cart() {
        $.ajax({
            type: "GET", url: "{{ route('mobile.cart.count') }}",
            success: function (data) { if (data) $(".mobilecart-qty").html(data); else $(".mobilecart-qty").empty(); }
        });
    }
    function cart_summary() {
        $.ajax({
            type: "GET", url: "{{ route('shipping.charge') }}", dataType: "html",
            success: function (response) { $(".cart-summary").html(response); }
        });
    }

    /* ---------- Live search (jQuery legacy bindings) ---------- */
    $(".search_click").on("keyup change", function () {
        var keyword = $(this).val();
        if (keyword.trim().length < 1) return;
        $.ajax({
            type: "GET", data: { keyword: keyword }, url: "{{ route('livesearch') }}",
            success: function (products) {
                if (products) $(".sf-search").find(".sf-search-drop").addClass("show").find(".sf-search-drop__body").html(products);
            }
        });
    });
    $(".msearch_click").on("keyup change", function () {
        var keyword = $(this).val();
        if (keyword.trim().length < 1) return;
        $.ajax({
            type: "GET", data: { keyword: keyword }, url: "{{ route('livesearch') }}",
            success: function (products) {
                if (products) $("#sfMSearch").find(".sf-search-drop").addClass("show").find(".sf-search-drop__body").html(products);
            }
        });
    });

    /* ---------- District → Area (checkout) ---------- */
    $(document).on("change", ".district", function () {
        var id = $(this).val();
        $.ajax({
            type: "GET", data: { id: id }, url: "{{ route('districts') }}",
            success: function (res) {
                if (res) {
                    $(".area").empty().append('<option value="">Select area…</option>');
                    $.each(res, function (key, value) { $(".area").append('<option value="' + key + '">' + value + "</option>"); });
                } else { $(".area").empty(); }
            }
        });
    });

</script>
<style>
    .fly-to-cart-img { position: fixed; z-index: 99999; pointer-events: none; }
    @keyframes cartBump { 0% { transform: scale(1); } 40% { transform: scale(1.22); } 70% { transform: scale(.96); } 100% { transform: scale(1); } }
    #loading { position: fixed; inset: 0; z-index: 1400; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,.6); }
    .custom-loader { width: 46px; height: 46px; border: 4px solid var(--c-primary-50); border-top-color: var(--c-primary); border-radius: 50%; animation: sfSpin .7s linear infinite; }
    @keyframes sfSpin { to { transform: rotate(360deg); } }
</style>

@stack('script')
</body>
</html>
