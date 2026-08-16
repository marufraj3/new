@extends('frontEnd.layouts.master')

@section('title', 'Hot Deals')

@section('content')
<div class="sf-page">
    <div class="sf-container">

        <div class="sf-page-head" style="border-radius:var(--r-lg);margin-top:18px;position:relative;overflow:hidden">
            <div class="sf-container" style="position:relative;z-index:2">
                <h1><i class="fa-solid fa-fire" style="color:#ffb02e;margin-right:10px"></i>Hot Deals</h1>
                <p style="color:#c3cdea;font-size:14px;margin-top:6px">The most wanted products at unbeatable prices — grab them before they're gone.</p>
            </div>
        </div>

        @if($products->count())
            <div class="sf-pgrid sf-pgrid--5" style="margin-top:22px">
                @foreach($products as $product)
                    @include('frontEnd.layouts.partials.product-card', ['product' => $product, 'showSold' => true])
                @endforeach
            </div>
            {{ $products->onEachSide(1)->links('pagination::bootstrap-5') }}
        @else
            <div class="sf-empty sf-card-surface">
                <i class="fa-solid fa-fire"></i>
                <h4>No hot deals right now</h4>
                <p>Check back soon — new deals drop regularly.</p>
                <a class="sf-btn sf-btn--dark" style="margin-top:16px" href="{{ route('shop') }}">Browse Products</a>
            </div>
        @endif
    </div>
</div>
@endsection
