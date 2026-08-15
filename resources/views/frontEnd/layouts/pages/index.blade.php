{{--
=====================================================================
  FILE: resources/views/frontEnd/layouts/pages/index.blade.php
  নতুন হোমপেজ ডিজাইন (ecommerce4.creativedesign.com.bd রেফারেন্স)
  --------------------------------------------------------------
  ✅ HTML + CSS + JS — সব এক ফাইলেই
  ✅ কোনো নতুন প্যাকেজ / CDN লাগবে না (slider, countdown, quick-view
     সব vanilla JS দিয়ে লেখা)
  ✅ আপনার FrontendController@index এর ভ্যারিয়েবল হুবহু ব্যবহার করা হয়েছে:
     $seo, $generalsetting, $menucategories, $sliders, $frontcategory,
     $sliderbottomads, $footertopads, $homepageads, $homepageads2,
     $hitdealsbaner, $campaognads, $flas_sales, $hotdeal_top,
     $hotdeal_bottom, $homeproducts, $brands, $blogs, $vendors,
     $reviews, $all_products
  ✅ রুট: category, subcategory, products, product, cart.store,
     flashsales, hotdeals, sellers, brand.products, vendor.shop
  --------------------------------------------------------------
  ব্যবহার: পুরনো index.blade.php এর জায়গায় এই ফাইলটা রিপ্লেস করুন।
  (আগে পুরনোটার একটা ব্যাকআপ রাখুন: index.blade.php.bak)
=====================================================================
--}}

@extends('frontEnd.layouts.master')

@section('title', $seo->meta_title ?? ($generalsetting->name ?? 'Home'))

@push('seo')
<meta name="app-url" content="{{ url('/') }}" />
<meta name="robots" content="index, follow" />
<meta name="description" content="{{ $seo->meta_description ?? '' }}" />
<meta name="keywords" content="{{ $seo->meta_tags ?? '' }}" />
<meta property="og:title" content="{{ $seo->meta_title ?? '' }}" />
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ asset($generalsetting->og_baner ?? 'public/logo.png') }}" />
<meta property="og:description" content="{{ $seo->meta_description ?? '' }}" />
@endpush

@section('content')

@php
    /* ---------- থিম কালার (এডমিন প্যানেল থেকে) ---------- */
    $cdPrimary   = $generalsetting->primary_color   ?? '#303d6e';
    $cdSecondary = $generalsetting->secodery_color  ?? '#ff0000';

    /* ---------- কাউন্টডাউন তারিখ ---------- */
    $flashEnd = $generalsetting->flash_sale_end_date ?? null;
    $dealEnd  = $generalsetting->hot_deal_end_date   ?? null;

    /* ---------- প্রোডাক্ট কার্ড রেন্ডার হেল্পার ডাটা ---------- */
    $currency = '৳';

    /* =========================================================
       প্রোডাক্ট কার্ড রেন্ডারার  (এক ফাইলেই রাখার জন্য ক্লোজার)
       ব্যবহার:  {!! $cdCard($product, true) !!}   // true = Sold/Left বার
       ---------------------------------------------------------
       ✅ "অর্ডার করুন" বাটনে ক্লিক করলে কোনো পেজ লোড হবে না —
          সাথে সাথে Quick Order পপআপ খুলবে (সাইজ/কালার/কোয়ান্টিটি)
          → কনফার্ম করলে সরাসরি Checkout পেজে চলে যাবে।
       ========================================================= */
    $cdProducts = [];   // পপআপের জন্য প্রোডাক্ট ডাটা (JSON আকারে নিচে যাবে)

    $cdCard = function ($p, $showSold = false) use ($currency, &$cdProducts) {

        $img  = isset($p->image) && $p->image ? asset($p->image->image) : asset('public/logo.png');
        $url  = route('product', $p->slug);
        $name = e(\Illuminate\Support\Str::limit($p->name, 42));

        /* --- ডিসকাউন্ট ব্যাজ --- */
        $badge = '';
        if (!empty($p->old_price) && $p->old_price > $p->new_price) {
            $off   = round((($p->old_price - $p->new_price) * 100) / $p->old_price);
            $badge = '<span class="cd-badge">' . $off . '% OFF</span>';
        }

        /* --- দাম --- */
        $price = '<div class="cd-price"><b>' . $currency . ' ' . number_format($p->new_price) . '</b>';
        if (!empty($p->old_price) && $p->old_price != $p->new_price) {
            $price .= '<del>' . $currency . ' ' . number_format($p->old_price) . '</del>';
        }
        $price .= '</div>';

        /* --- রেটিং --- */
        $avg   = (isset($p->reviews) && $p->reviews->count()) ? $p->reviews->avg('ratting') : 0;
        $stars = '<div class="cd-stars">';
        for ($i = 1; $i <= 5; $i++) { $stars .= '<span class="' . ($i <= round($avg) ? '' : 'off') . '">★</span>'; }
        $stars .= '</div>';

        /* Variant stock is the source of truth. Product stock displays the sum of
           every size/color variant so a product is not incorrectly marked out. */
        $variantRows = (isset($p->variantPrices) && $p->variantPrices->count()) ? $p->variantPrices : collect();
        $hasVariantStock = $variantRows->contains(fn($v) => $v->stock !== null);
        $displayStock = $hasVariantStock
            ? (int) $variantRows->sum(fn($v) => max(0, (int) $v->stock))
            : (int) ($p->stock ?? 0);

        /* --- Sold / Left প্রগ্রেস (urgency) --- */
        $soldHtml = '';
        if ($showSold) {
            $sold  = (int) ($p->sold ?? 0);
            $stock = $displayStock;
            $tot   = max($sold + $stock, 1);
            $pct   = min(100, round($sold * 100 / $tot));
            $soldHtml = '<div class="cd-sold"><span>Sold ' . $sold . '</span><span>Left ' . $stock . '</span></div>'
                      . '<div class="cd-bar"><i style="width:' . max($pct, 4) . '%"></i></div>';
        }

        /* --- স্টক আউট --- */
        $isOut = $displayStock <= 0;
        $out   = $isOut ? '<div class="cd-stock-out">স্টক শেষ</div>' : '';

        /* ---------------------------------------------------------
           সাইজ / কালার ডাটা
           ✅ প্রধান সোর্স : variantPrices (product_variant_prices)
              — এখানে প্রতি ভ্যারিয়েন্টের আলাদা দাম ও স্টক আছে
           ✅ ফলব্যাক     : productsizes / productcolors (পুরনো প্রোডাক্ট)
           --------------------------------------------------------- */
        $sizes = []; $colors = []; $variants = [];

        if (isset($p->variantPrices) && $p->variantPrices->count()) {
            foreach ($p->variantPrices as $v) {
                $variants[] = [
                    's'  => $v->size_id  ? (int) $v->size_id  : null,
                    'c'  => $v->color_id ? (int) $v->color_id : null,
                    'p'  => (float) $v->price,
                    'st' => $v->stock === null ? null : (int) $v->stock,
                ];
                if ($v->size_id && $v->size) {
                    if (!isset($sizes[$v->size_id])) {
                        $sizes[$v->size_id] = ['id' => (int) $v->size_id, 'name' => $v->size->sizeName, 'stock' => 0, 'has_stock' => false];
                    }
                    if ($v->stock !== null) {
                        $sizes[$v->size_id]['stock'] += max(0, (int) $v->stock);
                        $sizes[$v->size_id]['has_stock'] = true;
                    }
                }
                if ($v->color_id && $v->color) {
                    $colors[$v->color_id] = ['id' => (int) $v->color_id, 'name' => $v->color->colorName, 'hex' => $v->color->color];
                }
            }
        }

        /* ফলব্যাক — পুরনো productsizes / productcolors টেবিল */
        if (empty($sizes) && isset($p->prosizes)) {
            foreach ($p->prosizes as $ps) {
                $sz = $ps->size ?? null;
                if ($sz) { $sizes[$sz->id] = ['id' => (int) $sz->id, 'name' => $sz->sizeName]; }
            }
        }
        if (empty($colors) && isset($p->procolors)) {
            foreach ($p->procolors as $pc) {
                $cl = $pc->color ?? null;
                if ($cl) { $colors[$cl->id] = ['id' => (int) $cl->id, 'name' => $cl->colorName, 'hex' => $cl->color]; }
            }
        }

        $sizes  = array_values($sizes);
        $colors = array_values($colors);

        /* --- পপআপ ডাটা (একই প্রোডাক্ট বারবার এলে একবারই যাবে) --- */
        $cdProducts[$p->id] = [
            'id'     => $p->id,
            'name'   => $p->name,
            'img'    => $img,
            'url'    => $url,
            'price'  => (float) $p->new_price,
            'old'    => (float) ($p->old_price ?? 0),
            'stock'  => $displayStock,
            'sizes'    => $sizes,
            'colors'   => $colors,
            'variants' => $variants,
        ];

        /* --- বাটন : সব প্রোডাক্টেই এখন পপআপ খুলবে --- */
        if ($isOut) {
            $btns = '<div class="cd-btns"><button type="button" class="cd-order cd-order-off" disabled>স্টক শেষ</button></div>';
        } else {
            $btns = '<div class="cd-btns">'
                  . '<button type="button" class="cd-order" onclick="cdOrder(' . $p->id . ')">অর্ডার করুন</button>'
                  . '<button type="button" class="cd-cart" onclick="cdOrder(' . $p->id . ',1)" title="কার্টে রাখুন">🛒</button>'
                  . '</div>';
        }

        return '<div class="cd-card">'
             . '<div class="cd-card-img">' . $badge . $out
             . '<a href="' . $url . '"><img src="' . $img . '" alt="' . $name . '" loading="lazy"></a>'
             . '<button type="button" class="cd-quick" onclick="cdOrder(' . $p->id . ')" title="দ্রুত অর্ডার">👁</button>'
             . '</div>'
             . '<div class="cd-card-body">'
             . '<a class="cd-card-name" href="' . $url . '">' . $name . '</a>'
             . $stars . $price . $soldHtml . $btns
             . '</div></div>';
    };
@endphp

{{-- ============================================================
     PART 1 : সমস্ত CSS (এক জায়গায়)
     ============================================================ --}}
<style>
:root{
    --cd-primary   : {{ $cdPrimary }};
    --cd-secondary : {{ $cdSecondary }};
    --cd-dark      : #131a22;
    --cd-text      : #1f2937;
    --cd-muted     : #6b7280;
    --cd-line      : #e9edf2;
    --cd-bg        : #ffffff;
    --cd-nav       : #111111;
    --cd-radius    : 12px;
    --cd-shadow    : 0 2px 10px rgba(16,24,40,.06);
    --cd-shadow-lg : 0 10px 30px rgba(16,24,40,.12);
}

/* ---------- Base ---------- */
.cd-home *{box-sizing:border-box;}
.cd-home{background:var(--cd-bg);color:var(--cd-text);font-family:'Hind Siliguri','Segoe UI',system-ui,-apple-system,sans-serif;overflow-x:hidden;}
.cd-wrap{max-width:1240px;margin:0 auto;padding:0 12px;}
.cd-home a{text-decoration:none;color:inherit;}
.cd-home img{max-width:100%;display:block;}
.cd-sec{padding:22px 0;}

/* ---------- HERO : স্লাইডার + ডানে ২টা ব্যানার ---------- */
.cd-hero{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,420px);gap:14px;padding:14px 0;}
.cd-hero-side{display:grid;grid-template-rows:1fr 1fr;gap:14px;}
.cd-hero-side a{border-radius:10px;overflow:hidden;box-shadow:var(--cd-shadow);display:block;}
.cd-hero-side img{width:100%;height:100%;object-fit:cover;transition:transform .5s;}
.cd-hero-side a:hover img{transform:scale(1.04);}
/* slider */
.cd-slider{position:relative;border-radius:10px;overflow:hidden;box-shadow:var(--cd-shadow);background:#fff;}
.cd-slides{display:flex;transition:transform .6s cubic-bezier(.4,0,.2,1);}
.cd-slides > a,.cd-slides > div{min-width:100%;}
.cd-slides img{width:100%;aspect-ratio:16/10.2;object-fit:cover;}
.cd-sl-btn{position:absolute;top:50%;transform:translateY(-50%);width:38px;height:38px;border:0;border-radius:50%;background:rgba(255,255,255,.85);color:var(--cd-primary);font-size:18px;cursor:pointer;display:grid;place-items:center;box-shadow:var(--cd-shadow);opacity:0;transition:.25s;}
.cd-slider:hover .cd-sl-btn{opacity:1;}
.cd-sl-prev{left:12px}.cd-sl-next{right:12px}
.cd-dots{position:absolute;bottom:10px;left:50%;transform:translateX(-50%);display:flex;gap:6px;}
.cd-dots span{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.6);cursor:pointer;transition:.25s;}
.cd-dots span.on{background:var(--cd-secondary);width:22px;border-radius:9px;}

/* ---------- ADS ROWS ---------- */
.cd-ads{display:grid;gap:12px;}
.cd-ads-2,.cd-ads-3,.cd-ads-4{grid-template-columns:repeat(auto-fit,minmax(230px,1fr));}
.cd-ads a{border-radius:var(--cd-radius);overflow:hidden;box-shadow:var(--cd-shadow);display:block;}
.cd-ads img{width:100%;height:100%;object-fit:cover;transition:transform .5s;}
.cd-ads a:hover img{transform:scale(1.05);}
.cd-ad-full img{width:100%;border-radius:var(--cd-radius);box-shadow:var(--cd-shadow);}

/* ---------- SECTION HEAD ---------- */
.cd-head{display:flex;align-items:center;justify-content:center;gap:18px;margin-bottom:18px;position:relative;}
.cd-head h2{margin:0;font-size:21px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:#111;white-space:nowrap;}
.cd-head:before,.cd-head:after{content:'';height:1px;background:#111;flex:0 1 170px;opacity:.85;}
.cd-head .cd-viewall{position:absolute;right:0;top:50%;transform:translateY(-50%);}
.cd-viewall{font-size:13px;font-weight:600;color:var(--cd-primary);border:1px solid var(--cd-primary);padding:6px 16px;border-radius:3px;transition:.25s;}
.cd-viewall:hover{background:var(--cd-primary);color:#fff;}

/* ---------- TOP CATEGORIES ---------- */
.cd-catgrid{display:flex;gap:24px;overflow-x:auto;padding:4px 2px 10px;scroll-behavior:smooth;scrollbar-width:none;}
.cd-catgrid::-webkit-scrollbar{display:none;}
.cd-catcard{flex:0 0 auto;width:120px;text-align:center;transition:.25s;}
.cd-catcard img{width:104px;height:104px;object-fit:cover;border-radius:50%;margin:0 auto 10px;border:1px solid #e5e7eb;transition:.3s;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.cd-catcard:hover img{transform:translateY(-5px);box-shadow:0 10px 22px rgba(0,0,0,.14);}
.cd-catcard span{font-size:13.5px;font-weight:700;display:block;line-height:1.3;color:#111;}

/* ---------- FLASH / DEAL BANNER STRIP ---------- */
.cd-deal{    background: black;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    padding: 16px 20px;
    margin-bottom: 18px;
    border: none;
    border-bottom: none;
    border-radius: 11px 11px 0 0;
    box-shadow: none;}
.cd-deal-ttl{font-size:19px;font-weight:800;letter-spacing:.6px;display:flex;align-items:center;gap:8px;margin:0;}
.cd-deal-ttl .cd-bolt{width:26px;height:26px;display:grid;place-items:center;background:var(--cd-secondary);border-radius:50%;font-size:14px;animation:cd-pulse 1.4s infinite;}
@keyframes cd-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.14)}}
.cd-deal-sub{font-size:12.5px;opacity:.85;margin:2px 0 0;}
.cd-timer{margin-left:auto;display:flex;align-items:center;gap:8px;}
.cd-timer-lbl{font-size:12px;opacity:.85;}
.cd-tbox{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);border-radius:8px;min-width:52px;padding:5px 6px;text-align:center;backdrop-filter:blur(4px);}
.cd-tbox b{display:block;font-size:17px;line-height:1;font-variant-numeric:tabular-nums;}
.cd-tbox i{font-style:normal;font-size:10px;opacity:.8;text-transform:uppercase;}
.cd-tsep{font-weight:700;opacity:.6;}
.cd-deal-body{background:#fff;border-radius:0 0 var(--cd-radius) var(--cd-radius);padding:16px 14px;box-shadow:var(--cd-shadow);}

/* ---------- PRODUCT GRID / SLIDER ---------- */
.cd-prow{position:relative;}
.cd-pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(165px,1fr));gap:12px;}
.cd-pscroll{display:flex;gap:12px;overflow-x:auto;scroll-behavior:smooth;padding-bottom:6px;scrollbar-width:none;}
.cd-pscroll::-webkit-scrollbar{display:none;}
.cd-pscroll > .cd-card{flex:0 0 clamp(155px,44vw,206px);}
.cd-nav{position:absolute;top:38%;width:34px;height:34px;border-radius:50%;border:0;background:#fff;box-shadow:var(--cd-shadow-lg);color:var(--cd-primary);cursor:pointer;z-index:5;font-size:16px;display:grid;place-items:center;}
.cd-nav-l{left:-14px}.cd-nav-r{right:-14px}

/* ---------- PRODUCT CARD ---------- */
.cd-card{background:#fff;border:1px solid var(--cd-line);border-radius:var(--cd-radius);overflow:hidden;position:relative;transition:.28s;display:flex;flex-direction:column;}
.cd-card:hover{transform:translateY(-6px);box-shadow:var(--cd-shadow-lg);border-color:transparent;}
.cd-card-img{position:relative;overflow:hidden;background:#fafbfc;}
.cd-card-img img{width:100%;aspect-ratio:1/1;object-fit:cover;transition:transform .55s;}
.cd-card:hover .cd-card-img img{transform:scale(1.07);}
.cd-badge{position:absolute;top:8px;left:8px;background:var(--cd-secondary);color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;z-index:2;}
.cd-stock-out{position:absolute;inset:0;background:rgba(255,255,255,.72);display:grid;place-items:center;font-weight:800;color:var(--cd-secondary);font-size:14px;z-index:3;}
.cd-quick{position:absolute;right:8px;top:8px;width:32px;height:32px;border-radius:50%;border:0;background:#fff;box-shadow:var(--cd-shadow);color:var(--cd-primary);cursor:pointer;opacity:0;transform:translateX(8px);transition:.25s;z-index:4;display:grid;place-items:center;}
.cd-card:hover .cd-quick{opacity:1;transform:none;}
.cd-card-body{padding:9px 10px 11px;display:flex;flex-direction:column;gap:5px;flex:1;}
.cd-card-name{font-size:13px;font-weight:600;line-height:1.35;min-height:35px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.cd-card-name:hover{color:var(--cd-primary);}
.cd-stars{display:flex;gap:1px;font-size:11px;color:#f5a623;}
.cd-stars .off{color:#d7dbe0;}
.cd-price{display:flex;align-items:baseline;gap:7px;flex-wrap:wrap;}
.cd-price b{font-size:15.5px;font-weight:800;color:var(--cd-secondary);}
.cd-price del{font-size:12.5px;color:var(--cd-muted);}
.cd-sold{font-size:11px;color:var(--cd-muted);display:flex;justify-content:space-between;}
.cd-bar{height:5px;background:#eef1f5;border-radius:9px;overflow:hidden;}
.cd-bar i{display:block;height:100%;border-radius:9px;background:linear-gradient(90deg,var(--cd-secondary),#ff8a3d);}
.cd-btns{display:flex;gap:6px;margin-top:auto;padding-top:6px;}
.cd-btns form{flex:1;margin:0;}
.cd-order{width:100%;border:0;cursor:pointer;background:var(--cd-primary);color:#fff;font-size:13px;font-weight:700;padding:8px 6px;border-radius:8px;transition:.25s;display:block;text-align:center;font-family:inherit;}
.cd-order:hover{background:var(--cd-secondary);}
.cd-cart{width:38px;flex:0 0 38px;border:1px solid var(--cd-line);background:#fff;color:var(--cd-primary);border-radius:8px;cursor:pointer;font-size:14px;transition:.25s;}
.cd-cart:hover{background:var(--cd-primary);color:#fff;border-color:var(--cd-primary);}

/* ---------- BRANDS ---------- */
.cd-brands{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;}
.cd-brand{background:#fff;border:1px solid var(--cd-line);border-radius:var(--cd-radius);padding:12px;display:grid;place-items:center;gap:6px;transition:.25s;}
.cd-brand:hover{transform:translateY(-4px);box-shadow:var(--cd-shadow-lg);}
.cd-brand img{height:52px;object-fit:contain;}
.cd-brand span{font-size:12px;font-weight:600;text-align:center;}

/* ---------- MERCHANT SHOPS ---------- */
.cd-shops{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:12px;}
.cd-shop{background:#fff;border:1px solid var(--cd-line);border-radius:var(--cd-radius);padding:16px 12px;text-align:center;position:relative;transition:.25s;}
.cd-shop:hover{transform:translateY(-5px);box-shadow:var(--cd-shadow-lg);}
.cd-verified{position:absolute;top:8px;right:8px;background:#e8f7ee;color:#12a150;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;}
.cd-shop img{width:70px;height:70px;border-radius:50%;object-fit:cover;margin:0 auto 8px;border:2px solid var(--cd-line);}
.cd-shop h4{margin:0 0 3px;font-size:14px;font-weight:700;}
.cd-shop small{color:var(--cd-muted);font-size:11.5px;}
.cd-visit{display:inline-block;margin-top:9px;font-size:12px;font-weight:600;color:#fff;background:var(--cd-primary);padding:6px 16px;border-radius:20px;}

/* ---------- FEATURES ---------- */
.cd-feat{display:grid;grid-template-columns:repeat(auto-fit,minmax(235px,1fr));gap:12px;}
.cd-feat-item{background:#fff;border:1px solid var(--cd-line);border-radius:var(--cd-radius);padding:16px;display:flex;align-items:center;gap:12px;}
.cd-feat-ico{width:46px;height:46px;flex:0 0 46px;border-radius:50%;display:grid;place-items:center;background:rgba(48,61,110,.08);color:var(--cd-primary);font-size:18px;}
.cd-feat-item h5{margin:0 0 2px;font-size:14px;font-weight:700;}
.cd-feat-item p{margin:0;font-size:12px;color:var(--cd-muted);}

/* ---------- QUICK ORDER POPUP (হাই কনভার্টিং) ---------- */
.cd-mo{position:fixed;inset:0;z-index:99999;display:none;}
.cd-mo.on{display:block;}
.cd-mo-bg{position:absolute;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(2px);animation:cd-fade .2s;}
@keyframes cd-fade{from{opacity:0}to{opacity:1}}
.cd-mo-box{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:min(820px,94vw);max-height:92vh;overflow:auto;background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.3);animation:cd-up .28s cubic-bezier(.2,.8,.2,1);}
@keyframes cd-up{from{opacity:0;transform:translate(-50%,-44%)}to{opacity:1;transform:translate(-50%,-50%)}}
.cd-mo-head{position:sticky;top:0;background:#fff;z-index:3;display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid var(--cd-line);}
.cd-mo-head h5{margin:0;font-size:15px;font-weight:800;color:var(--cd-primary);}
.cd-mo-x{border:0;background:#f1f3f7;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:15px;line-height:1;}
.cd-mo-x:hover{background:var(--cd-secondary);color:#fff;}
.cd-mo-body{display:grid;grid-template-columns:minmax(0,240px) minmax(0,1fr);gap:18px;padding:18px;}
.cd-mo-img{border-radius:12px;overflow:hidden;border:1px solid var(--cd-line);}
.cd-mo-img img{width:100%;aspect-ratio:1/1;object-fit:cover;}
.cd-mo-name{font-size:16px;font-weight:700;line-height:1.4;margin:0 0 8px;}
.cd-mo-price{display:flex;align-items:baseline;gap:9px;flex-wrap:wrap;margin-bottom:6px;}
.cd-mo-price b{font-size:24px;font-weight:800;color:var(--cd-secondary);}
.cd-mo-price del{color:var(--cd-muted);font-size:15px;}
.cd-mo-save{background:#e8f7ee;color:#12a150;font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:20px;}
.cd-mo-stock{font-size:12.5px;color:var(--cd-secondary);font-weight:600;margin-bottom:12px;}
.cd-lbl{font-size:13px;font-weight:800;margin:0 0 7px;display:flex;align-items:center;gap:6px;}
.cd-lbl em{font-style:normal;color:var(--cd-secondary);}
.cd-lbl .cd-req{font-size:11px;font-weight:600;color:var(--cd-muted);}
.cd-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;}
.cd-chip{border:1.5px solid #dfe3e8;background:#fff;min-width:48px;padding:8px 15px;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;transition:.18s;font-family:inherit;position:relative;}
.cd-chip-stock{display:block;font-size:10px;color:#12a150;margin-top:2px;font-weight:600;}
.cd-chip.cd-off .cd-chip-stock{color:#e11d48;}
.cd-chip:hover{border-color:var(--cd-primary);}
.cd-chip.on{border-color:var(--cd-primary);background:var(--cd-primary);color:#fff;box-shadow:0 4px 12px rgba(48,61,110,.28);}
.cd-chip .cd-dot{display:inline-block;width:13px;height:13px;border-radius:50%;border:1px solid rgba(0,0,0,.15);margin-right:6px;vertical-align:-2px;}
.cd-chips.cd-err .cd-chip{border-color:var(--cd-secondary);}
.cd-chip.cd-off{opacity:.35;cursor:not-allowed;text-decoration:line-through;background:#f5f6f8;}
.cd-chip.cd-off:hover{border-color:#dfe3e8;}
.cd-shake{animation:cd-sh .4s;}
@keyframes cd-sh{0%,100%{transform:translateX(0)}25%{transform:translateX(-7px)}75%{transform:translateX(7px)}}
.cd-qty{display:flex;align-items:center;gap:0;border:1.5px solid #dfe3e8;border-radius:9px;width:fit-content;overflow:hidden;margin-bottom:14px;}
.cd-qty button{width:40px;height:40px;border:0;background:#f7f8fa;font-size:18px;cursor:pointer;font-family:inherit;color:var(--cd-primary);font-weight:700;}
.cd-qty button:hover{background:var(--cd-primary);color:#fff;}
.cd-qty input{width:56px;height:40px;border:0;text-align:center;font-size:15px;font-weight:700;outline:none;font-family:inherit;}
.cd-total{display:flex;justify-content:space-between;align-items:center;background:#f7f9fc;border:1px dashed #cfd8e3;border-radius:10px;padding:11px 14px;margin-bottom:14px;}
.cd-total span{font-size:13.5px;font-weight:700;color:var(--cd-muted);}
.cd-total b{font-size:21px;font-weight:800;color:var(--cd-primary);}
.cd-mo-btns{display:grid;grid-template-columns:1fr auto;gap:9px;}
.cd-confirm{border:0;cursor:pointer;background:linear-gradient(90deg,var(--cd-secondary),#ff6a3d);color:#fff;font-size:16px;font-weight:800;padding:15px 10px;border-radius:11px;font-family:inherit;box-shadow:0 8px 20px rgba(255,0,0,.28);transition:.22s;display:flex;align-items:center;justify-content:center;gap:8px;}
.cd-confirm:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(255,0,0,.36);}
.cd-confirm:active{transform:none;}
.cd-addcart{border:1.5px solid var(--cd-primary);background:#fff;color:var(--cd-primary);font-size:14px;font-weight:700;padding:0 18px;border-radius:11px;cursor:pointer;font-family:inherit;transition:.2s;}
.cd-addcart:hover{background:var(--cd-primary);color:#fff;}
.cd-trust{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:14px;padding-top:13px;border-top:1px solid var(--cd-line);}
.cd-trust div{text-align:center;font-size:11px;color:var(--cd-muted);line-height:1.4;font-weight:600;}
.cd-trust i{display:block;font-style:normal;font-size:17px;margin-bottom:3px;}
.cd-mo-help{text-align:center;font-size:12.5px;color:var(--cd-muted);margin-top:11px;}
.cd-mo-help a{color:var(--cd-primary);font-weight:700;}
.cd-mo-view{display:block;text-align:center;font-size:12.5px;margin-top:8px;color:var(--cd-muted);text-decoration:underline;}

/* মোবাইলে বটম-শিট স্টাইল (কনভার্শনের জন্য সেরা) */
@media(max-width:640px){
    .cd-mo-box{top:auto;bottom:0;left:0;transform:none;width:100%;max-height:94vh;border-radius:18px 18px 0 0;animation:cd-sheet .3s cubic-bezier(.2,.8,.2,1);}
    @keyframes cd-sheet{from{transform:translateY(100%)}to{transform:none}}
    .cd-mo-body{grid-template-columns:1fr;gap:14px;padding:14px;}
    .cd-mo-img{max-width:190px;margin:0 auto;}
    .cd-mo-btns{position:sticky;bottom:0;background:#fff;padding-top:8px;}
}

/* ---------- LIVE PURCHASE TOAST ---------- */
.cd-live{position:fixed;left:16px;bottom:16px;background:#fff;border-radius:12px;box-shadow:var(--cd-shadow-lg);display:flex;gap:10px;padding:10px;width:290px;z-index:9998;transform:translateY(140%);transition:transform .5s cubic-bezier(.4,0,.2,1);}
.cd-live.on{transform:none;}
.cd-live img{width:52px;height:52px;border-radius:8px;object-fit:cover;}
.cd-live b{font-size:12.5px;display:block;}
.cd-live p{margin:2px 0 0;font-size:11.5px;color:var(--cd-muted);line-height:1.35;}
.cd-live .cd-live-x{position:absolute;top:4px;right:6px;border:0;background:none;cursor:pointer;color:#9aa3af;font-size:13px;}
.cd-live-vf{color:#12a150;font-size:10.5px;font-weight:700;}

/* ---------- BACK TO TOP ---------- */
.cd-top{position:fixed;right:16px;bottom:16px;width:42px;height:42px;border-radius:50%;border:0;background:var(--cd-primary);color:#fff;font-size:17px;cursor:pointer;box-shadow:var(--cd-shadow-lg);opacity:0;pointer-events:none;transition:.3s;z-index:9997;}
.cd-top.on{opacity:1;pointer-events:auto;}

/* ============ RESPONSIVE ============ */
@media(max-width:1100px){
    .cd-hero{grid-template-columns:minmax(0,1fr) minmax(0,340px);}
}
@media(max-width:991px){
    .cd-hero{grid-template-columns:1fr;}
    .cd-hero-side{grid-template-columns:1fr 1fr;grid-template-rows:auto;}
    .cd-nav{display:none;}
}
@media(max-width:767px){
    .cd-head:before,.cd-head:after{flex:1 1 20px;}
    .cd-head h2{font-size:16px;letter-spacing:.6px;}
    .cd-head .cd-viewall{position:static;transform:none;}
    .cd-head{flex-wrap:wrap;}
    .cd-catcard{width:96px;}
    .cd-catcard img{width:88px;height:88px;}
    .cd-catgrid{gap:16px;}
    .cd-deal{flex-direction:column;align-items:flex-start;gap:10px;}
    .cd-timer{margin-left:0;width:100%;justify-content:space-between;}
    .cd-tbox{min-width:46px;}
    .cd-mo-body{grid-template-columns:1fr;}
}
@media(max-width:575px){
    .cd-hero-side{grid-template-columns:1fr;}
    .cd-pgrid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:9px;}
    .cd-catcard{width:84px;}
    .cd-catcard img{width:76px;height:76px;}
    .cd-catcard span{font-size:12px;}
    .cd-card-name{font-size:12.5px;}
    .cd-price b{font-size:14.5px;}
    .cd-slides img{aspect-ratio:16/9;}
    .cd-live{left:8px;right:8px;width:auto;}
}
</style>

<div class="cd-home">

{{-- ---------- হিরো : বড় স্লাইডার + ডানপাশে ২টা ব্যানার ---------- --}}
<div class="cd-wrap">
    <div class="cd-hero">

        <div class="cd-slider" id="cdSlider">
            <div class="cd-slides" id="cdSlides">
                @forelse ($sliders as $s)
                <a href="{{ $s->link ?: '#' }}"><img src="{{ asset($s->image) }}" alt="slider"></a>
                @empty
                <div><img src="{{ asset($generalsetting->og_baner ?? 'public/logo.png') }}" alt="slider"></div>
                @endforelse
            </div>
            <button class="cd-sl-btn cd-sl-prev" type="button" onclick="cdSlide(-1)">❮</button>
            <button class="cd-sl-btn cd-sl-next" type="button" onclick="cdSlide(1)">❯</button>
            <div class="cd-dots" id="cdDots"></div>
        </div>

        @php
            $cdSideBanners = [
                [
                    'image' => 'https://ecommerce4.creativedesign.com.bd/public/uploads/banner/1783342266-6a4ba4ba14809-captan-fashion-banner-fa8ebb88ff.webp',
                    'link'  => url('#'),
                    'alt'   => 'Polo T-Shirt Collection',
                ],
                [
                    'image' => 'https://ecommerce4.creativedesign.com.bd/public/uploads/banner/1783342291-6a4ba4d356321-website-banner-71c5d21967.webp',
                    'link'  => url('/'),
                    'alt'   => 'Premium Quality Tracksuit',
                ],
            ];
        @endphp

        <div class="cd-hero-side">
            @foreach($cdSideBanners as $sb)
            <a href="{{ $sb['link'] }}">
                <img src="{{ asset($sb['image']) }}" alt="{{ $sb['alt'] }}" loading="lazy">
            </a>
            @endforeach
        </div>

    </div>
</div>



{{-- ============================================================
     PART 5 : TOP CATEGORIES
     ============================================================ --}}
@if($frontcategory->count())
<section class="cd-sec"><div class="cd-wrap">
    <div class="cd-head"><h2>Top Categories</h2></div>
    <div class="cd-catgrid">
        @foreach($frontcategory as $cat)
        <a class="cd-catcard" href="{{ route('category', $cat->slug) }}">
            <img src="{{ asset($cat->image ?: $cat->icon) }}" alt="{{ $cat->name }}" loading="lazy">
            <span>{{ $cat->name }}</span>
        </a>
        @endforeach
    </div>
</div></section>
@endif


{{-- ============================================================
     PART 7 : FLASH SALE (কাউন্টডাউন + প্রোডাক্ট)
     ============================================================ --}}
@if($flas_sales->count())
<section class="cd-sec"><div class="cd-wrap">
    <div class="cd-deal">
        <div>
            <h3 class="cd-deal-ttl"><span class="cd-bolt">⚡</span> FLASH SALE</h3>
            <p class="cd-deal-sub">Flash OFF — Shop fast!</p>
        </div>
        @if($flashEnd)
        <div class="cd-timer" data-cd-end="{{ \Carbon\Carbon::parse($flashEnd)->format('Y-m-d H:i:s') }}">
            <span class="cd-timer-lbl">Ends in</span>
            <span class="cd-tbox"><b data-d>00</b><i>Days</i></span><span class="cd-tsep">:</span>
            <span class="cd-tbox"><b data-h>00</b><i>Hours</i></span><span class="cd-tsep">:</span>
            <span class="cd-tbox"><b data-m>00</b><i>Min</i></span><span class="cd-tsep">:</span>
            <span class="cd-tbox"><b data-s>00</b><i>Sec</i></span>
        </div>
        @endif
        <a class="cd-viewall" style="background: #ff0000;border-color:#fff" href="{{ route('flashsales') }}">View All</a>
    </div>

    <div class="cd-deal-body">
        <div class="cd-prow">
            <button class="cd-nav cd-nav-l" type="button" onclick="cdScroll('flashRow',-1)">❮</button>
            <div class="cd-pscroll" id="flashRow">
                @foreach($flas_sales as $p)
                    {!! $cdCard($p, true) !!}
                @endforeach
            </div>
            <button class="cd-nav cd-nav-r" type="button" onclick="cdScroll('flashRow',1)">❯</button>
        </div>
    </div>
</div></section>
@endif

{{-- ============================================================
     PART 8 : হট ডিল ব্যানার
     ============================================================ --}}
@if($hitdealsbaner->count())
<section class="cd-sec"><div class="cd-wrap"><div class="cd-ad-full">
    @foreach($hitdealsbaner as $ad)
    <a href="{{ $ad->link ?: '#' }}"><img src="{{ asset($ad->image) }}" alt="Promo banner" loading="lazy"></a>
    @endforeach
</div></div></section>
@endif

{{-- ============================================================
     PART 9 : HOT DEAL (কাউন্টডাউন + প্রোডাক্ট)
     ============================================================ --}}
@if($hotdeal_top->count())
<section class="cd-sec"><div class="cd-wrap">
    <div class="cd-deal" style="background:linear-gradient(100deg,#b30000,var(--cd-secondary))">
        <div>
            <h3 class="cd-deal-ttl"><span class="cd-bolt" style="background:#fff;color:var(--cd-secondary)">🔥</span> HOT DEAL</h3>
            <p class="cd-deal-sub">Limited-time special offer</p>
        </div>
        @if($dealEnd)
        <div class="cd-timer" data-cd-end="{{ \Carbon\Carbon::parse($dealEnd)->format('Y-m-d H:i:s') }}">
            <span class="cd-timer-lbl">Ends in</span>
            <span class="cd-tbox"><b data-d>00</b><i>Days</i></span><span class="cd-tsep">:</span>
            <span class="cd-tbox"><b data-h>00</b><i>Hours</i></span><span class="cd-tsep">:</span>
            <span class="cd-tbox"><b data-m>00</b><i>Min</i></span><span class="cd-tsep">:</span>
            <span class="cd-tbox"><b data-s>00</b><i>Sec</i></span>
        </div>
        @endif
        <a class="cd-viewall" style="background:#fff;border-color:#fff;color:var(--cd-secondary)" href="{{ route('hotdeals') }}">View All</a>
    </div>

    <div class="cd-deal-body">
        <div class="cd-prow">
            <button class="cd-nav cd-nav-l" type="button" onclick="cdScroll('dealRow',-1)">❮</button>
            <div class="cd-pscroll" id="dealRow">
                @foreach($hotdeal_top as $p)
                    {!! $cdCard($p, false) !!}
                @endforeach
                @foreach($hotdeal_bottom as $p)
                    {!! $cdCard($p, false) !!}
                @endforeach
            </div>
            <button class="cd-nav cd-nav-r" type="button" onclick="cdScroll('dealRow',1)">❯</button>
        </div>
    </div>
</div></section>
@endif

{{-- ============================================================
     PART 10 : ক্যাম্পেইন অ্যাড
     ============================================================ --}}
@if($campaognads->count())
<section class="cd-sec"><div class="cd-wrap"><div class="cd-ad-full">
    @foreach($campaognads as $ad)
    <a href="{{ $ad->link ?: '#' }}"><img src="{{ asset($ad->image) }}" alt="Campaign" loading="lazy"></a>
    @endforeach
</div></div></section>
@endif

{{-- ============================================================
     PART 11 : ক্যাটাগরি অনুযায়ী প্রোডাক্ট
     ============================================================ --}}
@if($homeproducts)
    @foreach($homeproducts as $hc)
        @continue($hc->products->isEmpty())
        <section class="cd-sec"><div class="cd-wrap">
            <div class="cd-head">
                <h2>{{ $hc->name }}</h2>
                <a class="cd-viewall" href="{{ route('category', $hc->slug) }}">View All</a>
            </div>
            <div class="cd-prow">
                <button class="cd-nav cd-nav-l" type="button" onclick="cdScroll('cat{{ $hc->id }}',-1)">❮</button>
                <div class="cd-pscroll" id="cat{{ $hc->id }}">
                    @foreach($hc->products as $p)
                        {!! $cdCard($p, false) !!}
                    @endforeach
                </div>
                <button class="cd-nav cd-nav-r" type="button" onclick="cdScroll('cat{{ $hc->id }}',1)">❯</button>
            </div>
        </div></section>
    @endforeach
@endif

{{-- ============================================================
     PART 12 : সব প্রোডাক্ট
     ============================================================ --}}
@if($all_products && $all_products->count())
<section class="cd-sec"><div class="cd-wrap">
    <div class="cd-head"><h2>All Products</h2></div>
    <div class="cd-pgrid">
        @foreach($all_products as $p)
            {!! $cdCard($p, false) !!}
        @endforeach
    </div>
</div></section>
@endif

{{-- ============================================================
     PART 13 : ব্র্যান্ড
     ============================================================ --}}
@if($brands->count())
<section class="cd-sec"><div class="cd-wrap">
    <div class="cd-head"><h2>Shop By Brand</h2></div>
    <div class="cd-brands">
        @foreach($brands as $b)
        <a class="cd-brand" href="{{ route('brand.products', $b->slug) }}">
            <img src="{{ asset($b->image) }}" alt="{{ $b->name }}" loading="lazy">
            <span>{{ $b->name }}</span>
        </a>
        @endforeach
    </div>
</div></section>
@endif

{{-- ============================================================
     PART 14 : MERCHANT SHOPS
     ============================================================ --}}
@if(($generalsetting->vendor_enabled ?? 0) == 1 && $vendors->count())
<section class="cd-sec"><div class="cd-wrap">
    <div class="cd-head">
        <h2>Merchant Shops</h2>
        <a class="cd-viewall" href="{{ route('sellers') }}">View All</a>
    </div>
    <div class="cd-shops">
        @foreach($vendors->take(5) as $v)
        <a class="cd-shop" href="{{ route('vendor.shop', $v->slug) }}">
            <span class="cd-verified">✔ Verified</span>
            <img src="{{ asset($v->logo) }}" alt="{{ $v->shop_name }}" loading="lazy">
            <h4>{{ $v->shop_name }}</h4>
            <small>⭐ {{ $v->average_rating ?? 0 }} ({{ $v->total_reviews ?? 0 }}) • {{ $v->products_count ?? 0 }} Products</small>
            <span class="cd-visit">Visit Shop</span>
        </a>
        @endforeach
    </div>
</div></section>
@endif

{{-- ============================================================
     PART 15 : FEATURES
     ============================================================ --}}
<section class="cd-sec" style="padding-bottom:28px"><div class="cd-wrap">
    <div class="cd-feat">
        <div class="cd-feat-item"><div class="cd-feat-ico">🌿</div><div><h5>100% Authentic</h5><p>Natural and safe ingredients.</p></div></div>
        <div class="cd-feat-item"><div class="cd-feat-ico">🚚</div><div><h5>Fast delivery</h5><p>It reaches the whole country quickly.</p></div></div>
        <div class="cd-feat-item"><div class="cd-feat-ico">🔒</div><div><h5>Secure payment</h5><p>100% secure transaction system.</p></div></div>
        <div class="cd-feat-item"><div class="cd-feat-ico">🎧</div><div><h5>Easy support</h5><p>Quick help is always ready.</p></div></div>
    </div>
</div></section>

{{-- ============================================================
     PART 16 : QUICK VIEW MODAL + LIVE TOAST + BACK TO TOP
     ============================================================ --}}
{{-- ---------- QUICK ORDER পপআপ (সাইজ সিলেক্ট করে সরাসরি চেকআউট) ---------- --}}
<div class="cd-mo" id="cdMo">
    <div class="cd-mo-bg" onclick="cdCloseMo()"></div>
    <div class="cd-mo-box">
        <div class="cd-mo-head">
            <h5>🛒 দ্রুত অর্ডার করুন</h5>
            <button class="cd-mo-x" type="button" onclick="cdCloseMo()">✕</button>
        </div>

        <form class="cd-mo-body" id="cdMoForm" action="{{ route('cart.store') }}" method="POST">
            @csrf
            <input type="hidden" name="id"            id="cdMoId">
            <input type="hidden" name="product_size"  id="cdMoSize">
            <input type="hidden" name="product_color" id="cdMoColor">
            <input type="hidden" name="qty"           id="cdMoQty" value="1">
            <input type="hidden" name="order_now"     id="cdMoNow" value="1">

            <div>
                <div class="cd-mo-img"><img id="cdMoImg" src="" alt="Product"></div>
                <a class="cd-mo-view" id="cdMoLink" href="#">বিস্তারিত দেখুন</a>
            </div>

            <div>
                <h4 class="cd-mo-name" id="cdMoName"></h4>
                <div class="cd-mo-price">
                    <b id="cdMoPrice"></b>
                    <del id="cdMoOld"></del>
                    <span class="cd-mo-save" id="cdMoSave"></span>
                </div>
                <div class="cd-mo-stock" id="cdMoStock"></div>

                <div id="cdMoSizeWrap" style="display:none">
                    <p class="cd-lbl">সাইজ সিলেক্ট করুন <em>*</em> <span class="cd-req" id="cdMoSizePick"></span></p>
                    <div class="cd-chips" id="cdMoSizes"></div>
                </div>

                <div id="cdMoColorWrap" style="display:none">
                    <p class="cd-lbl">কালার সিলেক্ট করুন <em>*</em> <span class="cd-req" id="cdMoColorPick"></span></p>
                    <div class="cd-chips" id="cdMoColors"></div>
                </div>

                <p class="cd-lbl">পরিমাণ</p>
                <div class="cd-qty">
                    <button type="button" onclick="cdQty(-1)">−</button>
                    <input type="text" id="cdMoQtyBox" value="1" readonly>
                    <button type="button" onclick="cdQty(1)">+</button>
                </div>

                <div class="cd-total">
                    <span>সর্বমোট</span>
                    <b id="cdMoTotal">৳ 0</b>
                </div>

                <div class="cd-mo-btns">
                    <button type="submit" class="cd-confirm" id="cdMoConfirm">অর্ডার কনফার্ম করুন →</button>
                    <button type="button" class="cd-addcart" onclick="cdMoAddCart()">কার্টে রাখুন</button>
                </div>

                <div class="cd-trust">
                    <div><i>🚚</i> ক্যাশ অন ডেলিভারি</div>
                    <div><i>🔄</i> সহজ রিটার্ন</div>
                    <div><i>✅</i> ১০০% অরিজিনাল</div>
                </div>

                @isset($contact)
                @if(!empty($contact->hotline))
                <p class="cd-mo-help">যেকোনো সাহায্যে কল করুন: <a href="tel:{{ $contact->hotline }}">{{ $contact->hotline }}</a></p>
                @endif
                @endisset
            </div>
        </form>
    </div>
</div>

<div class="cd-live" id="cdLive">
    <button class="cd-live-x" type="button" onclick="document.getElementById('cdLive').classList.remove('on')">✕</button>
    <img id="cdLiveImg" src="" alt="Product">
    <div>
        <b id="cdLiveName">—</b>
        <p><span id="cdLiveCity">ঢাকা</span> থেকে একজন কাস্টমার just purchased</p>
        <span class="cd-live-vf">🛡️ Verified Order</span>
    </div>
</div>

<button class="cd-top" id="cdTop" type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

</div>{{-- /.cd-home --}}

{{-- ============================================================
     PART 17 : সমস্ত JAVASCRIPT (এক জায়গায়)
     ============================================================ --}}
<script>
(function(){
    'use strict';

    /* ---------- ১. হিরো স্লাইডার ---------- */
    var slidesBox = document.getElementById('cdSlides');
    var dotsBox   = document.getElementById('cdDots');
    var cur = 0, total = slidesBox ? slidesBox.children.length : 0, timer = null;

    function paint(){
        if(!slidesBox) return;
        slidesBox.style.transform = 'translateX(' + (-cur * 100) + '%)';
        if(dotsBox) [].forEach.call(dotsBox.children, function(d,i){ d.className = i === cur ? 'on' : ''; });
    }
    window.cdSlide = function(dir){
        if(total < 2) return;
        cur = (cur + dir + total) % total; paint(); restart();
    };
    function restart(){ clearInterval(timer); timer = setInterval(function(){ window.cdSlide(1); }, 5000); }

    if(total > 1 && dotsBox){
        for(var i = 0; i < total; i++){
            (function(idx){
                var d = document.createElement('span');
                d.onclick = function(){ cur = idx; paint(); restart(); };
                dotsBox.appendChild(d);
            })(i);
        }
        paint(); restart();
        // মোবাইল swipe
        var sx = 0;
        slidesBox.addEventListener('touchstart', function(e){ sx = e.touches[0].clientX; }, {passive:true});
        slidesBox.addEventListener('touchend', function(e){
            var dx = e.changedTouches[0].clientX - sx;
            if(Math.abs(dx) > 45) window.cdSlide(dx < 0 ? 1 : -1);
        }, {passive:true});
    }

    /* ---------- ২. কাউন্টডাউন টাইমার ---------- */
    var timers = document.querySelectorAll('[data-cd-end]');
    function tick(){
        timers.forEach(function(t){
            var end = new Date(t.getAttribute('data-cd-end').replace(/-/g,'/')).getTime();
            var gap = end - Date.now();
            if(gap < 0) gap = 0;
            var d = Math.floor(gap / 86400000),
                h = Math.floor(gap % 86400000 / 3600000),
                m = Math.floor(gap % 3600000 / 60000),
                s = Math.floor(gap % 60000 / 1000);
            var p = function(n){ return n < 10 ? '0' + n : n; };
            t.querySelector('[data-d]').textContent = p(d);
            t.querySelector('[data-h]').textContent = p(h);
            t.querySelector('[data-m]').textContent = p(m);
            t.querySelector('[data-s]').textContent = p(s);
        });
    }
    if(timers.length){ tick(); setInterval(tick, 1000); }

    /* ---------- ৩. প্রোডাক্ট রো স্ক্রল ---------- */
    window.cdScroll = function(id, dir){
        var row = document.getElementById(id);
        if(row) row.scrollBy({ left: dir * (row.clientWidth * 0.8), behavior: 'smooth' });
    };

    /* ---------- ৪. QUICK ORDER পপআপ ---------- */
    var CDP = @json($cdProducts);      // প্রোডাক্ট ডাটা (সাইজ / কালার / ভ্যারিয়েন্ট দাম)
    var mo  = document.getElementById('cdMo');
    var cd  = { p:null, size:null, color:null, qty:1, price:0, stock:0, cartOnly:false };

    window.cdOrder = function(id, cartOnly){
        var p = CDP[id]; if(!p){ return; }
        cd = { p:p, size:null, color:null, qty:1, price:p.price, stock:p.stock, cartOnly: !!cartOnly };

        document.getElementById('cdMoId').value    = p.id;
        document.getElementById('cdMoImg').src     = p.img;
        document.getElementById('cdMoName').textContent = p.name;
        document.getElementById('cdMoLink').href   = p.url;
        document.getElementById('cdMoSize').value  = '';
        document.getElementById('cdMoColor').value = '';
        document.getElementById('cdMoNow').value   = cd.cartOnly ? '' : '1';

        var btn = document.getElementById('cdMoConfirm');
        btn.disabled = false;
        btn.textContent = cd.cartOnly ? 'কার্টে যোগ করুন' : 'অর্ডার কনফার্ম করুন →';

        buildChips('cdMoSizes',  'cdMoSizeWrap',  p.sizes,  'size');
        buildChips('cdMoColors', 'cdMoColorWrap', p.colors, 'color');

        /* একটাই অপশন থাকলে অটো সিলেক্ট (কম ক্লিক = বেশি অর্ডার) */
        if(p.sizes.length  === 1) pick('size',  p.sizes[0].id,  0);
        if(p.colors.length === 1) pick('color', p.colors[0].id, 0);

        cdSync();
        setQty(1);
        mo.classList.add('on');
        document.body.style.overflow = 'hidden';
    };

    function cdNum(n){ return Number(n || 0).toLocaleString('en-US'); }

    /* চিপ (সাইজ/কালার বাটন) তৈরি */
    function buildChips(boxId, wrapId, list, type){
        var box = document.getElementById(boxId), wrap = document.getElementById(wrapId);
        box.innerHTML = ''; box.classList.remove('cd-err');
        document.getElementById(type === 'size' ? 'cdMoSizePick' : 'cdMoColorPick').textContent = '';
        if(!list || !list.length){ wrap.style.display = 'none'; return; }
        wrap.style.display = '';
        list.forEach(function(o, idx){
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'cd-chip';
            b.dataset.id = o.id;
            b.dataset.stock = (type === 'size' && o.has_stock) ? o.stock : '';
            if(type === 'size' && o.has_stock && Number(o.stock) <= 0){ b.classList.add('cd-off'); }
            b.innerHTML = (type === 'color' && o.hex ? '<span class="cd-dot" style="background:' + o.hex + '"></span>' : '') + o.name +
                (type === 'size' && o.has_stock ? '<small class="cd-chip-stock">' + (o.stock > 0 ? o.stock + ' টি' : 'স্টক শেষ') + '</small>' : '');
            b.onclick = function(){ if(!b.classList.contains('cd-off')) pick(type, o.id, idx); };
            box.appendChild(b);
        });
    }

    /* সিলেক্ট */
    function pick(type, id, idx){
        var box = document.getElementById(type === 'size' ? 'cdMoSizes' : 'cdMoColors');
        [].forEach.call(box.children, function(b,i){ b.classList.toggle('on', i === idx); });
        box.classList.remove('cd-err');
        cd[type] = id;
        document.getElementById(type === 'size' ? 'cdMoSize' : 'cdMoColor').value = id;
        var lbl = box.children[idx] ? box.children[idx].textContent.trim() : '';
        document.getElementById(type === 'size' ? 'cdMoSizePick' : 'cdMoColorPick').textContent = '— ' + lbl;
        cdSync();
        setQty(cd.qty);
    }

    /* ভ্যারিয়েন্ট অনুযায়ী দাম / স্টক / অ্যাভেইলেবিলিটি আপডেট */
    function cdSync(){
        var p = cd.p, vs = p.variants || [];

        if(vs.length){
            /* যে কম্বিনেশন নেই সেটা ডিজেবল (ভুল অর্ডার আটকায়) */
            avail('cdMoSizes', function(id){
                return vs.some(function(v){ return v.s == id && (cd.color == null || v.c == null || v.c == cd.color); });
            });
            avail('cdMoColors', function(id){
                return vs.some(function(v){ return v.c == id && (cd.size == null || v.s == null || v.s == cd.size); });
            });

            var match = vs.filter(function(v){
                return (cd.size == null || v.s == null || v.s == cd.size) &&
                       (cd.color == null || v.c == null || v.c == cd.color);
            });
            var chosen = (!p.sizes.length  || cd.size  != null) &&
                         (!p.colors.length || cd.color != null);

            if(chosen && match.length){
                if(match[0].p > 0) cd.price = match[0].p;
                // Size stock is the sum of all colors for that size. When a
                // color is selected, use the exact size+color variant stock.
                var stockRows = match.filter(function(v){ return v.st !== null; });
                if(stockRows.length){
                    cd.stock = (cd.color != null || stockRows.length === 1)
                        ? Number(stockRows[0].st)
                        : stockRows.reduce(function(sum, v){ return sum + Number(v.st); }, 0);
                }
            } else {
                cd.price = p.price;
                cd.stock = p.stock;
            }
        }

        /* দাম */
        document.getElementById('cdMoPrice').textContent = '৳ ' + cdNum(cd.price);
        var oldEl = document.getElementById('cdMoOld'), saveEl = document.getElementById('cdMoSave');
        if(p.old && p.old > cd.price){
            oldEl.textContent  = '৳ ' + cdNum(p.old);
            saveEl.textContent = 'সাশ্রয় ৳ ' + cdNum(p.old - cd.price);
            oldEl.style.display = ''; saveEl.style.display = '';
        } else { oldEl.style.display = 'none'; saveEl.style.display = 'none'; }

        /* স্টক */
        var st = document.getElementById('cdMoStock');
        if(cd.stock !== null && cd.stock <= 0){
            st.textContent = '❌ এই ভ্যারিয়েন্টটি স্টকে নেই';
            st.style.color = '#e11d48';
        } else if(cd.stock > 0 && cd.stock <= 20){
            st.textContent = '🔥 তাড়াতাড়ি করুন! মাত্র ' + cd.stock + ' টি বাকি আছে';
            st.style.color = 'var(--cd-secondary)';
        } else {
            st.textContent = '✅ স্টকে আছে';
            st.style.color = '#12a150';
        }
    }

    function avail(boxId, fn){
        var box = document.getElementById(boxId);
        [].forEach.call(box.children, function(b){
            var hasStock = !b.dataset.stock || Number(b.dataset.stock) > 0;
            var ok = fn(b.dataset.id) && hasStock;
            b.classList.toggle('cd-off', !ok);
            if(!ok) b.classList.remove('on');
        });
    }

    /* পরিমাণ */
    window.cdQty = function(d){ setQty(cd.qty + d); };
    function setQty(q){
        var max = (cd.stock && cd.stock > 0) ? cd.stock : 99;
        cd.qty = Math.max(1, Math.min(q, max));
        document.getElementById('cdMoQtyBox').value = cd.qty;
        document.getElementById('cdMoQty').value    = cd.qty;
        document.getElementById('cdMoTotal').textContent = '৳ ' + cdNum(cd.price * cd.qty);
    }

    window.cdCloseMo = function(){ mo.classList.remove('on'); document.body.style.overflow = ''; };
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') cdCloseMo(); });

    /* ভ্যালিডেশন */
    function cdValidate(){
        var ok = true;
        if(cd.p.sizes.length  && !cd.size ){ flag('cdMoSizes');  ok = false; }
        if(cd.p.colors.length && !cd.color){ flag('cdMoColors'); ok = false; }
        if(ok && cd.stock !== null && cd.stock <= 0){ cdToast('এই ভ্যারিয়েন্টটি স্টকে নেই', 1); ok = false; }
        return ok;
    }
    function flag(id){
        var b = document.getElementById(id);
        b.classList.add('cd-err','cd-shake');
        setTimeout(function(){ b.classList.remove('cd-shake'); }, 420);
        b.scrollIntoView({block:'center', behavior:'smooth'});
    }

    /* কনফার্ম → cart.store (order_now=1) → সরাসরি Checkout */
    document.getElementById('cdMoForm').addEventListener('submit', function(e){
        if(!cdValidate()){ e.preventDefault(); return false; }
        var btn = document.getElementById('cdMoConfirm');
        btn.disabled = true; btn.textContent = 'অপেক্ষা করুন...';
    });

    /* শুধু কার্টে রাখা (রিলোড ছাড়া) */
    window.cdMoAddCart = function(){
        if(!cdValidate()) return;
        var form = document.getElementById('cdMoForm');
        var fd = new FormData(form);
        fd.delete('order_now');
        fetch(form.action, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
            .then(function(r){ return r.json().catch(function(){ return {success:true}; }); })
            .then(function(res){
                if(res && res.success === false){ cdToast(res.message || 'সমস্যা হয়েছে', 1); return; }
                var c = document.querySelector('.cart_count, #cart-qty span, .mobilecart-qty');
                if(c) c.textContent = (parseInt(c.textContent || 0, 10) + cd.qty);
                cdCloseMo(); cdToast('কার্টে যোগ হয়েছে ✔');
            })
            .catch(function(){ form.submit(); });
    };

    /* ---------- ৫. টোস্ট ---------- */
    function cdToast(msg, err){
        var t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:' + (err ? '#e11d48' : '#12a150') + ';color:#fff;padding:11px 22px;border-radius:30px;z-index:100000;font-size:13.5px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,.2)';
        document.body.appendChild(t);
        setTimeout(function(){ t.remove(); }, 2300);
    }

    /* ---------- ৬. লাইভ পারচেজ নোটিফিকেশন ---------- */
    var liveData = Object.keys(CDP).slice(0,10).map(function(k){ return { n: CDP[k].name.substring(0,34), i: CDP[k].img }; });
    var cities = ['ঢাকা','চট্টগ্রাম','সিলেট','খুলনা','রাজশাহী','বরিশাল','রংপুর','ময়মনসিংহ','কুমিল্লা','নারায়ণগঞ্জ'];
    var live = document.getElementById('cdLive');
    if(liveData.length){
        setInterval(function(){
            var d = liveData[Math.floor(Math.random() * liveData.length)];
            document.getElementById('cdLiveImg').src   = d.i;
            document.getElementById('cdLiveName').textContent = d.n;
            document.getElementById('cdLiveCity').textContent = cities[Math.floor(Math.random() * cities.length)];
            live.classList.add('on');
            setTimeout(function(){ live.classList.remove('on'); }, 5000);
        }, 14000);
    }

    /* ---------- ৭. ব্যাক টু টপ ---------- */
    var topBtn = document.getElementById('cdTop');
    window.addEventListener('scroll', function(){
        topBtn.classList.toggle('on', window.scrollY > 500);
    }, {passive:true});

})();
</script>
@endsection
