@extends('frontEnd.layouts.master')

@section('title', $category->name . ' — Shop')

@section('content')
<div class="sf-page">
    <div class="sf-container">

        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            <a href="{{ route('shop') }}">Shop</a><i class="fa-solid fa-angle-right"></i>
            <span class="cur">{{ $category->name }}</span>
        </nav>

        <form class="sf-shop" method="GET" id="sfFilterForm">

            {{-- ============ SIDEBAR FILTERS ============ --}}
            <aside class="sf-sidebar" id="sfFilterSide">
                <div class="sf-filter-card">
                    <h5>Subcategories <button type="button" class="cls" id="sfFilterClose" style="display:none"><i class="fa-solid fa-xmark"></i></button></h5>
                    <div class="sf-filter-cats">
                        <a href="{{ route('category', $category->slug) }}" class="{{ !request('subcategory') ? 'active' : '' }}">
                            All {{ $category->name }} <small>{{ $category->products->count() ?? '' }}</small>
                        </a>
                        @foreach($subcategories as $sub)
                            <a href="{{ route('category', $category->slug) }}?subcategory[]={{ $sub->id }}">
                                {{ $sub->subcategoryName }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @if($min_price !== null && $max_price !== null && $max_price > $min_price)
                    <div class="sf-filter-card sf-filter-price">
                        <h5>Price Range</h5>
                        <div class="vals"><span id="sfRangeMinVal">৳{{ number_format((float) $min_price) }}</span><span id="sfRangeMaxVal">৳{{ number_format((float) $max_price) }}</span></div>
                        <div class="sf-range">
                            <div class="bar" id="sfRangeBar"></div>
                            <input type="range" id="sfRangeMin" name="min_price" min="{{ (int) $min_price }}" max="{{ (int) $max_price }}" value="{{ request('min_price', (int) $min_price) }}" step="1" />
                            <input type="range" id="sfRangeMax" name="max_price" min="{{ (int) $min_price }}" max="{{ (int) $max_price }}" value="{{ request('max_price', (int) $max_price) }}" step="1" />
                        </div>
                        <button type="submit" class="sf-btn sf-btn--dark sf-btn--block sf-btn--sm" style="margin-top:14px">Apply Price</button>
                    </div>
                @endif

                <div class="sf-filter-card" style="display:none">
                    <label class="sf-check">
                        <input type="checkbox" name="sold" value="show" @if(request('sold') == 'show') checked @endif onchange="this.form.submit()" />
                        Only show products with sales
                    </label>
                </div>

                <div class="sf-filter-card" style="background:linear-gradient(135deg,var(--c-primary),#32457e);border:0;color:#fff">
                    <h5 style="color:#fff"><i class="fa-solid fa-headset"></i> Need Help?</h5>
                    <p style="font-size:12.5px;color:#c3cdea;line-height:1.6">Can't find what you're looking for? Our team is ready to help you choose.</p>
                    @if(!empty(optional($contact)->hotline))
                        <a class="sf-btn sf-btn--primary sf-btn--block sf-btn--sm" style="margin-top:12px" href="tel:{{ $contact->hotline }}">
                            <i class="fa-solid fa-phone"></i> {{ $contact->hotline }}
                        </a>
                    @endif
                </div>
            </aside>
            <div class="sf-sidebar-ovl" id="sfFilterOvl"></div>

            {{-- ============ MAIN ============ --}}
            <div class="sf-shop__main">
                <div class="sf-shop__toolbar">
                    <h3>{{ $category->name }} <small>{{ $products->total() }} product(s) found</small></h3>
                    <button type="button" class="sf-shop__filterbtn" id="sfFilterBtn"><i class="fa-solid fa-sliders"></i> Filter</button>
                    <label class="sf-shop__sort">Sort by
                        <select class="sf-select" name="sort" onchange="this.form.submit()">
                            <option value="">Newest</option>
                            <option value="1" @if(request('sort') == 1) selected @endif>Newest First</option>
                            <option value="2" @if(request('sort') == 2) selected @endif>Oldest First</option>
                            <option value="3" @if(request('sort') == 3) selected @endif>Price: High → Low</option>
                            <option value="4" @if(request('sort') == 4) selected @endif>Price: Low → High</option>
                            <option value="5" @if(request('sort') == 5) selected @endif>Name: A → Z</option>
                            <option value="6" @if(request('sort') == 6) selected @endif>Name: Z → A</option>
                        </select>
                    </label>
                </div>

                @if($products->count())
                    <div class="sf-pgrid sf-pgrid--5">
                        @foreach($products as $product)
                            @include('frontEnd.layouts.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                    {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
                @else
                    <div class="sf-empty sf-card-surface">
                        <i class="fa-regular fa-face-frown"></i>
                        <h4>No products found</h4>
                        <p>Try adjusting your filters or browse other categories.</p>
                        <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}">Browse All Products</a>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

@push('script')
<script>
    // Open filter close button on mobile only
    $("#sfFilterClose").css("display", window.innerWidth < 992 ? "inline-flex" : "none");
    $("#sfFilterClose").on("click", function () { $("#sfFilterSide").removeClass("show"); $("#sfFilterOvl").removeClass("show"); });
</script>
@endpush
