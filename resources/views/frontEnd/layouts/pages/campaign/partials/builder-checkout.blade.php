@php
    $builderSubtotal = (float) str_replace([',', '.00'], '', Cart::instance('shopping')->subtotal());
    $builderShipping = (float) Session::get('shipping', 0);
@endphp
<div class="cpb-live-checkout">
    @if($products->count() > 1)
        <div class="cpb-checkout-products">
            <div class="cpb-checkout-eyebrow">আপনার পছন্দের পণ্যটি বেছে নিন</div>
            <div class="cpb-compact-products">
                @foreach($products as $product)
                    <button type="button" class="cpb-compact-product {{ $loop->first ? 'is-selected' : '' }}" data-select-product="{{ $product->id }}">
                        <img src="{{ asset(optional($product->image)->image ?? 'public/uploads/default.webp') }}" alt="">
                        <span><strong>{{ Str::limit($product->name, 35) }}</strong><small>৳{{ number_format((float) $product->new_price, 0) }}</small></span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <div id="cpb-variant-picker" class="cpb-variant-picker" hidden>
        <div class="cpb-variant-field" data-size-field hidden>
            <label for="cpb-product-size">সাইজ বেছে নিন</label>
            <select id="cpb-product-size" data-product-size></select>
        </div>
        <div class="cpb-variant-field" data-color-field hidden>
            <label for="cpb-product-color">কালার বেছে নিন</label>
            <select id="cpb-product-color" data-product-color></select>
        </div>
    </div>

    <div class="cpb-checkout-columns">
        <section class="cpb-order-summary" aria-labelledby="cpb-summary-heading">
            <div class="cpb-checkout-card-head">
                <span>১</span>
                <div><strong id="cpb-summary-heading">আপনার অর্ডার</strong><small>পরিমাণ ও মোট মূল্য যাচাই করুন</small></div>
            </div>
            <div class="cartlist cpb-cartlist">
                <table class="cart_table table table-bordered text-center mb-0">
                    <thead><tr><th>প্রোডাক্ট</th><th style="width:120px">পরিমাণ</th><th style="width:95px">মূল্য</th></tr></thead>
                    <tbody>
                    @foreach(Cart::instance('shopping')->content() as $item)
                        <tr>
                            <td class="text-start"><img src="{{ asset($item->options->image ?? 'public/uploads/default.webp') }}" width="42" height="42" alt=""> <strong>{{ Str::limit($item->name, 24) }}</strong></td>
                            <td><div class="quantity"><button type="button" class="cart_decrement" data-id="{{ $item->rowId }}">−</button><input type="text" value="{{ $item->qty }}" readonly><button type="button" class="cart_increment" data-id="{{ $item->rowId }}">+</button></div></td>
                            <td>৳{{ number_format($item->price * $item->qty, 0) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="2" class="text-end">মোট</th><td id="net_total"><strong>{{ number_format($builderSubtotal, 0) }}</strong></td></tr>
                        <tr><th colspan="2" class="text-end">ডেলিভারি চার্জ</th><td id="cart_shipping_cost"><strong>{{ number_format($builderShipping, 0) }}</strong></td></tr>
                        <tr><th colspan="2" class="text-end">সর্বমোট</th><td id="grand_total"><strong>{{ number_format($builderSubtotal + $builderShipping, 0) }}</strong></td></tr>
                    </tfoot>
                </table>
            </div>
            <div class="cpb-checkout-assurance"><span>🔒 নিরাপদ অর্ডার</span><span>✓ ক্যাশ অন ডেলিভারি</span></div>
        </section>

        <section class="cpb-customer-form" aria-labelledby="cpb-form-heading">
            <div class="cpb-checkout-card-head">
                <span>২</span>
                <div><strong id="cpb-form-heading">ডেলিভারি তথ্য</strong><small>অর্ডার নিশ্চিত করতে সঠিক তথ্য দিন</small></div>
            </div>
            @if($errors->any())
                <div class="cpb-form-errors" role="alert">
                    <strong>তথ্যগুলো আবার যাচাই করুন:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form action="{{ route('customer.ordersave') }}" method="POST" data-cpb-order-form>
                @csrf
                <input type="hidden" name="payment_method" value="cod">
                <div class="cpb-form-field">
                    <label for="cpb-name">আপনার নাম <span>*</span></label>
                    <input id="cpb-name" type="text" name="name" value="{{ old('name') }}" placeholder="আপনার সম্পূর্ণ নাম" autocomplete="name" required>
                </div>
                <div class="cpb-form-field">
                    <label for="cpb-phone">মোবাইল নম্বর <span>*</span></label>
                    <input id="cpb-phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" inputmode="numeric" pattern="0[0-9]{10}" maxlength="11" autocomplete="tel" required>
                </div>
                <div class="cpb-form-field">
                    <label for="cpb-address">সম্পূর্ণ ঠিকানা <span>*</span></label>
                    <textarea id="cpb-address" name="address" placeholder="জেলা, থানা, এলাকা/গ্রাম ও বাড়ির ঠিকানা" autocomplete="street-address" required>{{ old('address') }}</textarea>
                </div>
                <div class="cpb-form-field">
                    <label for="cpb-area">ডেলিভারি এরিয়া <span>*</span></label>
                    <select id="cpb-area" name="area" required>
                        @foreach($shippingcharge as $charge)
                            <option value="{{ $charge->id }}" {{ (string) old('area') === (string) $charge->id ? 'selected' : '' }}>{{ $charge->name }} — ৳{{ number_format((float) $charge->amount, 0) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cpb-form-field">
                    <label for="cpb-note">অর্ডার নোট <small>(ঐচ্ছিক)</small></label>
                    <textarea id="cpb-note" name="order_note" rows="2" placeholder="বিশেষ কোনো নির্দেশনা থাকলে লিখুন">{{ old('order_note') }}</textarea>
                </div>
                <button class="cpb-place-order" type="submit"><span>অর্ডার কনফার্ম করুন</span><strong>→</strong></button>
                <p class="cpb-form-privacy">আপনার তথ্য শুধু অর্ডার প্রসেস করার জন্য ব্যবহার করা হবে।</p>
            </form>
        </section>
    </div>
</div>
