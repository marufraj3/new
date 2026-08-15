/** Runtime behavior for published visual campaign pages. */
(function () {
    'use strict';

    const root = document.getElementById('campaign-builder-storefront');
    if (!root) return;

    const products = Array.isArray(window._campaignProducts) ? window._campaignProducts : [];
    const campaign = window._campaignData || {};
    const loading = document.getElementById('cpb-store-loading');
    const toastElement = document.getElementById('cpb-store-toast');
    let selectedProductId = String(products[0]?.id || '');
    let requestInProgress = false;
    let variantSelectionSynced = true;
    let toastTimer = null;

    /* পপআপের চলমান অবস্থা: প্রোডাক্ট, নির্বাচিত সাইজ/কালার, পরিমাণ, লাইভ প্রাইস/স্টক */
    const modal = document.getElementById('cpb-modal');
    const state = { product: null, size: null, color: null, qty: 1, price: 0, stock: null };
    /* কনফার্ম হওয়া ভ্যারিয়েন্ট (order form guard এই তথ্যটাই দেখে) */
    let confirmedSelection = null;

    function cloneTemplate(id) {
        return document.getElementById(id)?.content.cloneNode(true) || document.createDocumentFragment();
    }

    function mountDynamicContent() {
        root.querySelectorAll('[data-cpb-dynamic="products"]').forEach(container => {
            const label = container.dataset.buttonLabel || 'অর্ডার করুন';
            container.replaceChildren(cloneTemplate('cpb-live-products-template'));
            container.querySelectorAll('[data-product-button-label]').forEach(node => { node.textContent = label; });
        });

        root.querySelectorAll('[data-cpb-dynamic="reviews"]').forEach(container => {
            container.replaceChildren(cloneTemplate('cpb-live-reviews-template'));
        });

        const checkoutTargets = Array.from(root.querySelectorAll('[data-cpb-dynamic="checkout"]'));
        checkoutTargets.forEach((container, index) => {
            if (index === 0) {
                container.id = 'order_form';
                container.replaceChildren(cloneTemplate('cpb-live-checkout-template'));
            } else {
                container.innerHTML = '<div class="cpb-empty-dynamic">Checkout form উপরে দেখানো হয়েছে। <a href="#order_form">অর্ডার ফর্মে যান</a></div>';
            }
        });

        mountSelectedVariantSummary();
        root.querySelectorAll('[data-cpb-youtube]').forEach(mountYoutube);
        initializeCountdowns();
        hideLegacyVariantPicker();
        const selectedArea = root.querySelector('#cpb-area');
        if (selectedArea && selectedArea.selectedIndex > 0) updateShipping(selectedArea.value);

        const stickyButton = document.getElementById('cpb-sticky-order');
        stickyButton.hidden = checkoutTargets.length === 0;
        if (checkoutTargets.length && root.querySelector('.cpb-form-errors')) {
            setTimeout(() => checkoutTargets[0].scrollIntoView({ behavior: 'smooth', block: 'start' }), 150);
        }
    }

    function mountYoutube(container) {
        const id = youtubeId(container.dataset.cpbYoutube || '');
        if (!id) return;
        const iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id);
        iframe.title = 'Campaign video';
        iframe.loading = 'lazy';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        iframe.allowFullscreen = true;
        const frame = document.createElement('div');
        frame.className = 'cpb-video-frame';
        frame.appendChild(iframe);
        container.replaceWith(frame);
    }

    function youtubeId(input) {
        const value = String(input || '').trim();
        if (/^[a-zA-Z0-9_-]{6,20}$/.test(value)) return value;
        try {
            const url = new URL(value);
            if (url.hostname.includes('youtu.be')) return url.pathname.split('/').filter(Boolean)[0] || '';
            if (url.hostname.includes('youtube.com')) return url.searchParams.get('v') || url.pathname.split('/').filter(Boolean).pop() || '';
        } catch (_) {}
        return '';
    }

    function initializeCountdowns() {
        const countdowns = Array.from(root.querySelectorAll('[data-cpb-countdown]'));
        if (!countdowns.length) return;
        const update = function () {
            countdowns.forEach(container => {
                const deadline = new Date(container.dataset.cpbCountdown).getTime();
                if (!Number.isFinite(deadline)) { container.hidden = true; return; }
                const distance = Math.max(0, deadline - Date.now());
                const values = {
                    days: Math.floor(distance / 86400000),
                    hours: Math.floor((distance % 86400000) / 3600000),
                    minutes: Math.floor((distance % 3600000) / 60000),
                    seconds: Math.floor((distance % 60000) / 1000)
                };
                Object.entries(values).forEach(([key, value]) => {
                    const target = container.querySelector('[data-' + key + ']');
                    if (target) target.textContent = String(value).padStart(2, '0');
                });
                container.classList.toggle('is-expired', distance === 0);
            });
        };
        update();
        window.setInterval(update, 1000);
    }

    /**
     * পুরনো dropdown variant picker আর ব্যবহার হয় না — পুরনো builder HTML
     * না ভাঙার জন্য শুধু hidden করে রাখি (select-গুলোর required-ও সরাই,
     * যাতে browser validation অর্ডার ফর্ম আটকে না দেয়)।
     */
    function hideLegacyVariantPicker() {
        const picker = root.querySelector('#cpb-variant-picker');
        if (!picker) return;
        picker.hidden = true;
        picker.querySelectorAll('select').forEach(select => { select.required = false; select.disabled = true; });
    }

    /* নির্বাচিত ভ্যারিয়েন্ট সামারি bar চেকআউট ব্লকের ঠিক উপরে বসাই */
    function mountSelectedVariantSummary() {
        if (root.querySelector('#cpb-selected-variant')) return;
        const template = document.getElementById('cpb-selected-variant-template');
        const anchor = root.querySelector('#cpb-variant-picker') || root.querySelector('.cpb-checkout-columns');
        if (!template || !anchor) return;
        anchor.parentNode.insertBefore(template.content.cloneNode(true), anchor);
    }

    function findProduct(id) {
        return products.find(product => String(product.id) === String(id));
    }

    function productSizes(product) { return (product && product.sizes) || []; }
    function productColors(product) { return (product && product.colors) || []; }
    function productVariants(product) { return (product && product.variants) || []; }
    function hasVariantOptions(product) { return productSizes(product).length > 0 || productColors(product).length > 0; }

    function formatMoney(value) { return Number(value || 0).toLocaleString('en-US'); }
    function byId(id) { return document.getElementById(id); }

    /* ---------- পপআপ খোলা/বন্ধ ---------- */
    function openVariantModal(productId, moveToCheckout) {
        const product = findProduct(productId);
        if (!product) return;
        state.product = product;
        state.size = null;
        state.color = null;
        state.qty = 1;
        state.price = Number(product.price || 0);
        state.stock = product.stock === undefined ? null : product.stock;
        state.moveToCheckout = !!moveToCheckout;

        /* সাইজ/কালার কিছুই না থাকলে পপআপ ছাড়াই সরাসরি কার্টে */
        if (!hasVariantOptions(product)) {
            confirmVariantSelection();
            return;
        }

        if (!modal) { confirmVariantSelection(); return; }

        byId('cpb-mo-img').src = product.image || '';
        byId('cpb-mo-img').alt = product.name || '';
        byId('cpb-mo-name').textContent = product.name || '';

        buildChips('cpb-sizes', 'cpb-size-wrap', productSizes(product), 'size');
        buildChips('cpb-colors', 'cpb-color-wrap', productColors(product), 'color');

        /* একটাই অপশন থাকলে অটো-সিলেক্ট (কম ক্লিক = বেশি কনভার্শন) */
        if (productSizes(product).length === 1) pickChip('size', productSizes(product)[0].id);
        if (productColors(product).length === 1) pickChip('color', productColors(product)[0].id);

        syncVariantState();
        setQty(1);
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeVariantModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    /* ---------- সাইজ/কালার চিপ ---------- */
    function buildChips(boxId, wrapId, list, type) {
        const box = byId(boxId);
        const wrap = byId(wrapId);
        if (!box || !wrap) return;
        box.innerHTML = '';
        box.classList.remove('is-error');
        if (!list.length) { wrap.hidden = true; return; }
        wrap.hidden = false;
        list.forEach(option => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'cpb-chip';
            chip.dataset.id = String(option.id);
            let stockNote = '';
            if (type === 'size' && option.has_stock) {
                stockNote = '<small>' + (Number(option.stock) > 0 ? escapeHtml(option.stock + ' টি আছে') : 'স্টক শেষ') + '</small>';
                if (Number(option.stock) <= 0) chip.classList.add('is-out');
            }
            const dot = (type === 'color' && option.hex) ? '<span class="cpb-dot" style="background:' + escapeHtml(option.hex) + '"></span>' : '';
            chip.innerHTML = dot + escapeHtml(option.name || '') + stockNote;
            chip.addEventListener('click', () => { if (!chip.classList.contains('is-out')) pickChip(type, option.id); });
            box.appendChild(chip);
        });
    }

    function pickChip(type, id) {
        const box = byId(type === 'size' ? 'cpb-sizes' : 'cpb-colors');
        if (!box) return;
        Array.from(box.children).forEach(chip => chip.classList.toggle('is-active', String(chip.dataset.id) === String(id)));
        box.classList.remove('is-error');
        state[type] = String(id);
        syncVariantState();
        setQty(state.qty);
    }

    /* ---------- ভ্যারিয়েন্ট মিলিয়ে availability / price / stock লাইভ আপডেট ---------- */
    function markAvailability(boxId, isAvailable) {
        const box = byId(boxId);
        if (!box) return;
        Array.from(box.children).forEach(chip => {
            const ok = isAvailable(chip.dataset.id);
            chip.classList.toggle('is-out', !ok);
            if (!ok) chip.classList.remove('is-active');
        });
    }

    function variantMatches(variant, sizeId, colorId) {
        const sizeOk = sizeId == null || variant.size_id == null || String(variant.size_id) === String(sizeId);
        const colorOk = colorId == null || variant.color_id == null || String(variant.color_id) === String(colorId);
        return sizeOk && colorOk;
    }

    function syncVariantState() {
        const product = state.product;
        if (!product) return;
        const variants = productVariants(product);

        if (variants.length) {
            markAvailability('cpb-sizes', id => variants.some(variant =>
                String(variant.size_id) === String(id) &&
                (state.color == null || variant.color_id == null || String(variant.color_id) === String(state.color)) &&
                (variant.stock === null || Number(variant.stock) > 0)
            ));
            markAvailability('cpb-colors', id => variants.some(variant =>
                String(variant.color_id) === String(id) &&
                (state.size == null || variant.size_id == null || String(variant.size_id) === String(state.size)) &&
                (variant.stock === null || Number(variant.stock) > 0)
            ));

            const matched = variants.filter(variant => variantMatches(variant, state.size, state.color));
            const chosen = (!productSizes(product).length || state.size != null) && (!productColors(product).length || state.color != null);
            if (chosen && matched.length) {
                state.price = Number(matched[0].price) > 0 ? Number(matched[0].price) : Number(product.price || 0);
                const rows = matched.filter(variant => variant.stock !== null);
                state.stock = rows.length
                    ? ((state.color != null || rows.length === 1) ? Number(rows[0].stock) : rows.reduce((sum, variant) => sum + Number(variant.stock), 0))
                    : (product.stock === undefined ? null : product.stock);
            } else {
                state.price = Number(product.price || 0);
                state.stock = product.stock === undefined ? null : product.stock;
            }
        }

        const priceEl = byId('cpb-mo-price');
        const oldEl = byId('cpb-mo-old');
        const saveEl = byId('cpb-mo-save');
        const stockEl = byId('cpb-mo-stock');
        if (priceEl) priceEl.textContent = '৳ ' + formatMoney(state.price);
        if (oldEl && saveEl) {
            const oldPrice = Number(product.old_price || 0);
            if (oldPrice > state.price) {
                oldEl.textContent = '৳ ' + formatMoney(oldPrice);
                saveEl.textContent = 'সাশ্রয় ৳ ' + formatMoney(oldPrice - state.price);
                oldEl.hidden = false; saveEl.hidden = false;
            } else { oldEl.hidden = true; saveEl.hidden = true; }
        }
        if (stockEl) {
            if (state.stock !== null && state.stock !== undefined && Number(state.stock) <= 0) {
                stockEl.textContent = '❌ এই ভ্যারিয়েন্টটি স্টকে নেই';
                stockEl.style.color = '#dc2626';
            } else if (state.stock !== null && state.stock !== undefined && Number(state.stock) > 0 && Number(state.stock) <= 20) {
                stockEl.textContent = '🔥 তাড়াতাড়ি করুন! মাত্র ' + state.stock + ' টি বাকি';
                stockEl.style.color = '#ea580c';
            } else {
                stockEl.textContent = '✅ স্টকে আছে';
                stockEl.style.color = '#12a150';
            }
        }
    }

    /* ---------- পরিমাণ ---------- */
    function setQty(value) {
        const max = (state.stock !== null && state.stock !== undefined && Number(state.stock) > 0) ? Number(state.stock) : 99;
        state.qty = Math.max(1, Math.min(Number(value) || 1, max));
        const box = byId('cpb-qty-box');
        const total = byId('cpb-mo-total');
        if (box) box.value = state.qty;
        if (total) total.textContent = '৳ ' + formatMoney(state.price * state.qty);
    }

    function shakeChips(boxId) {
        const box = byId(boxId);
        if (!box) return;
        box.classList.add('is-error', 'cpb-shake');
        setTimeout(() => box.classList.remove('cpb-shake'), 420);
        box.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    /* ---------- কনফার্ম → cart.changeProduct (size/color/qty সহ) ---------- */
    async function confirmVariantSelection() {
        const product = state.product;
        if (!product || requestInProgress) return;
        if (productSizes(product).length && !state.size) { shakeChips('cpb-sizes'); return; }
        if (productColors(product).length && !state.color) { shakeChips('cpb-colors'); return; }
        if (state.stock !== null && state.stock !== undefined && Number(state.stock) <= 0) {
            showToast('এই ভ্যারিয়েন্টটি স্টকে নেই');
            return;
        }

        const confirmButton = byId('cpb-mo-confirm');
        if (confirmButton) confirmButton.disabled = true;
        setBusy(true);
        try {
            const html = await requestHtml(root.dataset.changeProductUrl, {
                id: product.id,
                product_size: state.size || '',
                product_color: state.color || '',
                qty: state.qty
            });
            selectedProductId = String(product.id);
            confirmedSelection = { productId: String(product.id), size: state.size, color: state.color, qty: state.qty, price: state.price };
            variantSelectionSynced = true;
            replaceCart(html);
            updateSelectedCards(product.id);
            showSelectedVariant(product);
            trackAddToCart(product.id, state.price, state.qty);
            closeVariantModal();
            showToast('✔ কার্টে যোগ হয়েছে — এখন ডেলিভারি তথ্য দিন', true);
            document.dispatchEvent(new CustomEvent('campaign:product-selected', { detail: { product, size: state.size, color: state.color, qty: state.qty } }));
            if (state.moveToCheckout) root.querySelector('#order_form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            showToast(error.message || 'কার্ট আপডেট করা যায়নি। আবার চেষ্টা করুন।');
        } finally {
            if (confirmButton) confirmButton.disabled = false;
            setBusy(false);
        }
    }

    function showSelectedVariant(product) {
        const box = root.querySelector('#cpb-selected-variant');
        const text = root.querySelector('#cpb-selected-variant-text');
        if (!box || !text) return;
        const parts = [];
        if (state.size) {
            const size = productSizes(product).find(item => String(item.id) === String(state.size));
            if (size) parts.push('সাইজ: ' + size.name);
        }
        if (state.color) {
            const color = productColors(product).find(item => String(item.id) === String(state.color));
            if (color) parts.push('কালার: ' + color.name);
        }
        parts.push('পরিমাণ: ' + state.qty + ' টি');
        text.textContent = '✓ ' + String(product.name || '').substring(0, 30) + ' — ' + parts.join(' | ');
        box.hidden = false;
        box.dataset.productId = String(product.id);
    }

    /**
     * প্রোডাক্ট সিলেক্ট: ভ্যারিয়েন্ট থাকলে পপআপ, নাহলে সরাসরি কার্টে।
     */
    function selectProduct(productId, moveToCheckout) {
        productId = String(productId || '');
        if (!productId || requestInProgress) return;
        openVariantModal(productId, moveToCheckout);
    }

    async function mutateCart(url, rowId) {
        if (!url || !rowId || requestInProgress) return;
        setBusy(true);
        try { replaceCart(await requestHtml(url, { id: rowId })); }
        catch (error) { showToast(error.message || 'কার্ট আপডেট করা যায়নি।'); }
        finally { setBusy(false); }
    }

    async function updateShipping(areaId) {
        if (!areaId || requestInProgress) return;
        setBusy(true);
        try { replaceCart(await requestHtml(root.dataset.shippingUrl, { id: areaId })); }
        catch (error) { showToast(error.message || 'ডেলিভারি চার্জ আপডেট করা যায়নি।'); }
        finally { setBusy(false); }
    }

    async function requestHtml(url, params) {
        const requestUrl = new URL(url, window.location.origin);
        requestUrl.searchParams.set('campaign', '1');
        Object.entries(params || {}).forEach(([key, value]) => requestUrl.searchParams.set(key, value));
        const response = await fetch(requestUrl.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html', 'X-Campaign-Page': '1' }, credentials: 'same-origin' });
        const contentType = response.headers.get('content-type') || '';
        if (!response.ok) {
            if (contentType.includes('application/json')) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'Request failed.');
            }
            throw new Error('Request failed (' + response.status + ').');
        }
        return response.text();
    }

    function replaceCart(html) {
        root.querySelectorAll('.cartlist').forEach(container => { container.innerHTML = html; });
    }

    function updateSelectedCards(productId) {
        root.querySelectorAll('[data-product-card], .cpb-compact-product').forEach(card => {
            const id = card.dataset.productCard || card.dataset.selectProduct;
            card.classList.toggle('is-selected', String(id) === String(productId));
        });
    }

    function setBusy(value) {
        requestInProgress = value;
        loading.hidden = !value;
        document.body.classList.toggle('cpb-store-busy', value);
    }

    function showToast(message, isSuccess) {
        window.clearTimeout(toastTimer);
        toastElement.textContent = message;
        toastElement.style.background = isSuccess ? '#12a150' : '#b91c1c';
        toastElement.classList.add('is-visible');
        toastTimer = window.setTimeout(() => toastElement.classList.remove('is-visible'), 4200);
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
    }

    function trackAddToCart(productId, price, qty) {
        const product = findProduct(productId) || {};
        const unitPrice = Number(price !== undefined ? price : (product.price || 0));
        const quantity = Math.max(1, Number(qty || 1));
        const item = { item_id: String(productId), item_name: product.name || '', price: unitPrice, quantity };
        const value = unitPrice * quantity;
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({ event: 'add_to_cart', ecommerce: { currency: 'BDT', value, items: [item] } });
        if (typeof window.fbq === 'function') {
            window.fbq('track', 'AddToCart', { content_ids: [item.item_id], content_name: item.item_name, content_type: 'product', value, currency: 'BDT' }, { eventID: 'atc_' + item.item_id + '_' + Math.floor(Date.now() / 1000) });
        }
        if (typeof window.ttq !== 'undefined' && typeof window.ttq.track === 'function') {
            window.ttq.track('AddToCart', { content_id: item.item_id, content_name: item.item_name, content_type: 'product', value, currency: 'BDT', quantity });
        }
    }

    function trackCheckout() {
        const subtotalNode = root.querySelector('#net_total strong');
        const subtotal = Number.parseFloat(String(subtotalNode?.textContent || '0').replace(/[^0-9.]/g, '')) || 0;
        const selectedProduct = findProduct(selectedProductId) || products[0] || {};
        const items = [{ item_id: String(selectedProduct.id || ''), item_name: selectedProduct.name || '', price: Number(selectedProduct.price || 0), index: 0, quantity: 1 }];
        const ids = items[0].item_id ? [items[0].item_id] : [];
        const timestamp = Math.floor(Date.now() / 1000);
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({ event: 'begin_checkout', ecommerce: { currency: 'BDT', value: subtotal, items } });
        if (typeof window.fbq === 'function') {
            window.fbq('track', 'InitiateCheckout', { content_ids: ids, content_type: 'product', value: subtotal, currency: 'BDT', num_items: ids.length }, { eventID: 'ic_camp' + campaign.id + '_' + timestamp });
            window.fbq('track', 'Lead', { value: subtotal, currency: 'BDT', content_name: campaign.name || '' }, { eventID: 'lead_camp' + campaign.id + '_' + timestamp });
        }
        if (typeof window.ttq !== 'undefined' && typeof window.ttq.track === 'function') {
            window.ttq.track('InitiateCheckout', { content_ids: ids, content_type: 'product', value: subtotal, currency: 'BDT', quantity: ids.length });
        }
    }

    function trackOrderClick() {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ event: 'click_order_now_button', campaign_id: campaign.id || '', campaign_name: campaign.name || '' });
    }

    function bindEvents() {
        document.addEventListener('click', event => {
            const productButton = event.target.closest('[data-select-product]');
            if (productButton) {
                event.preventDefault();
                if (productButton.hasAttribute('data-order-product')) trackOrderClick();
                selectProduct(productButton.dataset.selectProduct, productButton.hasAttribute('data-order-product'));
                return;
            }

            const increment = event.target.closest('.cart_increment');
            if (increment) { event.preventDefault(); mutateCart(root.dataset.cartIncrementUrl, increment.dataset.id); return; }
            const decrement = event.target.closest('.cart_decrement');
            if (decrement) { event.preventDefault(); mutateCart(root.dataset.cartDecrementUrl, decrement.dataset.id); return; }
            const remove = event.target.closest('.cart_remove');
            if (remove) { event.preventDefault(); mutateCart(root.dataset.cartRemoveUrl, remove.dataset.id); return; }

            const modalClose = event.target.closest('[data-cpb-modal-close]');
            if (modalClose) { event.preventDefault(); closeVariantModal(); return; }

            const qtyButton = event.target.closest('[data-cpb-qty]');
            if (qtyButton) { event.preventDefault(); setQty(state.qty + Number(qtyButton.dataset.cpbQty)); return; }

            const changeVariant = event.target.closest('#cpb-change-variant');
            if (changeVariant) {
                event.preventDefault();
                const id = root.querySelector('#cpb-selected-variant')?.dataset.productId;
                if (id) openVariantModal(id, false);
                return;
            }

            const orderLink = event.target.closest('a[href="#order_form"], .cam_order_now');
            if (orderLink) trackOrderClick();
        });

        document.getElementById('cpb-mo-confirm')?.addEventListener('click', confirmVariantSelection);

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeVariantModal();
        });

        document.addEventListener('change', event => {
            if (event.target.matches('#cpb-area')) updateShipping(event.target.value);
        });

        document.addEventListener('submit', event => {
            const form = event.target.closest('[data-cpb-order-form]');
            if (!form) return;

            if (requestInProgress) {
                event.preventDefault();
                showToast('প্রোডাক্ট আপডেট হচ্ছে—এক মুহূর্ত পরে আবার অর্ডার করুন।');
                return;
            }

            /* Variant guard: ভ্যারিয়েন্টওয়ালা প্রোডাক্ট কনফার্ম না করে অর্ডার করা যাবে না */
            const activeProduct = findProduct(selectedProductId) || products[0];
            const needsVariant = hasVariantOptions(activeProduct);
            const isConfirmed = confirmedSelection && String(confirmedSelection.productId) === String(activeProduct?.id);

            if (needsVariant && !isConfirmed) {
                event.preventDefault();
                showToast('অর্ডারের আগে সাইজ/কালার সিলেক্ট করুন');
                openVariantModal(activeProduct.id, false);
                return;
            }

            if (!variantSelectionSynced) {
                event.preventDefault();
                showToast('এই সাইজ ও কালারের সমন্বয়টি নিশ্চিত করা যায়নি। অন্য অপশন বেছে আবার চেষ্টা করুন।');
                if (activeProduct) openVariantModal(activeProduct.id, false);
                return;
            }

            trackCheckout();
            const submit = form.querySelector('[type="submit"]');
            if (submit) { submit.disabled = true; submit.querySelector('span').textContent = 'অর্ডার প্রসেস হচ্ছে...'; }
        });

        document.getElementById('cpb-sticky-order').addEventListener('click', () => {
            trackOrderClick();
            root.querySelector('#order_form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    /* ============================================================
       Visual design ON/OFF toggle
       বাটনে ক্লিক করলে builder-এর কাস্টম page_html/CSS ডিজাইন hide হয়ে
       শুধু প্রোডাক্ট + রিভিউ + চেকআউট (plain) দেখায়; আবার ক্লিক করলে ফিরে আসে।
       ============================================================ */
    function initDesignToggle() {
        const button = document.getElementById('cpb-design-toggle');
        if (!button) return;
        const label = button.querySelector('[data-design-toggle-label]');
        const storageKey = 'cpb-design-hidden-' + (campaign.id || 'campaign');
        const plainView = document.createElement('div');
        plainView.id = 'cpb-plain-view';
        plainView.hidden = true;
        root.appendChild(plainView);

        const blocks = [
            { node: root.querySelector('[data-cpb-dynamic="products"]'), heading: 'আমাদের পণ্যসমূহ' },
            { node: root.querySelector('[data-cpb-dynamic="reviews"]'), heading: 'কাস্টমার রিভিউ' },
            { node: root.querySelector('#order_form'), heading: 'অর্ডার করতে নিচের ফর্মটি পূরণ করুন' }
        ].filter(block => block.node);

        let designHidden = false;

        function moveToPlain() {
            blocks.forEach(block => {
                block.parent = block.node.parentNode;
                block.next = block.node.nextSibling;
                const wrapper = document.createElement('section');
                wrapper.className = 'cpb-plain-block';
                const title = document.createElement('h2');
                title.className = 'cpb-plain-heading';
                title.textContent = block.heading;
                wrapper.appendChild(title);
                wrapper.appendChild(block.node);
                plainView.appendChild(wrapper);
                block.wrapper = wrapper;
            });
        }

        function restoreFromPlain() {
            blocks.slice().reverse().forEach(block => {
                if (!block.parent) return;
                block.parent.insertBefore(block.node, block.next);
                block.wrapper?.remove();
                block.wrapper = null;
            });
            plainView.replaceChildren();
        }

        function apply(hidden, persist) {
            if (hidden === designHidden) return;
            designHidden = hidden;
            if (hidden) { moveToPlain(); } else { restoreFromPlain(); }
            plainView.hidden = !hidden;
            root.classList.toggle('cpb-design-hidden', hidden);
            button.setAttribute('aria-pressed', hidden ? 'false' : 'true');
            if (label) label.textContent = hidden ? 'ডিজাইন দেখান' : 'ডিজাইন লুকান';
            button.title = hidden ? 'বিল্ডার ডিজাইন আবার দেখান' : 'বিল্ডার ডিজাইন লুকান';
            if (persist) {
                try { window.sessionStorage.setItem(storageKey, hidden ? '1' : '0'); } catch (_) {}
            }
        }

        button.addEventListener('click', () => apply(!designHidden, true));

        let stored = null;
        try { stored = window.sessionStorage.getItem(storageKey); } catch (_) {}
        if (stored === '1') apply(true, false);
    }

    function initAnalytics() {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({
            event: 'view_item_list',
            ecommerce: { currency: 'BDT', items: products.map((product, index) => ({ item_id: product.id, item_name: product.name, price: Number(product.price || 0), index, quantity: 1 })) }
        });
    }

    mountDynamicContent();
    bindEvents();
    initDesignToggle();
    initAnalytics();
})();
