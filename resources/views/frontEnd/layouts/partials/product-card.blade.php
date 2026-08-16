{{--
    Reusable product card.
    Usage:
      @include('frontEnd.layouts.partials.product-card', ['product' => $p])
      @include('frontEnd.layouts.partials.product-card', ['product' => $p, 'showSold' => true])

    Quick actions (প্রতি কার্ডে ২টি):
      1) Order Now  → দ্রুত অর্ডার পপআপ খোলে (.qo-order-link — quick-order-modal.js হ্যান্ডেল করে)
      2) Cart Icon  → সরাসরি কার্টে যোগ করে (.addcartbutton — master.blade.php হ্যান্ডেল করে)
--}}
@php
    $__img = isset($product->image) && $product->image ? asset($product->image->image) : asset('public/logo.png');
    $__url = route('product', $product->slug);
    $__rating = isset($product->reviews) && $product->reviews->count() ? (int) round($product->reviews->avg('ratting')) : 0;
    $__reviewsCount = isset($product->reviews) ? $product->reviews->count() : 0;
    $__off = (!empty($product->old_price) && $product->old_price > $product->new_price)
        ? round((($product->old_price - $product->new_price) * 100) / $product->old_price) : 0;
    $__variantRows = (isset($product->variantPrices) && $product->variantPrices->count()) ? $product->variantPrices : collect();
    $__hasVariantStock = $__variantRows->contains(fn($v) => $v->stock !== null);
    $__stock = $__hasVariantStock ? (int) $__variantRows->sum(fn($v) => max(0, (int) $v->stock)) : (int) ($product->stock ?? 0);
    $__sold = (int) ($product->sold ?? 0);
    $__showSold = $showSold ?? false;
@endphp

<article class="sf-card">
    <div class="sf-card__media">
        @if($__off > 0)<span class="sf-off-badge">-{{ $__off }}%</span>@endif
        @if($__stock <= 0)
            <span class="sf-badge sf-badge--plain" style="position:absolute;top:10px;right:10px;z-index:2;background:var(--c-text);color:#fff">Sold Out</span>
        @elseif($__showSold && $__stock <= 20)
            <span class="sf-badge sf-badge--amber" style="position:absolute;top:10px;right:10px;z-index:2">Only {{ $__stock }} left</span>
        @endif
        <a href="{{ $__url }}" aria-label="{{ $product->name }}">
            <img src="{{ $__img }}" alt="{{ $product->name }}" loading="lazy" />
        </a>
        @if($__stock > 0)
            <button type="button" class="sf-icon-btn sf-card__view quick_view" data-id="{{ $product->id }}" title="Quick view" aria-label="Quick view">
                <i class="fa-regular fa-eye"></i>
            </button>
        @endif
        @if($__stock <= 0)
            <div class="sf-card__stockout"><span>Sold Out</span></div>
        @endif
    </div>
    <div class="sf-card__body">
        <a class="sf-card__name sf-clamp-2" href="{{ $__url }}">{{ $product->name }}</a>
        <div class="sf-card__meta">
            <span class="sf-stars">
                @for($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= $__rating ? 'on' : '' }}"></i>@endfor
            </span>
            @if($__reviewsCount)<span>({{ $__reviewsCount }})</span>@endif
            @if($__sold > 0)<span>{{ $__sold }}+ sold</span>@endif
        </div>
        <div class="sf-card__price">
            <span class="sf-price"><span class="cur">৳</span>{{ number_format((float) $product->new_price) }}</span>
            @if($__off > 0)<span class="sf-old-price">৳{{ number_format((float) $product->old_price) }}</span>@endif
        </div>
        @if($__showSold)
            @php $__pct = min(100, round($__sold * 100 / max($__sold + $__stock, 1))); @endphp
            <div class="sf-card__sold-txt"><span>Sold: {{ $__sold }}</span><span>Left: {{ max($__stock, 0) }}</span></div>
            <div class="sf-card__sold"><i style="width:{{ max($__pct, 4) }}%"></i></div>
        @endif
        <div class="sf-card__actions">
            @if($__stock > 0)
                <button type="button" class="sf-btn sf-btn--primary sf-card__order qo-order-link" data-id="{{ $product->id }}" data-url="{{ $__url }}" aria-label="Order Now">
                    <i class="fa-solid fa-bolt"></i> Order Now
                </button>
                {{-- ভ্যারিয়েন্ট (সাইজ/কালার) থাকলে কার্ট আইকনে ক্লিকে পপআপ খুলে অপশন নেবে; সাধারণ প্রোডাক্ট সরাসরি কার্টে যাবে --}}
                @if($__variantRows->count())
                    <button type="button" class="sf-icon-btn sf-card__cart qo-cart-link" data-id="{{ $product->id }}" data-url="{{ $__url }}" title="Add to cart" aria-label="Add to cart">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </button>
                @else
                    <button type="button" class="sf-icon-btn sf-card__cart addcartbutton" data-id="{{ $product->id }}" title="Add to cart" aria-label="Add to cart">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </button>
                @endif
            @else
                <button type="button" class="sf-btn sf-btn--outline sf-card__order" disabled><i class="fa-solid fa-ban"></i> Sold Out</button>
            @endif
        </div>
    </div>
</article>
