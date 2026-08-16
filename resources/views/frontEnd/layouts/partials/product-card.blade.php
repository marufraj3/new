{{--
    Reusable product card.
    Usage:
      @include('frontEnd.layouts.partials.product-card', ['product' => $p])
      @include('frontEnd.layouts.partials.product-card', ['product' => $p, 'showSold' => true])
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
@endphp

<article class="sf-card">
    <div class="sf-card__media">
        @if($__off > 0)<span class="sf-off-badge">-{{ $__off }}%</span>@endif
        @if($__stock <= 0)
            <span class="sf-badge sf-badge--plain" style="position:absolute;top:10px;right:10px;z-index:2;background:var(--c-text);color:#fff">Sold Out</span>
        @elseif(($showSold ?? false) && $__stock <= 20)
            <span class="sf-badge sf-badge--amber" style="position:absolute;top:10px;right:10px;z-index:2">Only {{ $__stock }} left</span>
        @endif
        <a href="{{ $__url }}" aria-label="{{ $product->name }}">
            <img src="{{ $__img }}" alt="{{ $product->name }}" loading="lazy" />
        </a>
        <div class="sf-card__hover">
            <button type="button" class="sf-icon-btn quick_view" data-id="{{ $product->id }}" title="Quick view" aria-label="Quick view">
                <i class="fa-regular fa-eye"></i>
            </button>
            @if($__stock > 0)
                <button type="button" class="sf-btn sf-btn--dark addcartbutton" data-id="{{ $product->id }}" aria-label="Add to cart">
                    <i class="fa-solid fa-cart-plus"></i> Cart
                </button>
            @endif
        </div>
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
        @if($showSold ?? false)
            @php $__pct = min(100, round($__sold * 100 / max($__sold + $__stock, 1))); @endphp
            <div class="sf-card__sold-txt"><span>Sold: {{ $__sold }}</span><span>Left: {{ max($__stock, 0) }}</span></div>
            <div class="sf-card__sold"><i style="width:{{ max($__pct, 4) }}%"></i></div>
        @endif
    </div>
</article>
