@extends('frontEnd.layouts.master')

@section('title', 'Search: ' . ($keyword ?? ''))

@section('content')
<div class="sf-page">
    <div class="sf-container">

        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            <span class="cur">Search Results</span>
        </nav>

        <div class="sf-shop__toolbar">
            <h3>Results for “{{ $keyword }}” <small>{{ $products->total() }} product(s) found</small></h3>
            <form class="sf-search" action="{{ route('search') }}" method="GET" style="max-width:340px">
                <div class="sf-search__box" style="border-width:1.5px">
                    <input type="text" name="keyword" value="{{ $keyword }}" class="search_keyword" placeholder="Search again…" />
                    <button type="submit" style="padding:0 16px"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
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
                <h4>No results for “{{ $keyword }}”</h4>
                <p>Check the spelling or try a different keyword.</p>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px">
                    <a class="sf-btn sf-btn--dark" href="{{ route('shop') }}">Browse All Products</a>
                    <a class="sf-btn sf-btn--outline" href="{{ route('contact') }}">Contact Support</a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
