{{--
    ======================================================================
    GLOBAL QUICK-ORDER POPUP  (Order Now / Cart বাটনে ক্লিকে পপআপ)
    ----------------------------------------------------------------------
    • সব frontend পেজে master.blade.php এর মাধ্যমে include হয়।
    • হোমপেজের মতো একই CD ডিজাইন স্টাইল (দ্রুত / responsive / converting)।
    • প্রোডাক্ট ডাটা embedded `window.CDP` থাকলে সেটা (হোমপেজ), না থাকলে
      /quick-order/{id} endpoint থেকে fetch — উভয় ক্ষেত্রেই ফাস্ট।
    • Admin → General Settings থেকে সব নিয়ন্ত্রণ (on/off, টাইটেল, বাটন টেক্সট)।
    ======================================================================
--}}
@php
    $qoPrimary    = $generalsetting->primary_color  ?? '#303d6e';
    $qoSecondary  = $generalsetting->secodery_color ?? '#ff0000';
    $qoEnabled    = (int)(($generalsetting->quick_order_popup_enabled ?? 1) == 1);
    $qoTitle      = $generalsetting->quick_order_popup_title ?? '🛒 দ্রুত অর্ডার করুন';
    $qoConfirm    = $generalsetting->quick_order_confirm_text ?? 'অর্ডার কনফার্ম করুন →';
    $qoCartText   = $generalsetting->quick_order_cart_text ?? 'কার্টে রাখুন';
    $qoToast      = $generalsetting->quick_order_cart_toast ?? 'কার্টে যোগ হয়েছে ✔';
    $qoHotline    = optional($contact)->hotline ?? optional($contact)->whatsapp ?? '';
@endphp

<style>
:root{
    --qo-primary   : {{ $qoPrimary }};
    --qo-secondary : {{ $qoSecondary }};
    --qo-dark      : #131a22;
    --qo-text      : #1f2937;
    --qo-muted     : #6b7280;
    --qo-line      : #e9edf2;
}
.qo-modal{position:fixed;inset:0;z-index:99998;display:none;font-family:'Hind Siliguri','Segoe UI',system-ui,-apple-system,sans-serif;}
.qo-modal.on{display:block;}
.qo-bg{position:absolute;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(2px);}
.qo-box{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:min(820px,94vw);max-height:92vh;overflow:auto;background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.3);animation:qo-up .28s cubic-bezier(.2,.8,.2,1);}
@keyframes qo-up{from{opacity:0;transform:translate(-50%,-46%);}to{opacity:1;transform:translate(-50%,-50%);}}
@keyframes qo-fade{from{opacity:0;}to{opacity:1;}}
.qo-head{position:sticky;top:0;background:#fff;z-index:3;display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid var(--qo-line);}
.qo-head h5{margin:0;font-size:15px;font-weight:800;color:var(--qo-primary);}
.qo-x{border:0;background:#f1f3f7;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:15px;line-height:1;}
.qo-x:hover{background:var(--qo-secondary);color:#fff;}
.qo-body{display:grid;grid-template-columns:minmax(0,240px) minmax(0,1fr);gap:18px;padding:18px;}
.qo-img{border-radius:12px;overflow:hidden;border:1px solid var(--qo-line);background:#f7f9fc;}
.qo-img img{width:100%;aspect-ratio:1/1;object-fit:cover;}
.qo-name{font-size:16px;font-weight:700;line-height:1.4;margin:0 0 8px;color:var(--qo-text);}
.qo-price{display:flex;align-items:baseline;gap:9px;flex-wrap:wrap;margin-bottom:6px;}
.qo-price b{font-size:24px;font-weight:800;color:var(--qo-secondary);}
.qo-price del{color:var(--qo-muted);font-size:15px;}
.qo-save{background:#e8f7ee;color:#12a150;font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:20px;}
.qo-stock{font-size:12.5px;color:var(--qo-secondary);font-weight:600;margin-bottom:12px;}
.qo-lbl{font-size:13px;font-weight:800;margin:0 0 7px;display:flex;align-items:center;gap:6px;color:var(--qo-text);}
.qo-lbl em{font-style:normal;color:var(--qo-secondary);}
.qo-lbl .qo-req{font-size:11px;font-weight:600;color:var(--qo-muted);}
.qo-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;}
.qo-chip{border:1.5px solid #dfe3e8;background:#fff;min-width:48px;padding:8px 15px;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;transition:.18s;font-family:inherit;position:relative;}
.qo-chip-stock{display:block;font-size:10px;color:#12a150;margin-top:2px;font-weight:600;}
.qo-chip.qo-off .qo-chip-stock{color:#e11d48;}
.qo-chip:hover{border-color:var(--qo-primary);}
.qo-chip.on{border-color:var(--qo-primary);background:var(--qo-primary);color:#fff;box-shadow:0 4px 12px rgba(48,61,110,.28);}
.qo-chip .qo-dot{display:inline-block;width:13px;height:13px;border-radius:50%;border:1px solid rgba(0,0,0,.15);margin-right:6px;vertical-align:-2px;}
.qo-chips.qo-err .qo-chip{border-color:var(--qo-secondary);}
.qo-chip.qo-off{opacity:.35;cursor:not-allowed;text-decoration:line-through;background:#f5f6f8;}
.qo-chip.qo-off:hover{border-color:#dfe3e8;}
.qo-shake{animation:qo-sh .4s;}
@keyframes qo-sh{0%,100%{transform:translateX(0);}25%{transform:translateX(-6px);}75%{transform:translateX(6px);}}
.qo-qty{display:flex;align-items:center;gap:0;border:1.5px solid #dfe3e8;border-radius:9px;width:fit-content;overflow:hidden;margin-bottom:14px;}
.qo-qty button{width:40px;height:40px;border:0;background:#f7f8fa;font-size:18px;cursor:pointer;font-family:inherit;color:var(--qo-primary);font-weight:700;}
.qo-qty button:hover{background:var(--qo-primary);color:#fff;}
.qo-qty input{width:56px;height:40px;border:0;text-align:center;font-size:15px;font-weight:700;outline:none;font-family:inherit;}
.qo-total{display:flex;justify-content:space-between;align-items:center;background:#f7f9fc;border:1px dashed #cfd8e3;border-radius:10px;padding:11px 14px;margin-bottom:14px;}
.qo-total span{font-size:13.5px;font-weight:700;color:var(--qo-muted);}
.qo-total b{font-size:21px;font-weight:800;color:var(--qo-primary);}
.qo-btns{display:grid;grid-template-columns:1fr auto;gap:9px;}
.qo-confirm{border:0;cursor:pointer;background:linear-gradient(90deg,var(--qo-secondary),#ff6a3d);color:#fff;font-size:16px;font-weight:800;padding:15px 10px;border-radius:11px;font-family:inherit;box-shadow:0 8px 20px rgba(255,0,0,.28);transition:.22s;display:flex;align-items:center;justify-content:center;gap:8px;}
.qo-confirm:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(255,0,0,.36);}
.qo-confirm:active{transform:none;}
.qo-confirm:disabled{opacity:.6;cursor:not-allowed;transform:none;box-shadow:none;}
.qo-addcart{border:1.5px solid var(--qo-primary);background:#fff;color:var(--qo-primary);font-size:14px;font-weight:700;padding:0 18px;border-radius:11px;cursor:pointer;font-family:inherit;transition:.2s;}
.qo-addcart:hover{background:var(--qo-primary);color:#fff;}
.qo-trust{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:14px;padding-top:13px;border-top:1px solid var(--qo-line);}
.qo-trust div{text-align:center;font-size:11px;color:var(--qo-muted);line-height:1.4;font-weight:600;}
.qo-trust i{display:block;font-style:normal;font-size:17px;margin-bottom:3px;}
.qo-help{text-align:center;font-size:12.5px;color:var(--qo-muted);margin-top:11px;}
.qo-help a{color:var(--qo-primary);font-weight:700;}
.qo-view{display:block;text-align:center;font-size:12.5px;margin-top:8px;color:var(--qo-muted);text-decoration:underline;}
.qo-loading{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);color:var(--qo-primary);font-weight:700;font-size:14px;}
@media(max-width:640px){
    .qo-box{top:auto;bottom:0;left:0;transform:none;width:100%;max-height:94vh;border-radius:18px 18px 0 0;animation:qo-sheet .3s cubic-bezier(.2,.8,.2,1);}
    @keyframes qo-sheet{from{transform:translateY(100%);}to{transform:translateY(0);}}
    .qo-body{grid-template-columns:1fr;gap:14px;padding:14px;}
    .qo-img{max-width:190px;margin:0 auto;}
    .qo-btns{position:sticky;bottom:0;background:#fff;padding-top:8px;}
    .qo-btns{grid-template-columns:1fr 1fr;}
    .qo-addcart{padding:0 8px;font-size:13px;}
}
</style>

<div class="qo-modal" id="qoModal" aria-hidden="true">
    <div class="qo-bg" onclick="qoClose()"></div>
    <div class="qo-box">
        <div class="qo-head">
            <h5 id="qoTitle">{{ $qoTitle }}</h5>
            <button class="qo-x" type="button" onclick="qoClose()" aria-label="Close">✕</button>
        </div>

        <form class="qo-body qo-form" id="qoForm" action="{{ route('cart.store') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="qoId">
            <input type="hidden" name="product_size" id="qoSize">
            <input type="hidden" name="product_color" id="qoColor">
            <input type="hidden" name="qty" id="qoQty" value="1">
            <input type="hidden" name="order_now" id="qoNow" value="1">

            <div>
                <div class="qo-img"><img id="qoImg" src="" alt="Product"></div>
                <a class="qo-view" id="qoLink" href="#">বিস্তারিত দেখুন</a>
            </div>

            <div>
                <h4 class="qo-name" id="qoName"></h4>
                <div class="qo-price">
                    <b id="qoPrice"></b>
                    <del id="qoOld"></del>
                    <span class="qo-save" id="qoSave"></span>
                </div>
                <div class="qo-stock" id="qoStock"></div>

                <div id="qoSizeWrap" style="display:none">
                    <p class="qo-lbl">সাইজ সিলেক্ট করুন <em>*</em> <span class="qo-req" id="qoSizePick"></span></p>
                    <div class="qo-chips" id="qoSizes"></div>
                </div>

                <div id="qoColorWrap" style="display:none">
                    <p class="qo-lbl">কালার সিলেক্ট করুন <em>*</em> <span class="qo-req" id="qoColorPick"></span></p>
                    <div class="qo-chips" id="qoColors"></div>
                </div>

                <p class="qo-lbl">পরিমাণ</p>
                <div class="qo-qty">
                    <button type="button" onclick="qoQty(-1)">−</button>
                    <input type="text" id="qoQtyBox" value="1" readonly>
                    <button type="button" onclick="qoQty(1)">+</button>
                </div>

                <div class="qo-total">
                    <span>সর্বমোট</span>
                    <b id="qoTotal">৳ 0</b>
                </div>

                <div class="qo-btns">
                    <button type="submit" class="qo-confirm" id="qoConfirm">{{ $qoConfirm }}</button>
                    <button type="button" class="qo-addcart" onclick="qoAddCart()">{{ $qoCartText }}</button>
                </div>

                <div class="qo-trust">
                    <div><i>🚚</i> ক্যাশ অন ডেলিভারি</div>
                    <div><i>🔄</i> সহজ রিটার্ন</div>
                    <div><i>✅</i> ১০০% অরিজিনাল</div>
                </div>

                @if(!empty($qoHotline))
                <p class="qo-help">যেকোনো সাহায্যে কল করুন: <a href="tel:{{ $qoHotline }}">{{ $qoHotline }}</a></p>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';

    /* ---------- Admin settings থেকে কনফিগ ---------- */
    window.CDQuickOrder = {
        enabled:   {{ $qoEnabled ? 'true' : 'false' }},
        title:     @json($qoTitle),
        confirmText: @json($qoConfirm),
        cartText:  @json($qoCartText),
        cartToast: @json($qoToast),
        endpoint:  "{{ url('quick-order') }}" + '/'
    };

    if (!window.CDP) window.CDP = {};   // embedded ডাটা ক্যাশ (হোমপেজ) + fetched ডাটা

    var mo  = document.getElementById('qoModal');
    var cd  = { p:null, size:null, color:null, qty:1, price:0, stock:0, cartOnly:false };

    function $(id) { return document.getElementById(id); }
    function qoNum(n) { return Number(n || 0).toLocaleString('en-US'); }

    /* ---------- টোস্ট (দ্রুত নোটিফিকেশন) ---------- */
    function qoToast(msg, err) {
        var t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:' + (err ? '#e11d48' : '#12a150') + ';color:#fff;padding:11px 22px;border-radius:30px;z-index:100000;font-size:13.5px;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,.2);white-space:nowrap';
        document.body.appendChild(t);
        setTimeout(function(){ t.remove(); }, 2400);
    }
    window.qoToast = qoToast;

    /* ---------- প্রোডাক্ট ডাটা লোড (ক্যাশে না থাকলে fetch) ---------- */
    function qoLoad(id, cb) {
        if (window.CDP && window.CDP[id]) { cb(window.CDP[id]); return; }
        fetch(window.CDQuickOrder.endpoint + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.error) { qoToast('প্রোডাক্ট পাওয়া যায়নি', 1); return; }
                window.CDP[id] = data;
                cb(data);
            })
            .catch(function () { qoToast('সমস্যা হয়েছে, আবার চেষ্টা করুন', 1); });
    }

    /* ---------- পপআপ খোলা ---------- */
    window.qoOpen = function (id, cartOnly, pre) {
        if (!window.CDQuickOrder.enabled) return false;
        if (!id) return false;
        qoLoad(id, function (p) {
            pre = pre || {};
            cd = {
                p: p,
                size:   pre.size  || null,
                color:  pre.color || null,
                qty:    (parseInt(pre.qty, 10) > 0) ? parseInt(pre.qty, 10) : 1,
                price:  p.price,
                stock:  p.stock,
                cartOnly: !!cartOnly
            };
            $('qoId').value    = p.id;
            $('qoImg').src     = p.img;
            $('qoName').textContent = p.name;
            $('qoLink').href   = p.url;
            $('qoSize').value  = '';
            $('qoColor').value = '';
            $('qoNow').value   = cd.cartOnly ? '' : '1';

            var btn = $('qoConfirm');
            btn.disabled = false;
            btn.textContent = cd.cartOnly ? window.CDQuickOrder.cartText : window.CDQuickOrder.confirmText;

            buildChips('qoSizes',  'qoSizeWrap',  p.sizes,  'size',  pre.size);
            buildChips('qoColors', 'qoColorWrap', p.colors, 'color', pre.color);

            /* একটাই অপশন থাকলে অটো সিলেক্ট (কম ক্লিক = বেশি অর্ডার) */
            if (p.sizes.length === 1 && !pre.size)  pick('size',  p.sizes[0].id,  0);
            if (p.colors.length === 1 && !pre.color) pick('color', p.colors[0].id, 0);

            qoSync();
            setQty(cd.qty);
            mo.classList.add('on');
            document.body.style.overflow = 'hidden';
        });
        return true;
    };

    /* ---------- চিপ (সাইজ/কালার বাটন) তৈরি ---------- */
    function buildChips(boxId, wrapId, list, type, preId) {
        var box = $(boxId), wrap = $(wrapId);
        box.innerHTML = ''; box.classList.remove('qo-err');
        $(type === 'size' ? 'qoSizePick' : 'qoColorPick').textContent = '';
        if (!list || !list.length) { wrap.style.display = 'none'; return; }
        wrap.style.display = '';

        var selIdx = -1;
        list.forEach(function (o, idx) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'qo-chip';
            b.dataset.id = o.id;
            b.dataset.stock = (type === 'size' && o.has_stock) ? o.stock : '';
            if (type === 'size' && o.has_stock && Number(o.stock) <= 0) b.classList.add('qo-off');
            b.innerHTML = (type === 'color' && o.hex ? '<span class="qo-dot" style="background:' + o.hex + '"></span>' : '') + o.name +
                (type === 'size' && o.has_stock ? '<small class="qo-chip-stock">' + (o.stock > 0 ? o.stock + ' টি' : 'স্টক শেষ') + '</small>' : '');
            b.onclick = function () { if (!b.classList.contains('qo-off')) pick(type, o.id, idx); };
            box.appendChild(b);
            if (preId && String(o.id) === String(preId)) selIdx = idx;
        });
        if (selIdx > -1) pick(type, list[selIdx].id, selIdx);
    }

    /* ---------- সিলেক্ট ---------- */
    function pick(type, id, idx) {
        var box = $(type === 'size' ? 'qoSizes' : 'qoColors');
        [].forEach.call(box.children, function (b, i) { b.classList.toggle('on', i === idx); });
        box.classList.remove('qo-err');
        cd[type] = id;
        $(type === 'size' ? 'qoSize' : 'qoColor').value = id;
        var lbl = box.children[idx] ? box.children[idx].textContent.trim() : '';
        $(type === 'size' ? 'qoSizePick' : 'qoColorPick').textContent = '— ' + lbl;
        qoSync();
        setQty(cd.qty);
    }

    /* ---------- ভ্যারিয়েন্ট অনুযায়ী দাম / স্টক / অ্যাভেইলেবিলিটি ---------- */
    function avail(boxId, fn) {
        var box = $(boxId);
        [].forEach.call(box.children, function (b) {
            var hasStock = !b.dataset.stock || Number(b.dataset.stock) > 0;
            var ok = fn(b.dataset.id) && hasStock;
            b.classList.toggle('qo-off', !ok);
            if (!ok) b.classList.remove('on');
        });
    }
    function qoSync() {
        var p = cd.p, vs = p.variants || [];
        if (vs.length) {
            avail('qoSizes', function (id) {
                return vs.some(function (v) { return v.s == id && (cd.color == null || v.c == null || v.c == cd.color); });
            });
            avail('qoColors', function (id) {
                return vs.some(function (v) { return v.c == id && (cd.size == null || v.s == null || v.s == cd.size); });
            });

            var match = vs.filter(function (v) {
                return (cd.size == null || v.s == null || v.s == cd.size) &&
                       (cd.color == null || v.c == null || v.c == cd.color);
            });
            var chosen = (!p.sizes.length  || cd.size  != null) &&
                         (!p.colors.length || cd.color != null);
            if (chosen && match.length) {
                if (match[0].p > 0) cd.price = match[0].p;
                var stockRows = match.filter(function (v) { return v.st !== null; });
                if (stockRows.length) {
                    cd.stock = (cd.color != null || stockRows.length === 1)
                        ? Number(stockRows[0].st)
                        : stockRows.reduce(function (sum, v) { return sum + Number(v.st); }, 0);
                }
            } else {
                cd.price = p.price;
                cd.stock = p.stock;
            }
        }

        $('qoPrice').textContent = '৳ ' + qoNum(cd.price);
        var oldEl = $('qoOld'), saveEl = $('qoSave');
        if (p.old && p.old > cd.price) {
            oldEl.textContent = '৳ ' + qoNum(p.old);
            saveEl.textContent = 'সাশ্রয় ৳ ' + qoNum(p.old - cd.price);
            oldEl.style.display = ''; saveEl.style.display = '';
        } else { oldEl.style.display = 'none'; saveEl.style.display = 'none'; }

        var st = $('qoStock');
        if (cd.stock !== null && cd.stock <= 0) {
            st.textContent = '❌ এই ভ্যারিয়েন্টটি স্টকে নেই'; st.style.color = '#e11d48';
        } else if (cd.stock > 0 && cd.stock <= 20) {
            st.textContent = '🔥 তাড়াতাড়ি করুন! মাত্র ' + cd.stock + ' টি বাকি আছে'; st.style.color = 'var(--qo-secondary)';
        } else {
            st.textContent = '✅ স্টকে আছে'; st.style.color = '#12a150';
        }
    }

    /* ---------- পরিমাণ ---------- */
    window.qoQty = function (d) { setQty(cd.qty + d); };
    function setQty(q) {
        var max = (cd.stock && cd.stock > 0) ? cd.stock : 99;
        cd.qty = Math.max(1, Math.min(q, max));
        $('qoQtyBox').value = cd.qty;
        $('qoQty').value    = cd.qty;
        $('qoTotal').textContent = '৳ ' + qoNum(cd.price * cd.qty);
    }

    window.qoClose = function () { mo.classList.remove('on'); document.body.style.overflow = ''; };
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') qoClose(); });

    /* ---------- ভ্যালিডেশন ---------- */
    function qoValidate() {
        var ok = true;
        if (cd.p.sizes.length  && !cd.size)  { flag('qoSizes');  ok = false; }
        if (cd.p.colors.length && !cd.color) { flag('qoColors'); ok = false; }
        if (ok && cd.stock !== null && cd.stock <= 0) { qoToast('এই ভ্যারিয়েন্টটি স্টকে নেই', 1); ok = false; }
        return ok;
    }
    function flag(id) {
        var b = $(id);
        b.classList.add('qo-err', 'qo-shake');
        setTimeout(function () { b.classList.remove('qo-shake'); }, 420);
        b.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    /* কনফার্ম → cart.store (order_now=1) → সরাসরি Checkout */
    $('qoForm').addEventListener('submit', function (e) {
        if (!qoValidate()) { e.preventDefault(); return false; }
        var btn = $('qoConfirm');
        btn.disabled = true; btn.textContent = 'অপেক্ষা করুন...';
    });

    /* শুধু কার্টে রাখা (রিলোড ছাড়া) */
    window.qoAddCart = function () {
        if (!qoValidate()) return;
        var form = $('qoForm');
        var fd = new FormData(form);
        fd.delete('order_now');
        fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().catch(function () { return { success: true }; }); })
            .then(function (res) {
                if (res && res.success === false) { qoToast(res.message || 'সমস্যা হয়েছে', 1); return; }
                var c = document.querySelector('.cart_count, #cart-qty span, .mobilecart-qty');
                if (c) c.textContent = (parseInt(c.textContent || '0', 10) + cd.qty);
                qoClose();
                qoToast(window.CDQuickOrder.cartToast);
            })
            .catch(function () { form.submit(); });
    };

    /* ---------- কার্ট কাউন্ট অ্যাজাক্স আপডেট (নিরাপদ) ---------- */
    window.qoRefreshCartCount = function () {
        fetch("{{ route('cart.count') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var n = (d && (d.count || d.length)) || 0;
                var c = document.querySelector('.cart_count, #cart-qty span, .mobilecart-qty');
                if (c && n) c.textContent = n;
            })
            .catch(function () {});
    };

    /* ======================================================================
       গ্লোবাল ইন্টারসেপশন:
       1) cart.store ফর্ম submit → পপআপ খুলবে
       2) .qo-order-link / .qo-cart-link <a> ক্লিক → পপআপ খুলবে
       ====================================================================== */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!window.CDQuickOrder || !window.CDQuickOrder.enabled) return;
        if (!form || !form.action || form.action.indexOf('cart.store') === -1) return;
        /* নিজস্ব পপআপ ফর্ম / হোমপেজ CD পপআপ ফর্ম বাদ */
        if (form.classList.contains('qo-form') || form.id === 'cdMoForm') return;

        var idEl = form.querySelector('input[name="id"]');
        var id = idEl ? idEl.value : '';
        if (!id) return;

        e.preventDefault();

        var sub = e.submitter || (form.querySelector('button[type=submit]:focus, input[type=submit]:focus'));
        var cartOnly = true;
        if (sub) {
            var cls = String(sub.className || '') + ' ' + String(sub.name || '');
            if ((cls.indexOf('order') > -1 && cls.indexOf('cart') === -1) || sub.name === 'order_now') cartOnly = false;
        }
        var pre = {};
        var sz = form.querySelector('input[name="product_size"]');
        var cl = form.querySelector('input[name="product_color"]');
        var qt = form.querySelector('input[name="qty"]');
        if (sz && sz.value) pre.size = sz.value;
        if (cl && cl.value) pre.color = cl.value;
        if (qt && qt.value) pre.qty = qt.value;

        window.qoOpen(id, cartOnly, pre);
    });

    document.addEventListener('click', function (e) {
        if (!window.CDQuickOrder || !window.CDQuickOrder.enabled) return;
        var a = e.target.closest ? e.target.closest('.qo-order-link, .qo-cart-link') : null;
        if (!a) return;
        var id = a.getAttribute('data-id');
        if (!id) return;
        e.preventDefault();
        var cartOnly = a.classList.contains('qo-cart-link');
        window.qoOpen(id, cartOnly, {});
    });
})();
</script>
