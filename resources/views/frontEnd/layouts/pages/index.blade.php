@extends('frontEnd.layouts.master')

@section('title', optional($seo)->meta_title ?? (optional($generalsetting)->name ?? 'Home'))

@push('seo')
<meta name="description" content="{{ optional($seo)->meta_description ?? '' }}" />
<meta name="keywords" content="{{ optional($seo)->meta_tags ?? '' }}" />
<meta property="og:title" content="{{ optional($seo)->meta_title ?? optional($generalsetting)->name }}" />
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ asset(optional($generalsetting)->og_baner ?? 'public/logo.png') }}" />
@endpush

@section('content')

@php
    $flashEnd = optional($generalsetting)->flash_sale_end_date;
    $dealEnd = optional($generalsetting)->hot_deal_end_date;
@endphp

<div class="sf-container">

    {{-- ================= HERO ================= --}}
    <div class="sf-hero">
        <div class="sf-hero__main" id="sfHero">
            @forelse($sliders ?? [] as $slider)
                <div class="sf-hero-slide {{ $loop->first ? 'active' : '' }}">
                    @if(!empty($slider->link))<a href="{{ $slider->link }}">@endif
                    <img src="{{ asset($slider->image) }}" alt="Banner" />
                    @if(!empty($slider->link))</a>@endif
                </div>
            @empty
                <div class="sf-hero-slide active">
                    <img src="{{ asset('public/frontEnd/images/banner.png') }}" alt="Shop Genie" />
                </div>
            @endforelse
            <button class="sf-hero__nav prev" aria-label="Previous"><i class="fa-solid fa-angle-left"></i></button>
            <button class="sf-hero__nav next" aria-label="Next"><i class="fa-solid fa-angle-right"></i></button>
            <div class="sf-hero__dots"></div>
        </div>
        <div class="sf-hero__side">
            @foreach(($campaognads ?? collect())->take(1) as $ad)
                <a href="{{ $ad->link ?? '#' }}"><img src="{{ asset($ad->image) }}" alt="Ad" /></a>
            @endforeach
            @foreach(($sliderbottomads ?? collect())->take(1) as $ad)
                <a href="{{ $ad->link ?? '#' }}"><img src="{{ asset($ad->image) }}" alt="Ad" /></a>
            @endforeach
        </div>
    </div>

    {{-- ================= FEATURES ================= --}}
    <div class="sf-features">
        <div class="sf-feature"><i class="fa-solid fa-truck-fast"></i><div><b>Fast Delivery</b><small>All over Bangladesh</small></div></div>
        <div class="sf-feature"><i class="fa-solid fa-shield-halved"></i><div><b>Secure Payment</b><small>bKash · Nagad · COD</small></div></div>
        <div class="sf-feature"><i class="fa-solid fa-rotate-left"></i><div><b>Easy Returns</b><small>7-day return policy</small></div></div>
        <div class="sf-feature"><i class="fa-solid fa-headset"></i><div><b>24/7 Support</b><small>We're here to help</small></div></div>
    </div>

    {{-- ================= CATEGORIES ================= --}}
    @if(($frontcategory ?? collect())->count())
        <div class="sf-sec-head">
            <div>
                <h2 class="sf-sec-head__ttl">Top Categories</h2>
                <p class="sf-sec-head__sub">Find everything you need, organised neatly</p>
            </div>
            <a class="sf-sec-head__link" href="{{ route('shop') }}">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="sf-catgrid">
            @foreach($frontcategory as $cat)
                <a class="sf-cat-tile" href="{{ route('category', $cat->slug) }}">
                    <span class="ico">
                        @if(!empty($cat->image))<img src="{{ asset($cat->image) }}" alt="{{ $cat->name }}" loading="lazy" />
                        @else<i class="fa-solid fa-tag"></i>@endif
                    </span>
                    <b>{{ $cat->name }}</b>
                    <small>{{ optional($cat->products)->count() ?? 0 }}+ items</small>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ================= FLASH SALE ================= --}}
    @if(($flas_sales ?? collect())->count())
        <div class="sf-sec-head">
            <div>
                <h2 class="sf-sec-head__ttl">Flash Sale</h2>
                <p class="sf-sec-head__sub">Deals that vanish fast — grab them now</p>
            </div>
            <a class="sf-sec-head__link" href="{{ route('flashsales') }}">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="sf-flash">
            <div class="sf-flash__head">
                <div class="sf-flash__ttl"><i class="fa-solid fa-bolt"></i> Limited Time Offers</div>
                @if($flashEnd)
                    <div class="sf-flash__timer" data-end="{{ strtotime($flashEnd) * 1000 }}">
                        <span class="box"><b data-t-d>00</b><small>Days</small></span><span class="sep">:</span>
                        <span class="box"><b data-t-h>00</b><small>Hours</small></span><span class="sep">:</span>
                        <span class="box"><b data-t-m>00</b><small>Mins</small></span><span class="sep">:</span>
                        <span class="box"><b data-t-s>00</b><small>Secs</small></span>
                    </div>
                @endif
                <a class="sf-flash__link" href="{{ route('flashsales') }}">See All Deals <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="sf-flash__body">
                @foreach($flas_sales->take(4) as $product)
                    @include('frontEnd.layouts.partials.product-card', ['product' => $product, 'showSold' => true])
                @endforeach
            </div>
        </div>
    @endif

    {{-- ================= AD STRIP ================= --}}
    @if(($homepageads2 ?? collect())->count() || ($homepageads ?? collect())->count())
        <div class="sf-adstrip two" style="grid-template-columns:repeat(2,1fr)">
            @foreach(($homepageads2 ?? collect())->take(1) as $ad)
                <a href="{{ $ad->link ?? '#' }}"><img src="{{ asset($ad->image) }}" alt="Ad" /></a>
            @endforeach
            @foreach(($homepageads ?? collect())->take(1) as $ad)
                <a href="{{ $ad->link ?? '#' }}"><img src="{{ asset($ad->image) }}" alt="Ad" /></a>
            @endforeach
        </div>
    @endif

    {{-- ================= HOT DEALS ================= --}}
    @if(($hotdeal_top ?? collect())->count())
        <div class="sf-sec-head">
            <div>
                <h2 class="sf-sec-head__ttl"><i class="fa-solid fa-fire" style="color:var(--c-accent)"></i> Hot Deals</h2>
                <p class="sf-sec-head__sub">Most wanted products at the best prices</p>
            </div>
            <a class="sf-sec-head__link" href="{{ route('hotdeals') }}">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        @if(($hitdealsbaner ?? collect())->count())
            @foreach($hitdealsbaner->take(1) as $banner)
                <a class="sf-catsec__banner" href="{{ $banner->link ?? '#' }}"><img src="{{ asset($banner->image) }}" alt="Hot Deals" /></a>
            @endforeach
        @endif
        <div class="sf-owl-nav" style="margin-top:16px">
            <div class="owl-carousel product_slider">
                @foreach($hotdeal_top->take(12) as $product)
                    <div style="padding:4px">
                        @include('frontEnd.layouts.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ================= CATEGORY WISE PRODUCTS ================= --}}
    @foreach($homeproducts ?? [] as $homesection)
        @if(($homesection->products ?? collect())->count())
            <div class="sf-sec-head">
                <div>
                    <h2 class="sf-sec-head__ttl">{{ $homesection->name }}</h2>
                    <p class="sf-sec-head__sub">Top picks in {{ $homesection->name }}</p>
                </div>
                <a class="sf-sec-head__link" href="{{ route('category', $homesection->slug) }}">View All <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="sf-owl-nav">
                <div class="owl-carousel product_slider">
                    @foreach($homesection->products->take(12) as $product)
                        <div style="padding:4px">
                            @include('frontEnd.layouts.partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    {{-- ================= ALL PRODUCTS ================= --}}
    @if(($all_products ?? collect())->count())
        <div class="sf-sec-head">
            <div>
                <h2 class="sf-sec-head__ttl">Just For You</h2>
                <p class="sf-sec-head__sub">Handpicked products you might love</p>
            </div>
            <a class="sf-sec-head__link" href="{{ route('shop') }}">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="sf-pgrid">
            @foreach($all_products->take(8) as $product)
                @include('frontEnd.layouts.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif

    {{-- ================= BRANDS ================= --}}
    @if(($brands ?? collect())->count())
        <div class="sf-sec-head">
            <div>
                <h2 class="sf-sec-head__ttl">Top Brands</h2>
                <p class="sf-sec-head__sub">Shop from brands you trust</p>
            </div>
        </div>
        <div class="sf-brands">
            @foreach($brands->take(6) as $brand)
                <a class="sf-brand" href="{{ route('brand.products', $brand->slug) }}">
                    <img src="{{ asset($brand->image) }}" alt="{{ $brand->name }}" loading="lazy" />
                </a>
            @endforeach
        </div>
    @endif

    {{-- ================= VENDOR SHOPS ================= --}}
    @if(($vendors ?? collect())->count() && ($generalsetting?->vendor_enabled ?? 1) == 1)
        <div class="sf-sec-head">
            <div>
                <h2 class="sf-sec-head__ttl">Featured Shops</h2>
                <p class="sf-sec-head__sub">Verified sellers with great ratings</p>
            </div>
            <a class="sf-sec-head__link" href="{{ route('sellers') }}">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="sf-shops">
            @foreach($vendors->take(3) as $vendor)
                <a class="sf-shop" href="{{ route('vendor.shop', $vendor->slug) }}">
                    <img class="logo" src="{{ asset($vendor->logo ?? 'public/logo.png') }}" alt="{{ $vendor->shop_name }}" />
                    <div>
                        <b>{{ $vendor->shop_name }}</b>
                        <small>{{ $vendor->products_count }}+ products</small>
                        <div class="sf-stars" style="margin-top:3px">
                            @for($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= round($vendor->average_rating ?? 0) ? 'on' : '' }}"></i>@endfor
                            <span class="sf-faint" style="margin-left:5px">{{ $vendor->average_rating ?? 0 }} ({{ $vendor->total_reviews ?? 0 }})</span>
                        </div>
                    </div>
                    <span class="sf-btn sf-btn--outline sf-btn--sm">Visit Shop</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ================= BLOGS ================= --}}
    @if(($blogs ?? collect())->count())
        <div class="sf-sec-head">
            <div>
                <h2 class="sf-sec-head__ttl">From Our Blog</h2>
                <p class="sf-sec-head__sub">Tips, guides and shopping ideas</p>
            </div>
            <a class="sf-sec-head__link" href="{{ route('blogs') }}">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="sf-blogs">
            @foreach($blogs->take(3) as $blog)
                <article class="sf-blog">
                    <a href="{{ route('blog.details', $blog->slug) }}"><img src="{{ asset($blog->image) }}" alt="{{ $blog->title }}" loading="lazy" /></a>
                    <div class="sf-blog__body">
                        <h4><a href="{{ route('blog.details', $blog->slug) }}">{{ $blog->title }}</a></h4>
                        <p class="sf-clamp-2">{{ Str::limit(strip_tags($blog->description ?? ''), 110) }}</p>
                        <div class="sf-blog__meta">
                            <span><i class="fa-regular fa-calendar" style="margin-right:5px"></i>{{ optional($blog->created_at)->format('M d, Y') }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ================= TRUST STRIP ================= --}}
    <div class="sf-trust">
        <div class="sf-feature"><i class="fa-solid fa-medal"></i><div><b>Genuine Products</b><small>100% authentic items</small></div></div>
        <div class="sf-feature"><i class="fa-solid fa-hand-holding-dollar"></i><div><b>Best Prices</b><small>Unbeatable value daily</small></div></div>
        <div class="sf-feature"><i class="fa-solid fa-credit-card"></i><div><b>Easy Payments</b><small>bKash, Nagad, COD</small></div></div>
        <div class="sf-feature"><i class="fa-solid fa-headset"></i><div><b>Human Support</b><small>Real people, real help</small></div></div>
    </div>

</div>
@endsection
