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

        root.querySelectorAll('[data-cpb-youtube]').forEach(mountYoutube);
        initializeCountdowns();
        initializeVariantPicker(selectedProductId);
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

    function initializeVariantPicker(productId) {
        const picker = root.querySelector('#cpb-variant-picker');
        if (!picker) return;
        const product = findProduct(productId);
        const sizes = uniqueOptions(product?.sizes || [], product?.variants || [], 'size_id');
        const colors = uniqueOptions(product?.colors || [], product?.variants || [], 'color_id');
        const sizeField = picker.querySelector('[data-size-field]');
        const colorField = picker.querySelector('[data-color-field]');
        fillSelect(picker.querySelector('[data-product-size]'), sizes, 'সাইজ নির্বাচন করুন');
        fillSelect(picker.querySelector('[data-product-color]'), colors, 'কালার নির্বাচন করুন');
        sizeField.hidden = sizes.length === 0;
        colorField.hidden = colors.length === 0;
        picker.hidden = sizes.length === 0 && colors.length === 0;
        variantSelectionSynced = sizes.length === 0 && colors.length === 0;
    }

    function uniqueOptions(options, variants, relationKey) {
        const map = new Map((options || []).filter(item => item.id).map(item => [String(item.id), item]));
        (variants || []).forEach(variant => {
            const id = variant[relationKey];
            if (id && !map.has(String(id))) map.set(String(id), { id: String(id), name: relationKey === 'size_id' ? 'Size ' + id : 'Color ' + id });
        });
        return Array.from(map.values());
    }

    function fillSelect(select, options, placeholder) {
        if (!select) return;
        select.innerHTML = '<option value="">' + escapeHtml(placeholder) + '</option>' + options.map(option => {
            const isOut = option.has_stock && Number(option.stock) <= 0;
            const label = option.name + (option.has_stock ? (isOut ? ' — স্টক শেষ' : ' (' + option.stock + ' টি আছে)') : '');
            return '<option value="' + escapeHtml(option.id) + '" data-name="' + escapeHtml(option.name) + '"' + (isOut ? ' disabled' : '') + '>' + escapeHtml(label) + '</option>';
        }).join('');
        select.required = options.length > 0;
    }

    function findProduct(id) {
        return products.find(product => String(product.id) === String(id));
    }

    async function selectProduct(productId, moveToCheckout) {
        productId = String(productId);
        if (!productId || requestInProgress) return;
        setBusy(true);
        try {
            const html = await requestHtml(root.dataset.changeProductUrl, { id: productId });
            selectedProductId = productId;
            replaceCart(html);
            updateSelectedCards(productId);
            initializeVariantPicker(productId);
            trackAddToCart(productId);
            document.dispatchEvent(new CustomEvent('campaign:product-selected', { detail: { product: findProduct(productId) } }));
            if (moveToCheckout) root.querySelector('#order_form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            showToast(error.message || 'পণ্য নির্বাচন করা যায়নি। আবার চেষ্টা করুন।');
        } finally { setBusy(false); }
    }

    async function updateVariant() {
        if (requestInProgress) return;
        const picker = root.querySelector('#cpb-variant-picker');
        const size = picker?.querySelector('[data-product-size]');
        const color = picker?.querySelector('[data-product-color]');
        const rowId = root.querySelector('.cartlist .cart_increment, .cartlist .cart_decrement')?.dataset.id;
        if (!rowId) return;
        const sizeId = size?.value || '';
        const colorId = color?.value || '';
        const sizeName = size?.selectedOptions?.[0]?.dataset.name || '';
        const colorName = color?.selectedOptions?.[0]?.dataset.name || '';
        const requiredSelections = Array.from(picker.querySelectorAll('select[required]'));
        variantSelectionSynced = false;
        if (requiredSelections.some(select => !select.value)) return;
        setBusy(true);
        try {
            const html = await requestHtml(root.dataset.cartUpdateUrl, {
                id: rowId, product_size: sizeName, product_color: colorName,
                product_size_id: sizeId, product_color_id: colorId
            });
            replaceCart(html);
            variantSelectionSynced = true;
        } catch (error) { showToast(error.message || 'ভ্যারিয়েন্ট আপডেট করা যায়নি।'); }
        finally { setBusy(false); }
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

    function showToast(message) {
        window.clearTimeout(toastTimer);
        toastElement.textContent = message;
        toastElement.classList.add('is-visible');
        toastTimer = window.setTimeout(() => toastElement.classList.remove('is-visible'), 4200);
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
    }

    function trackAddToCart(productId) {
        const product = findProduct(productId) || {};
        const item = { item_id: String(productId), item_name: product.name || '', price: Number(product.price || 0), quantity: 1 };
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ ecommerce: null });
        window.dataLayer.push({ event: 'add_to_cart', ecommerce: { currency: 'BDT', value: item.price, items: [item] } });
        if (typeof window.fbq === 'function') {
            window.fbq('track', 'AddToCart', { content_ids: [item.item_id], content_name: item.item_name, content_type: 'product', value: item.price, currency: 'BDT' }, { eventID: 'atc_' + item.item_id + '_' + Math.floor(Date.now() / 1000) });
        }
        if (typeof window.ttq !== 'undefined' && typeof window.ttq.track === 'function') {
            window.ttq.track('AddToCart', { content_id: item.item_id, content_name: item.item_name, content_type: 'product', value: item.price, currency: 'BDT', quantity: 1 });
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

            const orderLink = event.target.closest('a[href="#order_form"], .cam_order_now');
            if (orderLink) trackOrderClick();
        });

        document.addEventListener('change', event => {
            if (event.target.matches('#cpb-area')) updateShipping(event.target.value);
            if (event.target.matches('[data-product-size], [data-product-color]')) updateVariant();
        });

        document.addEventListener('submit', event => {
            const form = event.target.closest('[data-cpb-order-form]');
            if (!form) return;

            if (requestInProgress) {
                event.preventDefault();
                showToast('প্রোডাক্ট আপডেট হচ্ছে—এক মুহূর্ত পরে আবার অর্ডার করুন।');
                return;
            }

            const missingVariant = Array.from(root.querySelectorAll('#cpb-variant-picker select[required]')).find(select => !select.value);
            if (missingVariant) {
                event.preventDefault();
                showToast('অর্ডার করার আগে প্রোডাক্টের সাইজ ও কালার নির্বাচন করুন।');
                missingVariant.focus();
                missingVariant.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            if (!variantSelectionSynced) {
                event.preventDefault();
                showToast('এই সাইজ ও কালারের সমন্বয়টি নিশ্চিত করা যায়নি। অন্য অপশন বেছে আবার চেষ্টা করুন।');
                root.querySelector('#cpb-variant-picker')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
    initAnalytics();
})();
