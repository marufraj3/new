<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $generalsetting->name }}</title>
        <link rel="shortcut icon" href="{{asset($generalsetting->favicon)}}" type="image/x-icon" />
        <!-- fot awesome -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/all.css" />
        <!-- core css -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/bootstrap.min.css" />
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/animate.css" />
        <!-- owl carousel -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/owl.theme.default.css" />
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/owl.carousel.min.css" />
        <!-- owl carousel -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/select2.min.css" />
        <!-- common css -->
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/style.css" />
        <link rel="stylesheet" href="{{ asset('public/frontEnd/campaign/css') }}/responsive.css" />
        <!-- ========== DataLayer Initialization ========== -->
        @php
            $camp_name      = strip_tags($campaign_data->name ?? '');
            $camp_slug      = $campaign_data->slug ?? '';
            $camp_id        = (string) $campaign_data->id;
            $_firstProd     = $products->first();
            $camp_value     = $_firstProd ? (float) $_firstProd->new_price : 0.0;
            $camp_products  = $products->map(function($p) {
                // সাইজ/কালার নাম প্রথমে variantPrices থেকে (নতুন এডমিন প্যানেল সেখানে সেভ করে),
                // পুরনো pivot টেবিল শুধু fallback — যাতে ID নাম্বার না দেখায়।
                $sizeOptions = [];
                $colorOptions = [];
                foreach ($p->variantPrices ?? [] as $v) {
                    if ($v->size_id && $v->size) {
                        if (!isset($sizeOptions[$v->size_id])) {
                            $sizeOptions[$v->size_id] = [
                                'id' => (string) $v->size_id,
                                'name' => $v->size->sizeName ?? $v->size->name ?? ('Size '.$v->size_id),
                                'stock' => 0,
                                'has_stock' => false,
                            ];
                        }
                        if ($v->stock !== null) {
                            $sizeOptions[$v->size_id]['stock'] += max(0, (int) $v->stock);
                            $sizeOptions[$v->size_id]['has_stock'] = true;
                        }
                    }
                    if ($v->color_id && $v->color) {
                        $colorOptions[$v->color_id] = [
                            'id' => (string) $v->color_id,
                            'name' => $v->color->colorName ?? $v->color->name ?? ('Color '.$v->color_id),
                            'hex' => $v->color->color ?? '',
                        ];
                    }
                }
                foreach ($p->sizes ?? [] as $s) {
                    if (!isset($sizeOptions[$s->id])) {
                        $sizeOptions[$s->id] = ['id' => (string) $s->id, 'name' => $s->sizeName ?? $s->name ?? '', 'stock' => 0, 'has_stock' => false];
                    }
                }
                foreach ($p->colors ?? [] as $c) {
                    if (!isset($colorOptions[$c->id])) {
                        $colorOptions[$c->id] = ['id' => (string) $c->id, 'name' => $c->colorName ?? $c->name ?? '', 'hex' => $c->color ?? ''];
                    }
                }

                $variantRows = collect($p->variantPrices ?? []);
                $hasVariantStock = $variantRows->contains(fn($v) => $v->stock !== null);
                $totalStock = $hasVariantStock
                    ? $variantRows->sum(fn($v) => max(0, (int) $v->stock))
                    : (int) ($p->stock ?? 0);

                return [
                    'id'        => (string) $p->id,
                    'name'      => strip_tags($p->name ?? ''),
                    'price'     => (float)  $p->new_price,
                    'old_price' => (float)  $p->old_price,
                    'image'     => asset(optional($p->image)->image ?? 'public/uploads/default.webp'),
                    'stock'     => (int) $totalStock,
                    'sizes'     => array_values($sizeOptions),
                    'colors'    => array_values($colorOptions),
                    'variants'  => $variantRows->map(fn($v) => [
                        's'  => $v->size_id ? (string) $v->size_id : null,
                        'c'  => $v->color_id ? (string) $v->color_id : null,
                        'p'  => (float) $v->price,
                        'st' => $v->stock === null ? null : (int) $v->stock,
                    ])->values(),
                ];
            })->values();
            $_camp_idx      = 0;
            $camp_items_gtm = $products->map(function($p) use (&$_camp_idx) {
                return [
                    'item_id'   => (string) $p->id,
                    'item_name' => strip_tags($p->name ?? ''),
                    'price'     => (float)  $p->new_price,
                    'index'     => $_camp_idx++,
                    'quantity'  => 1,
                ];
            })->values();
        @endphp
        <script>
            window.dataLayer = window.dataLayer || [];
            window._campaignData = {
                id:          {{ json_encode($camp_id) }},
                name:        {{ json_encode($camp_name) }},
                slug:        {{ json_encode($camp_slug) }},
                currency:    'BDT',
                fb_event_id: {{ json_encode($fb_view_content_event_id) }}
            };
            window._campaignProducts = {!! json_encode($camp_products) !!};
            dataLayer.push({
                event:         'campaign_page_loaded',
                page_type:     'campaign_landing',
                campaign_id:   {{ json_encode($camp_id) }},
                campaign_name: {{ json_encode($camp_name) }},
                currency:      'BDT',
                value:         {{ $camp_value }},
                ecommerce: {
                    currency: 'BDT',
                    items:    {!! json_encode($camp_items_gtm) !!}
                }
            });
        </script>
        <!-- ========== Google Tag Manager ========== -->
        @foreach($gtm_code as $gtm)
        @php
            $gtm_container_id = preg_match('/^GTM-/i', trim($gtm->code))
                ? trim($gtm->code)
                : 'GTM-' . trim($gtm->code);
        @endphp
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtm_container_id }}');</script>
        @endforeach
        <!-- ========== End Google Tag Manager ========== -->

        <meta name="app-url" content="{{route('campaign',$campaign_data->slug)}}" />
        <meta name="robots" content="index, follow" />
        <meta name="description" content="{{$campaign_data->description}}" />
        <meta name="keywords" content="{{ $campaign_data->slug }}" />

        <!-- Twitter Card data -->
        <meta name="twitter:card" content="product" />
        <meta name="twitter:site" content="{{$campaign_data->name}}" />
        <meta name="twitter:title" content="{{$campaign_data->name}}" />
        <meta name="twitter:description" content="{{ $campaign_data->description}}" />
        <meta name="twitter:creator" content="{{ $generalsetting->name }}" />
        <meta property="og:url" content="{{route('campaign',$campaign_data->slug)}}" />
        <meta name="twitter:image" content="{{asset($campaign_data->image_one)}}" />

        <!-- Open Graph data -->
        <meta property="og:title" content="{{$campaign_data->name}}" />
        <meta property="og:type" content="product" />
        <meta property="og:url" content="{{route('campaign',$campaign_data->slug)}}" />
        <meta property="og:image" content="{{asset($campaign_data->image_one)}}" />
        <meta property="og:description" content="{{ $campaign_data->description}}" />
        <meta property="og:site_name" content="{{$campaign_data->name}}" />

        <!-- ========== Facebook Pixel (single init) ========== -->
        @if($pixels->count() > 0)
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
            n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}
            (window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
            @foreach($pixels as $pixel)
            fbq('init', '{{{ $pixel->code }}}');
            @endforeach
            fbq('track', 'PageView', {}, {eventID: {{ json_encode('pv_camp'.$campaign_data->id.'_'.time()) }}});
            fbq('track', 'ViewContent', {
                content_name: {{ json_encode($camp_name) }},
                content_ids:  {!! json_encode($products->pluck('id')->map(fn($id) => (string)$id)->values()->toArray()) !!},
                content_type: 'product',
                value:        {{ $camp_value }},
                currency:     'BDT',
                num_items:    {{ $products->count() }}
            }, {eventID: {{ json_encode($fb_view_content_event_id) }}});
        </script>
        @foreach($pixels as $pixel)
        <noscript>
            <img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{{ $pixel->code }}}&ev=PageView&noscript=1" />
        </noscript>
        @endforeach
        @endif
        <!-- ========== End Facebook Pixel ========== -->

        <!-- ========== TikTok Pixel ========== -->
        @if($tiktok_pixels->count() > 0)
        <script>
            !function (w, d, t) {
                w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
                ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];
                ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
                for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
                ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};
                ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";
                    ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};
                    var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;
                    var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
            }(window, document, 'ttq');
            @foreach($tiktok_pixels as $tiktok)
            ttq.load('{{ $tiktok->code }}');
            @endforeach
            ttq.page();
            ttq.track('ViewContent', {
                content_name: {{ json_encode($camp_name) }},
                content_id:   {{ json_encode($camp_id) }},
                content_type: 'product',
                value:        {{ $camp_value }},
                currency:     'BDT',
                quantity:     1
            });
        </script>
        @endif
        <!-- ========== End TikTok Pixel ========== -->
        <style>
            /* Style for selected product card */
            .selected {
                border: 2px solid green; /* Change border color to green */
            }
            .countdown-container {
                text-align: center;
            }
            .counter-card {
                border: 2px dotted white; /* Dotted border */
                border-radius: 15px; /* Rounded corners */
                padding: 5px; /* Padding for the card */
                background-color: transparent; /* Slightly transparent white background */
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
                text-align: center; /* Center the text within each card */
               
            }
            .counter-card div{
                font-size: 1.2em;
                font-weight:bolder;
                color:white;
            }
            
            
            .counter-card span {
                display: block; /* Make the span block-level for better spacing */
                font-size: 0.8em; /* Font size for labels */
                color:orange;
            }
            @keyframes colorAnimation {
                0% {
                    color: pink; /* Start with pink */
                }
                33% {
                    color: green; /* Transition to green */
                }
                66% {
                    color: red; /* Transition to red */
                }
                100% {
                    color: pink; /* Return to pink */
                }
            }
            
            .animated-heading {
                font-size: 2em; /* Adjust font size as needed */
                font-weight: bold; /* Make the heading bold */
                animation: colorAnimation 3s linear infinite; /* Apply the animation */
                
               
            }
            .form_inn{
                padding:10px;
            }
            @media (max-width: 992px) {
                .campro_inn,.cont_inner,.cont_num ,.discount_inn{
                    padding: 10px!important; /* Add 10px padding for tablet and smaller devices */
                    width: 100%;
                }
                .discount_inn{
                    margin:10px 0 0 0;
                }
                .campro_inn h2{
                    font-size:20px;
                }
            }

        </style>
        <style>
            .button-3d {
                position: relative;
                overflow: hidden;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
        
           
            
        
            .button-3d:hover {
                transform: scale(1.05);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            }
        
           
        
        </style>
        <style>
            .button-animated-border {
                position: relative;
                overflow: hidden;
                border: 3px solid white; /* Initial border */
                border-radius: 10px; /* Optional: for rounded corners */
                transition: color 0.3s ease; /* Transition for text color */
                animation: border-animation 3s linear infinite; /* Animation */
            }
        
            
        
            @keyframes border-animation {
                0% {
                    border-color: white; /* Transparent at start */
                    transform: scale(0.95); /* Initial scale */
                }
                25% {
                    border-color: yellow; /* Fill with white */
                    transform: scale(1); /* Slightly grow */
                }
                50% {
                    border-color: white; /* Transparent in middle */
                    transform: scale(0.95); /* Back to original scale */
                }
                75% {
                    border-color: yellow; /* Fill with white again */
                    transform: scale(1); /* Slightly grow again */
                }
                100% {
                    border-color: white; /* Transparent at end */
                    transform: scale(0.95); /* Back to original scale */
                }
            }
        
            .button-animated-border:hover {
                color: #fff; /* Change text color on hover */
            }
        </style>

{!! $generalsetting->header_code !!}
    </head>

    <body>
        <!-- ========== GTM noscript ========== -->
        @foreach($gtm_code as $gtm)
        @php $gtm_noscript_id = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-'.trim($gtm->code); @endphp
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm_noscript_id }}"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        @endforeach
        <!-- ========== TikTok Pixel noscript ========== -->
        @if($tiktok_pixels->count() > 0)
        @foreach($tiktok_pixels as $tiktok)
        <noscript><img height="1" width="1" style="display:none" alt=""
            src="https://analytics.tiktok.com/i18n/pixel/events.js?sdkid={{ $tiktok->code }}&noscript=1" /></noscript>
        @endforeach
        @endif

         @php
            $subtotal = Cart::instance('shopping')->subtotal();
            $subtotal=str_replace(',','',$subtotal);
            $subtotal=str_replace('.00', '',$subtotal);
            $shipping = Session::get('shipping')?Session::get('shipping'):0;
        @endphp
        <section style="background-image: radial-gradient(at center center, #139525 28%, #0E320F 79%)">
            <div class="container py-2 py-md-4">
                <div class="row gy-2">
                    <div class="col-md-7">
                        <h4 class="text-light text-center py-2 py-md-4 fw-bolder">{!! $campaign_data->top_title_1  !!} <span class="text-warning"> {!! $campaign_data->top_title_2  !!}</span> </h4>
                    </div>
                     <div class="col-md-5">
                        <div class="countdown-container">
                            <div class="countdown" id="countdown">
                                <div class="row g-1">
                                    <div class="col-3">
                                       <div class="counter-card">
                                            <div id="days"></div>
                                            <span>Days</span>
                                        </div> 
                                    </div>
                                    <div class="col-3">
                                        <div class="counter-card">
                                            <div id="hours"></div>
                                            <span>Hours</span>
                                        </div>                                        
                                    </div>
                                    <div class="col-3">
                                        <div class="counter-card">
                                            <div id="minutes"></div>
                                            <span>Minutes</span>
                                        </div>                                    
                                    </div>
                                    <div class="col-3">
                                        <div class="counter-card">
                                            <div id="seconds"></div>
                                            <span>Seconds</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section>
            <div class="container py-2 py-md-4">
                <div class="py-2 py-md-4  rounded" style="border:2px dashed green">
                    <h2 class="animated-heading text-center">{!! $campaign_data->heading_1 !!}</h2>
                </div>
            </div>
        </section>
        <section>
            <div class="container py-2 py-md-4">
                <div class="row gy-2">
                    @if($campaign_data->image_one)
                    <div class="col-sm-6">
                        <img class="img-fluid shadow" src="{{asset($campaign_data->image_one)}}" >
                    </div>
                    @endif
                    @if($campaign_data->image_two)
                    <div class="col-sm-6">
                        <img class="img-fluid shadow" src="{{asset($campaign_data->image_two)}}" >
                    </div>
                    @endif
                </div>
            </div>
        </section>
        <section>
            <div class="container py-2 py-md-4">
                <div class="row gy-2">
                    @if($campaign_data->feature_1)
                    <div class="col-sm-6">
                       <div class="py-2 py-md-4  rounded" style="border:1px dashed green">
                            <h2 class="text-center">{!! $campaign_data->feature_1 !!}</h2>
                        </div>
                    </div>
                    @endif
                    @if($campaign_data->feature_2)
                    <div class="col-sm-6">
                       <div class="py-2 py-md-4  rounded" style="border:1px dashed green">
                            <h2 class="text-center">{!! $campaign_data->feature_2 !!}</h2>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </section>
        <section>
            <div class="container py-2">
                <div class="py-2 py-md-4  rounded" style="border:2px dashed green">
                    <h2 class="animated-heading text-center">{!! $campaign_data->heading_2 !!}</h2>
                </div>
            </div>
        </section>
        <section>
            <div class="container py-2 ">
                <div class="py-2 py-md-4  rounded" style="border:2px dashed green">
                    <h2 class="animated-heading text-center">{!! $campaign_data->heading_3 !!}</h2>
                </div>
            </div>
        </section>
        {{--
        <section style="background: url('{{asset($campaign_data->banner)}}'); background-repeat: no-repeat; background-size:cover; background-position: center;" >
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="campaign_image">
                            <div class="campaign_item">
                                <div class="banner_t">
                                    <h2>{{$campaign_data->banner_title}}</h2>
                                    
                                    <a href="#order_form" class="cam_order_now" id="cam_order_now"><i class="fa-solid fa-cart-shopping"></i> অর্ডার করুন </a>
                                    <p class="megaoffer_btn">মেগা অফার {{$subtotal}} Tk টাকা</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        --}}
        @if($campaign_data->video!=null)
        <section class="camp_video_sec">
            <div class="container">
            
                <div class="row justify-content-center gy-2 gy-md-4">
                    <div class="col-md-8">
                        <h2 class="p-2 py-md-3 rounded text-center" style="background-color:black;border:green 2px solid;color:white;font-weight:bolder">প্রডাক্টের "ভিডিও দেখুন"</h2>
                    </div>
                    <div class="col-md-8 col-sm-12">
                        <div class="camp_vid rounded" style="border:5px solid red">
                            <iframe width="100%" height="480" 
                            src="https://www.youtube.com/embed/{{$campaign_data->video}}" 
                            title="{{$campaign_data->banner_title}}" frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen=""></iframe>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="ord_btn">
                            <a href="#order_form" class="cam_order_now" id="cam_order_now"> অর্ডার করতে ক্লিক করুন <i class="fa-solid fa-hand-point-right"></i> </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @endif
        
        <section class="py-2 py-md-4" style="background: linear-gradient(to bottom, #FAF4B3, #ECC7CF);">
            <div class="container my-2 my-md-4">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <h2 class="text-center p-2 p-md-4 rounded" style="background-color:#FBEFF7;border:2px dashed #F1ACE7">আমাদের থেকে বিস্তারিত জানতে এই নাম্বারে কল করুন {{ optional($contact)->phone }}</h2>
                        <div class="row justify-content-center my-2 my-md-4 gy-2">
                            <div class="col-md-6 custom_btn">
                                <div class="shadow-lg">
                                    <a href="tel:{{ optional($contact)->phone }}" 
                                    class="btn btn-danger btn-lg d-block py-md-3 fs-2 fw-bolder button-3d button-animated-border" >
                                        <i class="fa-solid fa-phone"></i> আমাদের কল করুন </a>
                                </div>
                                
                            </div>
                            <div class="col-md-6">
                            <div class="shadow-lg">
                                <a href="https://wa.me/{{ preg_replace('/\D+/', '', optional($contact)->whatsapp ?? '') }}" 
                                class="btn btn-success btn-lg d-block py-md-3 fs-2 text-light fw-bolder button-3d button-animated-border">
                                    <i class="fa-brands fa-whatsapp"></i> হোয়াটসঅ্যাপ  
                                    </a>
                             </div>
                                
                            </div>
                        </div>
                        
                        <h2 class="text-center p-2 p-md-4 rounded" style="background-color:#FBEFF7;border:2px dashed #F1ACE7">{!! $campaign_data->heading_4 !!}</h2>
                    
                    </div>
                </div>
            </div>
        </section>

        @if(optional($campaign_data)->short_description && strlen($campaign_data->short_description) > 15 || 
    optional($campaign_data)->description && strlen($campaign_data->description) > 15)
        <section class="rules_sec">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-body">
                                <h2>বিস্তারিত</h2>
                                {!! $campaign_data->short_description !!}
                                <br>
                                <br>
                                {!!$campaign_data->description !!} 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @endif
        <section>
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="campro_inn">
                            <div class="campro_head">
                                <h2>{{$campaign_data->name}}</h2>
                            </div>

                            <div class="campro_img_slider owl-carousel">
                                @if($campaign_data->image_one)
                               <div class="campro_img_item">
                                   <img src="{{asset($campaign_data->image_one)}}" alt="">
                               </div> 
                               @endif
                                @if($campaign_data->image_two)
                               <div class="campro_img_item">
                                   <img src="{{asset($campaign_data->image_two)}}" alt="">
                               </div> 
                               @endif
                                @if($campaign_data->image_three)
                               <div class="campro_img_item">
                                   <img src="{{asset($campaign_data->image_three)}}" alt="">
                               </div>
                               @endif
                            </div>
                            <div class="col-sm-12">
                                <div class="ord_btn">
                                    <a href="#order_form" class="cam_order_now" id="cam_order_now"> অর্ডার করতে ক্লিক করুন <i class="fa-solid fa-hand-point-right"></i> </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>


        <section>
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="rev_inn">
                            
                            <h2 class="campaign_offer">{{$campaign_data->review}}</h2>
                            
                            <div class="review_slider owl-carousel">
                            @foreach($campaign_data->images as $key=>$value)
                            <div class="review_item">
                                <img src="{{asset($value->image)}}" alt="">
                            </div>
                            @endforeach
                           </div>
                            <div class="col-sm-12">
                                <div class="ord_btn">
                                    <a href="#order_form" class="cam_order_now" id="cam_order_now"> অর্ডার করতে ক্লিক করুন <i class="fa-solid fa-hand-point-right"></i> </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <style>
        /* ===== High-converting checkout redesign ===== */
        .cmp-checkout-sec{background:linear-gradient(180deg,#f5f7fb 0%,#eef1f7 100%);padding:26px 0 60px;}
        .cmp-checkout-head{text-align:center;margin-bottom:18px;}
        .cmp-checkout-head h2{font-size:clamp(19px,4.5vw,28px);font-weight:800;color:#15803d;margin:0 0 6px;}
        .cmp-checkout-head p{color:#64748b;font-size:14px;margin:0;}
        .cmp-order-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);gap:18px;align-items:start;}
        @media(max-width:991px){.cmp-order-grid{grid-template-columns:1fr;}}
        .cmp-card{background:#fff;border:1px solid #e6eaf1;border-radius:14px;box-shadow:0 6px 22px rgba(15,23,42,.06);overflow:hidden;}
        .cmp-card-head{display:flex;align-items:center;gap:10px;padding:13px 16px;border-bottom:1px solid #eef1f6;background:#fbfcfe;}
        .cmp-card-head .step{width:30px;height:30px;border-radius:50%;background:#15803d;color:#fff;display:grid;place-items:center;font-weight:800;font-size:14px;flex:0 0 30px;}
        .cmp-card-head strong{font-size:15.5px;color:#0f172a;display:block;line-height:1.25;}
        .cmp-card-head small{color:#64748b;font-size:12px;}
        .cmp-card-body{padding:14px 16px;}
        /* product select cards */
        .cmp-products{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;}
        .cmp-product{position:relative;border:2px solid #e6eaf1;border-radius:12px;overflow:hidden;cursor:pointer;background:#fff;transition:.2s;text-align:center;padding:0;}
        .cmp-product:hover{border-color:#16a34a;transform:translateY(-2px);box-shadow:0 8px 20px rgba(22,163,74,.15);}
        .cmp-product.is-selected{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.18);}
        .cmp-product.is-selected::after{content:'✓';position:absolute;top:6px;right:6px;width:24px;height:24px;background:#16a34a;color:#fff;border-radius:50%;display:grid;place-items:center;font-weight:800;font-size:13px;z-index:2;}
        .cmp-product img{width:100%;aspect-ratio:1/1;object-fit:cover;display:block;}
        .cmp-product .info{padding:8px 6px 10px;}
        .cmp-product .nm{font-size:12.5px;font-weight:700;color:#0f172a;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:32px;}
        .cmp-product .pr{font-size:14px;font-weight:800;color:#dc2626;margin-top:3px;}
        .cmp-product .pr del{color:#94a3b8;font-weight:500;font-size:11.5px;margin-left:4px;}
        .cmp-product .badge-off{position:absolute;top:6px;left:6px;background:#dc2626;color:#fff;font-size:10.5px;font-weight:800;padding:2px 7px;border-radius:20px;z-index:2;}
        .cmp-product .stock-out-cover{position:absolute;inset:0;background:rgba(255,255,255,.75);display:grid;place-items:center;font-weight:800;color:#dc2626;font-size:13.5px;z-index:3;}
        /* selected variant chip line */
        .cmp-selected-variant{display:none;align-items:center;gap:8px;background:#f0fdf4;border:1px dashed #86efac;border-radius:10px;padding:9px 12px;margin-top:10px;font-size:13px;font-weight:700;color:#166534;flex-wrap:wrap;}
        .cmp-selected-variant.on{display:flex;}
        .cmp-selected-variant button{border:0;background:#16a34a;color:#fff;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;cursor:pointer;}
        /* cart table */
        .cmp-cartlist table{margin:0;}
        .cmp-cartlist .cart_table th{background:#f8fafc;font-size:13px;}
        .cmp-cartlist .cart_table td{vertical-align:middle;font-size:13.5px;}
        .cmp-cartlist .quantity{display:inline-flex;align-items:center;border:1.5px solid #e2e8f0;border-radius:8px;overflow:hidden;}
        .cmp-cartlist .quantity button{width:30px;height:32px;border:0;background:#f8fafc;font-weight:800;font-size:15px;cursor:pointer;color:#15803d;}
        .cmp-cartlist .quantity button:hover{background:#15803d;color:#fff;}
        .cmp-cartlist .quantity input{width:38px;height:32px;border:0;text-align:center;font-weight:700;font-size:13.5px;}
        /* form */
        .cmp-form-field{margin-bottom:13px;}
        .cmp-form-field label{display:block;font-size:13.5px;font-weight:700;color:#334155;margin-bottom:5px;}
        .cmp-form-field label span{color:#dc2626;}
        .cmp-form-field input,.cmp-form-field select,.cmp-form-field textarea{width:100%;border:1.5px solid #dbe1ea;border-radius:10px;padding:11px 13px;font-size:14.5px;background:#fff;outline:none;transition:.18s;}
        .cmp-form-field input:focus,.cmp-form-field select:focus,.cmp-form-field textarea:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.12);}
        .cmp-form-field .invalid-feedback{display:block;font-size:12px;}
        .cmp-form-field input.is-invalid,.cmp-form-field select.is-invalid{border-color:#dc2626;}
        .cmp-submit{width:100%;border:0;cursor:pointer;background:linear-gradient(90deg,#16a34a,#15803d);color:#fff;font-size:17px;font-weight:800;padding:15px 12px;border-radius:12px;box-shadow:0 10px 24px rgba(22,163,74,.35);transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px;animation:cmp-pulse 2s infinite;}
        .cmp-submit:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(22,163,74,.45);}
        .cmp-submit:disabled{opacity:.65;cursor:not-allowed;animation:none;transform:none;}
        @keyframes cmp-pulse{0%,100%{box-shadow:0 10px 24px rgba(22,163,74,.35);}50%{box-shadow:0 10px 34px rgba(22,163,74,.6);}}
        .cmp-trust{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:13px;padding-top:12px;border-top:1px solid #eef1f6;}
        .cmp-trust div{text-align:center;font-size:11.5px;color:#64748b;font-weight:600;line-height:1.35;}
        .cmp-trust i{display:block;font-style:normal;font-size:18px;margin-bottom:2px;}
        /* sticky mobile CTA */
        .cmp-sticky-cta{position:fixed;left:0;right:0;bottom:0;z-index:9990;background:linear-gradient(90deg,#16a34a,#15803d);color:#fff;border:0;width:100%;padding:14px 10px;font-size:16.5px;font-weight:800;display:none;align-items:center;justify-content:center;gap:8px;box-shadow:0 -6px 20px rgba(0,0,0,.18);}
        @media(max-width:767px){.cmp-sticky-cta.on{display:flex;}}
        /* ===== Variant popup (storefront style) ===== */
        .cmp-modal{position:fixed;inset:0;z-index:99998;display:none;font-family:inherit;}
        .cmp-modal.on{display:block;}
        .cmp-modal-bg{position:absolute;inset:0;background:rgba(15,23,42,.62);backdrop-filter:blur(2px);}
        .cmp-modal-box{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:min(680px,94vw);max-height:92vh;overflow:auto;background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.3);animation:cmp-up .28s cubic-bezier(.2,.8,.2,1);}
        @keyframes cmp-up{from{opacity:0;transform:translate(-50%,-46%);}to{opacity:1;transform:translate(-50%,-50%);}}
        @media(max-width:640px){.cmp-modal-box{top:auto;bottom:0;left:0;transform:none;width:100%;max-height:94vh;border-radius:18px 18px 0 0;animation:cmp-sheet .3s cubic-bezier(.2,.8,.2,1);}@keyframes cmp-sheet{from{transform:translateY(100%);}to{transform:translateY(0);}}}
        .cmp-modal-head{position:sticky;top:0;background:#fff;z-index:3;display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid #eef1f6;}
        .cmp-modal-head h5{margin:0;font-size:15px;font-weight:800;color:#15803d;}
        .cmp-modal-x{border:0;background:#f1f5f9;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:15px;line-height:1;}
        .cmp-modal-x:hover{background:#dc2626;color:#fff;}
        .cmp-modal-body{display:grid;grid-template-columns:minmax(0,190px) minmax(0,1fr);gap:16px;padding:16px;}
        @media(max-width:640px){.cmp-modal-body{grid-template-columns:1fr;gap:12px;padding:13px;}.cmp-modal-img{max-width:170px;margin:0 auto;}}
        .cmp-modal-img{border-radius:12px;overflow:hidden;border:1px solid #eef1f6;background:#f8fafc;}
        .cmp-modal-img img{width:100%;aspect-ratio:1/1;object-fit:cover;display:block;}
        .cmp-modal-name{font-size:15.5px;font-weight:700;color:#0f172a;margin:0 0 7px;line-height:1.35;}
        .cmp-modal-price{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin-bottom:4px;}
        .cmp-modal-price b{font-size:23px;font-weight:800;color:#dc2626;}
        .cmp-modal-price del{color:#94a3b8;font-size:14px;}
        .cmp-modal-save{background:#e8f7ee;color:#12a150;font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:20px;}
        .cmp-modal-stock{font-size:12.5px;font-weight:700;margin-bottom:11px;}
        .cmp-lbl{font-size:13px;font-weight:800;margin:0 0 6px;color:#0f172a;}
        .cmp-lbl em{font-style:normal;color:#dc2626;}
        .cmp-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:13px;}
        .cmp-chips.cmp-err .cmp-chip{border-color:#dc2626;}
        .cmp-chip{border:1.5px solid #dbe1ea;background:#fff;min-width:46px;padding:8px 14px;border-radius:9px;font-size:13.5px;font-weight:700;cursor:pointer;transition:.18s;text-align:center;}
        .cmp-chip:hover{border-color:#16a34a;}
        .cmp-chip.on{border-color:#16a34a;background:#16a34a;color:#fff;box-shadow:0 4px 12px rgba(22,163,74,.3);}
        .cmp-chip.off{opacity:.4;cursor:not-allowed;text-decoration:line-through;background:#f5f6f8;}
        .cmp-chip.off:hover{border-color:#dbe1ea;}
        .cmp-chip small{display:block;font-size:10px;font-weight:600;color:#12a150;margin-top:2px;}
        .cmp-chip.on small{color:#d1fae5;}
        .cmp-chip.off small{color:#dc2626;}
        .cmp-chip .dot{display:inline-block;width:13px;height:13px;border-radius:50%;border:1px solid rgba(0,0,0,.15);margin-right:6px;vertical-align:-2px;}
        .cmp-qty{display:inline-flex;align-items:center;border:1.5px solid #dbe1ea;border-radius:9px;overflow:hidden;margin-bottom:13px;}
        .cmp-qty button{width:40px;height:40px;border:0;background:#f8fafc;font-size:18px;cursor:pointer;color:#15803d;font-weight:800;}
        .cmp-qty button:hover{background:#15803d;color:#fff;}
        .cmp-qty input{width:52px;height:40px;border:0;text-align:center;font-size:15px;font-weight:800;outline:none;}
        .cmp-modal-total{display:flex;justify-content:space-between;align-items:center;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px;padding:10px 14px;margin-bottom:13px;}
        .cmp-modal-total span{font-size:13px;font-weight:700;color:#64748b;}
        .cmp-modal-total b{font-size:20px;font-weight:800;color:#15803d;}
        .cmp-modal-confirm{width:100%;border:0;cursor:pointer;background:linear-gradient(90deg,#dc2626,#f97316);color:#fff;font-size:16px;font-weight:800;padding:14px 10px;border-radius:11px;box-shadow:0 8px 20px rgba(220,38,38,.3);transition:.2s;}
        .cmp-modal-confirm:hover{transform:translateY(-2px);}
        .cmp-modal-confirm:disabled{opacity:.6;cursor:not-allowed;transform:none;}
        .cmp-shake{animation:cmp-sh .4s;}
        @keyframes cmp-sh{0%,100%{transform:translateX(0);}25%{transform:translateX(-6px);}75%{transform:translateX(6px);}}
        .cmp-toast{position:fixed;top:18px;left:50%;transform:translateX(-50%);z-index:100000;background:#12a150;color:#fff;padding:11px 22px;border-radius:30px;font-size:13.5px;font-weight:700;box-shadow:0 10px 30px rgba(0,0,0,.22);display:none;white-space:nowrap;}
        .cmp-toast.err{background:#dc2626;}
        .cmp-toast.on{display:block;}
        .cmp-busy{position:fixed;inset:0;z-index:99997;background:rgba(255,255,255,.55);display:none;place-items:center;}
        .cmp-busy.on{display:grid;}
        .cmp-busy span{width:44px;height:44px;border:4px solid #16a34a;border-top-color:transparent;border-radius:50%;animation:cmp-spin .8s linear infinite;}
        @keyframes cmp-spin{to{transform:rotate(360deg);}}
    </style>

    <section class="cmp-checkout-sec form_sec" id="order_form">
        <div class="container">
            <div class="cmp-checkout-head">
                <h2>অর্ডার করতে নিচের ফর্মটি পূরণ করুন</h2>
                <p>অফারটি সীমিত সময়ের জন্য — স্টক শেষ হওয়ার আগেই অর্ডার করুন ✅ ক্যাশ অন ডেলিভারি</p>
                @if($campaign_data->note)
                    <p class="my-1">{!! $campaign_data->note !!}</p>
                @endif
            </div>

            <div class="cmp-order-grid">
                {{-- ===== Left: product select + cart summary ===== --}}
                <div>
                    <div class="cmp-card mb-3">
                        <div class="cmp-card-head">
                            <span class="step">১</span>
                            <div><strong>আপনার পণ্য {{ $products->count() > 1 ? 'সিলেক্ট' : 'কনফার্ম' }} করুন</strong><small>ছবিতে ক্লিক করে সাইজ/কালার বেছে নিন</small></div>
                        </div>
                        <div class="cmp-card-body">
                            <div class="cmp-products">
                                @foreach($products as $product)
                                    @php
                                        $vRows = $product->variantPrices ?? collect();
                                        $hasVStock = $vRows->contains(fn($v) => $v->stock !== null);
                                        $pStock = $hasVStock ? $vRows->sum(fn($v) => max(0,(int)$v->stock)) : (int)($product->stock ?? 0);
                                        $off = ((float)$product->old_price > (float)$product->new_price && (float)$product->old_price > 0)
                                            ? round((($product->old_price - $product->new_price)/$product->old_price)*100) : 0;
                                    @endphp
                                    <button type="button" class="cmp-product {{ $loop->first ? 'is-selected' : '' }}" data-cmp-product="{{ $product->id }}" {{ $pStock <= 0 ? 'disabled' : '' }}>
                                        @if($off > 0)<span class="badge-off">-{{ $off }}%</span>@endif
                                        <img src="{{ asset(optional($product->image)->image ?? 'public/uploads/default.webp') }}" alt="{{ $product->name }}" loading="lazy">
                                        <span class="info">
                                            <span class="nm">{{ Str::limit($product->name, 40) }}</span>
                                            <span class="pr">৳{{ number_format((float)$product->new_price,0) }} @if((float)$product->old_price > (float)$product->new_price)<del>৳{{ number_format((float)$product->old_price,0) }}</del>@endif</span>
                                        </span>
                                        @if($pStock <= 0)<span class="stock-out-cover">স্টক আউট</span>@endif
                                    </button>
                                @endforeach
                            </div>
                            <div class="cmp-selected-variant" id="cmpSelectedVariant">
                                <span id="cmpSelectedVariantText"></span>
                                <button type="button" id="cmpChangeVariant">পরিবর্তন করুন</button>
                            </div>
                        </div>
                    </div>

                    <div class="cmp-card">
                        <div class="cmp-card-head">
                            <span class="step">২</span>
                            <div><strong>আপনার অর্ডার</strong><small>পরিমাণ ও মোট মূল্য যাচাই করুন</small></div>
                        </div>
                        <div class="cmp-card-body cartlist cmp-cartlist table-responsive">
                            @include('frontEnd.layouts.ajax.campaign-cart')
                        </div>
                    </div>
                </div>

                {{-- ===== Right: checkout form ===== --}}
                <div>
                    <div class="cmp-card">
                        <div class="cmp-card-head">
                            <span class="step">৩</span>
                            <div><strong>ডেলিভারি তথ্য দিন</strong><small>সঠিক তথ্য দিলে দ্রুত ডেলিভারি পাবেন</small></div>
                        </div>
                        <div class="cmp-card-body">
                            @if($errors->any())
                                <div class="alert alert-danger py-2" role="alert" style="font-size:13px;">
                                    <strong>তথ্যগুলো আবার যাচাই করুন:</strong>
                                    <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                                </div>
                            @endif
                            <form action="{{ route('customer.ordersave') }}" method="POST" id="cmpOrderForm">
                                @csrf
                                <input type="hidden" name="payment_method" value="cod">
                                <div class="cmp-form-field">
                                    <label for="cmp-name">আপনার নাম <span>*</span></label>
                                    <input type="text" id="cmp-name" name="name" value="{{ old('name') }}" placeholder="আপনার সম্পূর্ণ নাম" autocomplete="name" class="@error('name') is-invalid @enderror" required>
                                    @error('name')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                                <div class="cmp-form-field">
                                    <label for="cmp-phone">মোবাইল নম্বর <span>*</span></label>
                                    <input type="tel" id="cmp-phone" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" inputmode="numeric" pattern="01[0-9]{9}" maxlength="11" autocomplete="tel" title="১১ ডিজিটের মোবাইল নম্বর দিন (01 দিয়ে শুরু)" class="@error('phone') is-invalid @enderror" required>
                                    @error('phone')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                                <div class="cmp-form-field">
                                    <label for="cmp-address">সম্পূর্ণ ঠিকানা <span>*</span></label>
                                    <input type="text" id="cmp-address" name="address" value="{{ old('address') }}" placeholder="জেলা, থানা, এলাকা/গ্রাম, বাড়ির ঠিকানা" autocomplete="street-address" class="@error('address') is-invalid @enderror" required>
                                    @error('address')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                                <div class="cmp-form-field">
                                    <label for="area">ডেলিভারি এরিয়া <span>*</span></label>
                                    <select id="area" name="area" class="@error('area') is-invalid @enderror" required>
                                        @foreach($shippingcharge as $key=>$value)
                                            <option value="{{ $value->id }}" {{ (string) old('area') === (string) $value->id ? 'selected' : '' }}>{{ $value->name }} — ৳{{ number_format((float) $value->amount, 0) }}</option>
                                        @endforeach
                                    </select>
                                    @error('area')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                                <button class="cmp-submit order_place" type="submit" id="cmpSubmitBtn">🛒 অর্ডার কনফার্ম করুন</button>
                                <div class="cmp-trust">
                                    <div><i>🚚</i> ক্যাশ অন ডেলিভারি</div>
                                    <div><i>🔄</i> সহজ রিটার্ন</div>
                                    <div><i>✅</i> ১০০% অরিজিনাল</div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @if($campaign_data->billing_details)
                        <p class="my-2 text-center" style="font-size:13px;color:#64748b;">{!! $campaign_data->billing_details !!}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ===== Sticky mobile CTA ===== --}}
    <button type="button" class="cmp-sticky-cta on" id="cmpStickyCta">🛒 এখনই অর্ডার করুন — ক্যাশ অন ডেলিভারি</button>

    {{-- ===== Size/Color popup (storefront style) ===== --}}
    <div class="cmp-modal" id="cmpModal" aria-hidden="true">
        <div class="cmp-modal-bg" onclick="cmpClose()"></div>
        <div class="cmp-modal-box">
            <div class="cmp-modal-head">
                <h5>🛒 সাইজ ও কালার বেছে নিন</h5>
                <button type="button" class="cmp-modal-x" onclick="cmpClose()" aria-label="Close">✕</button>
            </div>
            <div class="cmp-modal-body">
                <div class="cmp-modal-img"><img id="cmpMoImg" src="" alt="Product"></div>
                <div>
                    <h4 class="cmp-modal-name" id="cmpMoName"></h4>
                    <div class="cmp-modal-price">
                        <b id="cmpMoPrice"></b>
                        <del id="cmpMoOld"></del>
                        <span class="cmp-modal-save" id="cmpMoSave"></span>
                    </div>
                    <div class="cmp-modal-stock" id="cmpMoStock"></div>
                    <div id="cmpSizeWrap" style="display:none">
                        <p class="cmp-lbl">সাইজ সিলেক্ট করুন <em>*</em></p>
                        <div class="cmp-chips" id="cmpSizes"></div>
                    </div>
                    <div id="cmpColorWrap" style="display:none">
                        <p class="cmp-lbl">কালার সিলেক্ট করুন <em>*</em></p>
                        <div class="cmp-chips" id="cmpColors"></div>
                    </div>
                    <p class="cmp-lbl">পরিমাণ</p>
                    <div class="cmp-qty">
                        <button type="button" onclick="cmpQty(-1)">−</button>
                        <input type="text" id="cmpQtyBox" value="1" readonly>
                        <button type="button" onclick="cmpQty(1)">+</button>
                    </div>
                    <div class="cmp-modal-total"><span>সর্বমোট</span><b id="cmpMoTotal">৳ 0</b></div>
                    <button type="button" class="cmp-modal-confirm" id="cmpMoConfirm">✓ কনফার্ম করুন — চেকআউটে যোগ হবে</button>
                </div>
            </div>
        </div>
    </div>

    <div class="cmp-toast" id="cmpToast" role="status"></div>
    <div class="cmp-busy" id="cmpBusy"><span></span></div>

        <script src="{{ asset('public/frontEnd/campaign/js') }}/jquery-2.1.4.min.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/all.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/bootstrap.min.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/owl.carousel.min.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/select2.min.js"></script>
        <script src="{{ asset('public/frontEnd/campaign/js') }}/script.js"></script>
        <!-- bootstrap js -->
        <script>
            $(document).ready(function () {
                $(".campro_img_slider, .review_slider").addClass('owl-ready');
            });
        </script>
        <script>
            $(document).ready(function() {
                $('.select2').select2();
            });
        </script>
        <script>
             $(document).on("change", "#area", function () {
                var id = $(this).val();
                $.ajax({
                    type: "GET",
                    data: { id: id, campaign: 1 },
                    url: "{{route('shipping.charge')}}",
                    dataType: "html",
                    success: function(response){
                        $('.cartlist').html(response);
                    }
                });
            });
        </script>
           <script>
            function campaignCartRequest(url, extra) {
                var payload = $.extend({ campaign: 1 }, extra || {});
                $.ajax({
                    type: "GET",
                    data: payload,
                    url: url,
                    success: function (data) {
                        if (data) {
                            $(".cartlist").html(data);
                        }
                    },
                    error: function (xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'কার্ট আপডেট করা যায়নি।';
                        alert(message);
                    }
                });
            }
            $(document).on("click", ".cart_remove", function () {
                var id = $(this).data("id");
                if (id) campaignCartRequest("{{route('cart.remove')}}", { id: id });
            });
            $(document).on("click", ".cart_increment", function () {
                var id = $(this).data("id");
                if (id) campaignCartRequest("{{route('cart.increment')}}", { id: id });
            });
            $(document).on("click", ".cart_decrement", function () {
                var id = $(this).data("id");
                if (id) campaignCartRequest("{{route('cart.decrement')}}", { id: id });
            });

        </script>
        <script>
            $('.review_slider').owlCarousel({   
                dots: false,
                arrow: false,
                autoplay: true,
                loop: $('.review_slider .review_item').length > 5,
                margin: 10,
                smartSpeed: 1000,
                mouseDrag: true,
                touchDrag: true,
                items: 6,
                responsiveClass: true,
                responsive: {
                    300: {
                        items: 1,
                    },
                    480: {
                        items: 2,
                    },
                    768: {
                        items: 5,
                    },
                    1170: {
                        items: 5,
                    },
                }
            });
        </script>

        <script>
            $('.campro_img_slider').owlCarousel({   
                dots: false,
                arrow: false,
                autoplay: true,
                loop: $('.campro_img_slider .campro_img_item').length > 3,
                margin: 10,
                smartSpeed: 1000,
                mouseDrag: true,
                touchDrag: true,
                items: 3,
                responsiveClass: true,
                responsive: {
                    300: {
                        items: 1,
                    },
                    480: {
                        items: 2,
                    },
                    768: {
                        items: 3,
                    },
                    1170: {
                        items: 3,
                    },
                }
            });
        </script>

        <script>
            @if($campaign_data->deadline)
            // Set the deadline from the campaign data
            const deadline = new Date("{{ $campaign_data->deadline }}").getTime();
        
            // Update the countdown every 1 second
            const x = setInterval(function() {
                // Get current date and time
                const now = new Date().getTime();
        
                // Calculate the distance between now and the deadline
                const distance = deadline - now;
        
                // Time calculations for days, hours, minutes and seconds
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
                // Display the result in the respective elements
                document.getElementById("days").innerHTML = days;
                document.getElementById("hours").innerHTML = hours;
                document.getElementById("minutes").innerHTML = minutes;
                document.getElementById("seconds").innerHTML = seconds;
        
                // If the countdown is over, write some text
                if (distance < 0) {
                    clearInterval(x);
                    document.getElementById("countdown").innerHTML = "EXPIRED";
                }
            }, 1000);
            @else
            document.getElementById("countdown").style.display = "none";
            @endif
        </script>
        <script>
        /* ============================================================
           Campaign checkout: product select → variant popup → cart
           ============================================================ */
        (function () {
            'use strict';

            var PRODUCTS = window._campaignProducts || [];
            var CHANGE_URL = "{{ route('cart.changeProduct') }}";
            var st = { p: null, size: null, color: null, qty: 1, price: 0, stock: 0 };
            var busyEl = document.getElementById('cmpBusy');
            var toastEl = document.getElementById('cmpToast');
            var modal = document.getElementById('cmpModal');
            var toastTimer = null;

            function $id(x) { return document.getElementById(x); }
            function fmt(n) { return Number(n || 0).toLocaleString('en-US'); }
            function findProduct(id) { return PRODUCTS.find(function (p) { return String(p.id) === String(id); }); }

            function toast(msg, err) {
                clearTimeout(toastTimer);
                toastEl.textContent = msg;
                toastEl.classList.toggle('err', !!err);
                toastEl.classList.add('on');
                toastTimer = setTimeout(function () { toastEl.classList.remove('on'); }, 3200);
            }
            function busy(v) { busyEl.classList.toggle('on', !!v); }

            /* ---------- Tracking ---------- */
            function trackATC(p, price, qty) {
                try {
                    window.dataLayer = window.dataLayer || [];
                    dataLayer.push({ ecommerce: null });
                    dataLayer.push({ event: 'add_to_cart', ecommerce: { currency: 'BDT', value: price * qty, items: [{ item_id: String(p.id), item_name: p.name, price: price, quantity: qty }] } });
                    if (typeof fbq !== 'undefined') {
                        fbq('track', 'AddToCart', { content_ids: [String(p.id)], content_name: p.name, content_type: 'product', value: price * qty, currency: 'BDT' }, { eventID: 'atc_' + p.id + '_' + Math.floor(Date.now() / 1000) });
                    }
                    if (typeof ttq !== 'undefined' && ttq.track) {
                        ttq.track('AddToCart', { content_id: String(p.id), content_name: p.name, content_type: 'product', value: price * qty, currency: 'BDT', quantity: qty });
                    }
                } catch (e) {}
            }

            /* ---------- Popup open ---------- */
            window.cmpOpen = function (productId) {
                var p = findProduct(productId);
                if (!p) return;
                st = { p: p, size: null, color: null, qty: 1, price: p.price, stock: p.stock };

                $id('cmpMoImg').src = p.image || '';
                $id('cmpMoName').textContent = p.name;

                buildChips('cmpSizes', 'cmpSizeWrap', p.sizes || [], 'size');
                buildChips('cmpColors', 'cmpColorWrap', p.colors || [], 'color');

                /* একটাই অপশন থাকলে অটো-সিলেক্ট (কম ক্লিক = বেশি কনভার্শন) */
                if ((p.sizes || []).length === 1) pickChip('size', p.sizes[0].id, 0);
                if ((p.colors || []).length === 1) pickChip('color', p.colors[0].id, 0);

                /* সাইজ/কালার কিছুই না থাকলে পপআপ ছাড়াই সরাসরি কার্টে */
                if (!(p.sizes || []).length && !(p.colors || []).length) {
                    confirmSelection();
                    return;
                }

                sync();
                setQty(1);
                modal.classList.add('on');
                document.body.style.overflow = 'hidden';
            };
            window.cmpClose = function () { modal.classList.remove('on'); document.body.style.overflow = ''; };
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cmpClose(); });

            /* ---------- Chips ---------- */
            function buildChips(boxId, wrapId, list, type) {
                var box = $id(boxId), wrap = $id(wrapId);
                box.innerHTML = '';
                box.classList.remove('cmp-err');
                if (!list.length) { wrap.style.display = 'none'; return; }
                wrap.style.display = '';
                list.forEach(function (o, idx) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'cmp-chip';
                    b.dataset.id = o.id;
                    var stockNote = '';
                    if (type === 'size' && o.has_stock) {
                        stockNote = '<small>' + (o.stock > 0 ? o.stock + ' টি আছে' : 'স্টক শেষ') + '</small>';
                        if (Number(o.stock) <= 0) b.classList.add('off');
                    }
                    b.innerHTML = (type === 'color' && o.hex ? '<span class="dot" style="background:' + o.hex + '"></span>' : '') + o.name + stockNote;
                    b.onclick = function () { if (!b.classList.contains('off')) pickChip(type, o.id, idx); };
                    box.appendChild(b);
                });
            }

            function pickChip(type, id, idx) {
                var box = $id(type === 'size' ? 'cmpSizes' : 'cmpColors');
                [].forEach.call(box.children, function (b, i) { b.classList.toggle('on', i === idx); });
                box.classList.remove('cmp-err');
                st[type] = id;
                sync();
                setQty(st.qty);
            }

            /* ---------- Variant sync: price/stock + availability ---------- */
            function markAvail(boxId, fn) {
                var box = $id(boxId);
                [].forEach.call(box.children, function (b) {
                    var ok = fn(b.dataset.id);
                    b.classList.toggle('off', !ok);
                    if (!ok) b.classList.remove('on');
                });
            }

            function sync() {
                var p = st.p; if (!p) return;
                var vs = p.variants || [];
                if (vs.length) {
                    markAvail('cmpSizes', function (id) {
                        return vs.some(function (v) { return v.s == id && (st.color == null || v.c == null || v.c == st.color) && (v.st === null || v.st > 0); });
                    });
                    markAvail('cmpColors', function (id) {
                        return vs.some(function (v) { return v.c == id && (st.size == null || v.s == null || v.s == st.size) && (v.st === null || v.st > 0); });
                    });

                    var match = vs.filter(function (v) {
                        return (st.size == null || v.s == null || v.s == st.size) &&
                               (st.color == null || v.c == null || v.c == st.color);
                    });
                    var chosen = (!(p.sizes || []).length || st.size != null) && (!(p.colors || []).length || st.color != null);
                    if (chosen && match.length) {
                        if (match[0].p > 0) st.price = match[0].p; else st.price = p.price;
                        var rows = match.filter(function (v) { return v.st !== null; });
                        st.stock = rows.length
                            ? ((st.color != null || rows.length === 1) ? Number(rows[0].st) : rows.reduce(function (s, v) { return s + Number(v.st); }, 0))
                            : p.stock;
                    } else {
                        st.price = p.price;
                        st.stock = p.stock;
                    }
                }

                $id('cmpMoPrice').textContent = '৳ ' + fmt(st.price);
                var oldEl = $id('cmpMoOld'), saveEl = $id('cmpMoSave');
                if (p.old_price && p.old_price > st.price) {
                    oldEl.textContent = '৳ ' + fmt(p.old_price);
                    saveEl.textContent = 'সাশ্রয় ৳ ' + fmt(p.old_price - st.price);
                    oldEl.style.display = ''; saveEl.style.display = '';
                } else { oldEl.style.display = 'none'; saveEl.style.display = 'none'; }

                var stEl = $id('cmpMoStock');
                if (st.stock !== null && st.stock <= 0) { stEl.textContent = '❌ এই ভ্যারিয়েন্টটি স্টকে নেই'; stEl.style.color = '#dc2626'; }
                else if (st.stock > 0 && st.stock <= 20) { stEl.textContent = '🔥 তাড়াতাড়ি করুন! মাত্র ' + st.stock + ' টি বাকি'; stEl.style.color = '#ea580c'; }
                else { stEl.textContent = '✅ স্টকে আছে'; stEl.style.color = '#12a150'; }
            }

            /* ---------- Qty ---------- */
            window.cmpQty = function (d) { setQty(st.qty + d); };
            function setQty(q) {
                var max = (st.stock && st.stock > 0) ? st.stock : 99;
                st.qty = Math.max(1, Math.min(q, max));
                $id('cmpQtyBox').value = st.qty;
                $id('cmpMoTotal').textContent = '৳ ' + fmt(st.price * st.qty);
            }

            /* ---------- Validate + confirm → auto add to checkout cart ---------- */
            function shake(id) {
                var b = $id(id);
                b.classList.add('cmp-err', 'cmp-shake');
                setTimeout(function () { b.classList.remove('cmp-shake'); }, 420);
                b.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }

            function confirmSelection() {
                var p = st.p; if (!p) return;
                if ((p.sizes || []).length && !st.size) { shake('cmpSizes'); return; }
                if ((p.colors || []).length && !st.color) { shake('cmpColors'); return; }
                if (st.stock !== null && st.stock <= 0) { toast('এই ভ্যারিয়েন্টটি স্টকে নেই', 1); return; }

                var btn = $id('cmpMoConfirm');
                btn.disabled = true;
                busy(true);

                $.ajax({
                    type: 'GET',
                    url: CHANGE_URL,
                    data: { id: p.id, campaign: 1, product_size: st.size || '', product_color: st.color || '', qty: st.qty },
                    success: function (html) {
                        $('.cartlist').html(html);
                        markSelectedCard(p.id);
                        showSelectedVariant(p);
                        trackATC(p, st.price, st.qty);
                        cmpClose();
                        toast('✔ কার্টে যোগ হয়েছে — এখন ডেলিভারি তথ্য দিন');
                        var form = document.getElementById('cmpOrderForm');
                        if (form && window.matchMedia('(max-width: 767px)').matches) {
                            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'কার্ট আপডেট করা যায়নি। আবার চেষ্টা করুন।';
                        toast(msg, 1);
                    },
                    complete: function () { btn.disabled = false; busy(false); }
                });
            }
            $id('cmpMoConfirm').addEventListener('click', confirmSelection);

            function markSelectedCard(id) {
                document.querySelectorAll('.cmp-product').forEach(function (c) {
                    c.classList.toggle('is-selected', String(c.dataset.cmpProduct) === String(id));
                });
            }

            function showSelectedVariant(p) {
                var box = $id('cmpSelectedVariant');
                var parts = [];
                if (st.size) {
                    var so = (p.sizes || []).find(function (s) { return String(s.id) === String(st.size); });
                    if (so) parts.push('সাইজ: ' + so.name);
                }
                if (st.color) {
                    var co = (p.colors || []).find(function (c) { return String(c.id) === String(st.color); });
                    if (co) parts.push('কালার: ' + co.name);
                }
                parts.push('পরিমাণ: ' + st.qty + ' টি');
                $id('cmpSelectedVariantText').textContent = '✓ ' + p.name.substring(0, 30) + ' — ' + parts.join(' | ');
                box.classList.add('on');
                box.dataset.productId = p.id;
            }

            /* ---------- Product card click → popup ---------- */
            document.addEventListener('click', function (e) {
                var card = e.target.closest('.cmp-product');
                if (card && !card.disabled) {
                    e.preventDefault();
                    cmpOpen(card.dataset.cmpProduct);
                }
            });
            $id('cmpChangeVariant').addEventListener('click', function () {
                var id = $id('cmpSelectedVariant').dataset.productId;
                if (id) cmpOpen(id);
            });

            /* ---------- Sticky mobile CTA ---------- */
            var sticky = document.getElementById('cmpStickyCta');
            if (sticky) {
                sticky.addEventListener('click', function () {
                    var sec = document.getElementById('order_form');
                    if (sec) sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    if (window.dataLayer) dataLayer.push({ event: 'click_order_now_button', campaign_id: window._campaignData ? window._campaignData.id : '', campaign_name: window._campaignData ? window._campaignData.name : '' });
                });
                var formSec = document.getElementById('order_form');
                if (formSec && 'IntersectionObserver' in window) {
                    new IntersectionObserver(function (entries) {
                        sticky.classList.toggle('on', !entries[0].isIntersecting);
                    }, { threshold: 0.08 }).observe(formSec);
                }
            }

            /* ---------- Order form guard: variant required before submit ---------- */
            var orderForm = document.getElementById('cmpOrderForm');
            if (orderForm) {
                orderForm.addEventListener('submit', function (e) {
                    var first = PRODUCTS[0];
                    var selectedBox = $id('cmpSelectedVariant');
                    var needsVariant = PRODUCTS.some(function (p) {
                        return document.querySelector('.cmp-product.is-selected[data-cmp-product="' + p.id + '"]') &&
                            (((p.sizes || []).length) || ((p.colors || []).length));
                    });
                    if (needsVariant && !selectedBox.classList.contains('on')) {
                        e.preventDefault();
                        var selCard = document.querySelector('.cmp-product.is-selected');
                        toast('অর্ডারের আগে সাইজ/কালার সিলেক্ট করুন', 1);
                        if (selCard) cmpOpen(selCard.dataset.cmpProduct);
                        return false;
                    }
                    var btn = $id('cmpSubmitBtn');
                    if (btn) { btn.disabled = true; btn.textContent = '⏳ অর্ডার প্রসেস হচ্ছে...'; }
                });
            }
        })();
        </script>
        <script>
            // ========== GTM — view_item_list (সব প্রোডাক্ট) ==========
            dataLayer.push({'ecommerce': null});
            dataLayer.push({
                'event': 'view_item_list',
                'ecommerce': {
                    'currency': 'BDT',
                    'items': window._campaignProducts
                        ? window._campaignProducts.map(function(p, i) {
                            return {item_id: p.id, item_name: p.name, price: p.price, index: i, quantity: 1};
                          })
                        : []
                }
            });

            $(document).ready(function() {
                // ========== InitiateCheckout + Lead — Order Form Submit ==========
                $('form[action="{{ route("customer.ordersave") }}"]').on('submit', function() {
                    var subtotalVal   = parseFloat($('#net_total strong').text().replace(/[^0-9.]/g, '')) || 0;
                    var contentIds    = window._campaignProducts ? window._campaignProducts.map(function(p){ return p.id; }) : [];
                    var icEventId     = 'ic_camp{{ $campaign_data->id }}_' + Math.floor(Date.now()/1000);
                    var leadEventId   = 'lead_camp{{ $campaign_data->id }}_' + Math.floor(Date.now()/1000);
                    var campItems     = window._campaignProducts
                        ? window._campaignProducts.map(function(p, i){
                            return {item_id: p.id, item_name: p.name, price: p.price, index: i, quantity: 1};
                          })
                        : [];

                    // GTM — begin_checkout
                    dataLayer.push({'ecommerce': null});
                    dataLayer.push({
                        'event': 'begin_checkout',
                        'ecommerce': {
                            'currency': 'BDT',
                            'value':    subtotalVal,
                            'items':    campItems
                        }
                    });

                    // Facebook Pixel — InitiateCheckout + Lead
                    if (typeof fbq !== 'undefined') {
                        fbq('track', 'InitiateCheckout', {
                            content_ids:  contentIds,
                            content_type: 'product',
                            value:        subtotalVal,
                            currency:     'BDT',
                            num_items:    contentIds.length
                        }, {eventID: icEventId});
                        fbq('track', 'Lead', {
                            value:        subtotalVal,
                            currency:     'BDT',
                            content_name: {{ json_encode($camp_name) }}
                        }, {eventID: leadEventId});
                    }

                    // TikTok Pixel — InitiateCheckout
                    if (typeof ttq !== 'undefined') {
                        ttq.track('InitiateCheckout', {
                            content_ids:  contentIds,
                            content_type: 'product',
                            value:        subtotalVal,
                            currency:     'BDT',
                            quantity:     contentIds.length
                        });
                    }
                });

                // ========== Order Now Button Click ==========
                $('.cam_order_now').on('click', function() {
                    dataLayer.push({
                        event:         'click_order_now_button',
                        campaign_id:   {{ json_encode($camp_id) }},
                        campaign_name: {{ json_encode($camp_name) }}
                    });
                });
            });
        </script>
    </body>
</html>
