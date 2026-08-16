/* Builds the static design preview (preview/www) from the real Blade templates. */
const fs = require('fs');
const path = require('path');
const { Compiler } = require('./compiler');
const { Collection, C } = require('./lib');
const data = require('./data');

const OUT = path.join(__dirname, 'www');
const REPO = path.join(__dirname, '..');

function pag(x) {
  const items = Array.isArray(x) ? x : (x.items || []);
  return { items, count: () => items.length, total: () => items.length, [Symbol.iterator]: function* () { yield* items; }, onEachSide: () => this, links: () => '', onEachSideSelf: true };
}
function paginator(x) {
  const items = Array.isArray(x) ? x : (x.items || []);
  return {
    items, count: () => items.length, total: () => items.length,
    [Symbol.iterator]: function* () { yield* items; },
    onEachSide() { return this; }, links() { return ''; },
  };
}

const pages = [
  { out: 'home.html', view: 'frontEnd.layouts.pages.index', title: 'Home' },
  { out: 'category.html', view: 'frontEnd.layouts.pages.category', title: 'Category', extra: { category: data.cat.fashion, products: paginator(data.cat.fashion.products.items), subcategories: data.cat.fashion.subcategories, min_price: 499, max_price: 2100 }, query: { subcategory: null, sort: null } },
  { out: 'subcategory.html', view: 'frontEnd.layouts.pages.subcategory', title: 'Subcategory', extra: { subcategory: data.subs.sMens, products: paginator([data.prods.pTeeBlack, data.prods.pTeeWhite, data.prods.pPolo, data.prods.pDenim]), impproducts: C([data.prods.pWatchPro, data.prods.pSneakWhite, data.prods.pHeadOver]), childcategories: data.subs.sMens.childcategories, min_price: 499, max_price: 1250 } },
  { out: 'childcategory.html', view: 'frontEnd.layouts.pages.childcategory', title: 'Child Category', extra: { childcategory: data.subs.sMens.childcategories.items[0], products: paginator([data.prods.pTeeBlack, data.prods.pTeeWhite]), impproducts: C([data.prods.pWatchPro, data.prods.pSneakWhite]), childcategories: data.subs.sMens.childcategories, min_price: 499, max_price: 550 } },
  { out: 'product.html', view: 'frontEnd.layouts.pages.details', title: 'Product Details', extra: { details: data.prods.pTeeBlack, products: C([data.prods.pTeeWhite, data.prods.pPolo, data.prods.pDenim, data.prods.pSneakWhite, data.prods.pRunner]), shippingcharge: data.shippingcharge, reviews: data.prods.pTeeBlack.reviews } },
  { out: 'search.html', view: 'frontEnd.layouts.pages.search', title: 'Search', extra: { products: paginator([data.prods.pTeeBlack, data.prods.pTeeWhite, data.prods.pPolo, data.prods.pDenim, data.prods.pSneakWhite]), keyword: 'tshirt' }, query: { keyword: 'tshirt' } },
  { out: 'cart.html', view: 'frontEnd.layouts.pages.cart', title: 'Cart', extra: { data: data.cartContent } },
  { out: 'checkout.html', view: 'frontEnd.layouts.customer.checkout', title: 'Checkout', extra: { shippingcharge: data.shippingcharge, bkash_gateway: data.bkash_gateway, shurjopay_gateway: data.shurjopay_gateway, uddoktapay_gateway: data.uddoktapay_gateway, aamarpay_gateway: data.aamarpay_gateway, advanceTotal: 0, hasAdvance: false, hasDigital: false, hasAllFreeDelivery: false } },
  { out: 'login.html', view: 'frontEnd.layouts.customer.login', title: 'Login' },
  { out: 'register.html', view: 'frontEnd.layouts.customer.register', title: 'Register' },
  { out: 'account.html', view: 'frontEnd.layouts.customer.account', title: 'My Dashboard' },
  { out: 'orders.html', view: 'frontEnd.layouts.customer.orders', title: 'My Orders', extra: { orders: paginator(data.orders) } },
  { out: 'order-track.html', view: 'frontEnd.layouts.customer.order_track', title: 'Track Order' },
  { out: 'tracking-result.html', view: 'frontEnd.layouts.customer.tracking_result', title: 'Tracking Result', extra: { order: data.orders } },
  { out: 'order-success.html', view: 'frontEnd.layouts.customer.order_success', title: 'Order Success', extra: { order: data.order } },
  { out: 'order-note.html', view: 'frontEnd.layouts.customer.order_note', title: 'Order Details', extra: { order: data.order } },
  { out: 'invoice.html', view: 'frontEnd.layouts.customer.invoice', title: 'Invoice', extra: { order: data.order } },
  { out: 'profile-edit.html', view: 'frontEnd.layouts.customer.profile_edit', title: 'Edit Profile', extra: { profile_edit: data.profile_edit, districts: data.districts, areas: data.areas } },
  { out: 'change-password.html', view: 'frontEnd.layouts.customer.change_password', title: 'Change Password' },
  { out: 'refunds.html', view: 'frontEnd.layouts.customer.refunds', title: 'Refunds', extra: { refunds: paginator(data.refunds) } },
  { out: 'refund-request.html', view: 'frontEnd.layouts.customer.refund_request', title: 'Refund Request', extra: { order: data.order } },
  { out: 'refund-details.html', view: 'frontEnd.layouts.customer.refund_details', title: 'Refund Details', extra: { refund: data.refunds.items[0] } },
  { out: 'flash-sale.html', view: 'frontEnd.layouts.pages.flashsales', title: 'Flash Sale', extra: { products: paginator([data.prods.pWatchPro, data.prods.pTeeBlack, data.prods.pSneakWhite, data.prods.pHeadOver, data.prods.pSkinSet, data.prods.pBottle]) } },
  { out: 'hot-deals.html', view: 'frontEnd.layouts.pages.hotdeals', title: 'Hot Deals', extra: { products: paginator(data.hotdeal_top) } },
  { out: 'sellers.html', view: 'frontEnd.layouts.pages.sellers', title: 'Sellers', extra: { vendors: paginator(data.vendors), generalsetting: data.generalsetting, seo: data.seo } },
  { out: 'vendor-shop.html', view: 'frontEnd.layouts.pages.vendor-shop', title: 'Vendor Shop', extra: { vendor: { ...data.vendors.items[0], total_products: 46 }, products: paginator([data.prods.pTeeBlack, data.prods.pTeeWhite, data.prods.pPolo, data.prods.pSneakWhite]), generalsetting: data.generalsetting } },
  { out: 'brand.html', view: 'frontEnd.layouts.pages.brand', title: 'Brand', extra: { brand: data.brands.items[0], products: paginator([data.prods.pTeeBlack, data.prods.pTeeWhite, data.prods.pPolo, data.prods.pDenim]) } },
  { out: 'blog.html', view: 'frontEnd.layouts.pages.blog.index', title: 'Blog', extra: { blogs: paginator(data.blogs) } },
  { out: 'blog-details.html', view: 'frontEnd.layouts.pages.blog.details', title: 'Blog Details', extra: { blog: data.blogs.items[0], recentBlogs: data.blogs } },
  { out: 'contact.html', view: 'frontEnd.layouts.pages.contact', title: 'Contact', extra: { contact: data.contact, cmnmenu: data.cmnmenu } },
  { out: 'complaint.html', view: 'frontEnd.layouts.pages.complaint', title: 'Complaint' },
  { out: 'page.html', view: 'frontEnd.layouts.pages.page', title: 'Static Page', extra: { page: { name: 'About Us', slug: 'about-us', details: '<p>Shop Genie started with one simple idea: online shopping in Bangladesh should be honest, fast and delightful. Today we serve thousands of customers every month with genuine products, fair prices and a support team that actually answers.</p>' } } },
  { out: 'offers.html', view: 'frontEnd.layouts.pages.offers', title: 'Offers' },
];

/* preview navigator injected into every page */
const NAV = `
<div id="pvBar" style="position:fixed;left:50%;transform:translateX(-50%);bottom:10px;z-index:99990;background:#0d1220;color:#fff;border-radius:999px;padding:8px 14px;display:flex;gap:6px;align-items:center;font:600 12px 'Manrope',sans-serif;box-shadow:0 10px 30px rgba(0,0,0,.4)">
  <span style="opacity:.55;margin-right:6px">PREVIEW:</span>
  <a href="home.html" style="color:#fff;text-decoration:none;padding:4px 10px;border-radius:99px" onmouseover="this.style.background='#e02b20'" onmouseout="this.style.background='transparent'">Home</a>
  <a href="category.html" style="color:#fff;text-decoration:none;padding:4px 10px;border-radius:99px" onmouseover="this.style.background='#e02b20'" onmouseout="this.style.background='transparent'">Category</a>
  <a href="product.html" style="color:#fff;text-decoration:none;padding:4px 10px;border-radius:99px" onmouseover="this.style.background='#e02b20'" onmouseout="this.style.background='transparent'">Product</a>
  <a href="cart.html" style="color:#fff;text-decoration:none;padding:4px 10px;border-radius:99px" onmouseover="this.style.background='#e02b20'" onmouseout="this.style.background='transparent'">Cart</a>
  <a href="checkout.html" style="color:#fff;text-decoration:none;padding:4px 10px;border-radius:99px" onmouseover="this.style.background='#e02b20'" onmouseout="this.style.background='transparent'">Checkout</a>
  <a href="login.html" style="color:#fff;text-decoration:none;padding:4px 10px;border-radius:99px" onmouseover="this.style.background='#e02b20'" onmouseout="this.style.background='transparent'">Login</a>
  <a href="account.html" style="color:#fff;text-decoration:none;padding:4px 10px;border-radius:99px" onmouseover="this.style.background='#e02b20'" onmouseout="this.style.background='transparent'">Account</a>
  <select onchange="location.href=this.value" style="background:#1c2438;color:#fff;border:1px solid #33406b;border-radius:99px;padding:4px 8px;font:inherit">
    <option value="">All pages…</option>
    ${pages.map(p => `<option value="${p.out}">${p.title}</option>`).join('')}
  </select>
</div>
`;

function build() {
  fs.rmSync(OUT, { recursive: true, force: true });
  fs.mkdirSync(OUT, { recursive: true });

  /* copy assets */
  fs.cpSync(path.join(__dirname, 'assets'), path.join(OUT, 'assets'), { recursive: true });
  fs.cpSync(path.join(REPO, 'public', 'frontEnd', 'css'), path.join(OUT, 'public', 'frontEnd', 'css'), { recursive: true });
  fs.cpSync(path.join(REPO, 'public', 'frontEnd', 'js'), path.join(OUT, 'public', 'frontEnd', 'js'), { recursive: true });
  fs.cpSync(path.join(REPO, 'public', 'frontEnd', 'images'), path.join(OUT, 'public', 'frontEnd', 'images'), { recursive: true });
  fs.cpSync(path.join(REPO, 'public', 'backEnd', 'assets', 'css', 'toastr.min.css'), path.join(OUT, 'public', 'backEnd', 'assets', 'css', 'toastr.min.css'));
  fs.cpSync(path.join(REPO, 'public', 'backEnd', 'assets', 'js', 'toastr.min.js'), path.join(OUT, 'public', 'backEnd', 'assets', 'js', 'toastr.min.js'));
  fs.cpSync(path.join(REPO, 'public', 'uploads', 'preview'), path.join(OUT, 'public', 'uploads', 'preview'), { recursive: true });
  fs.cpSync(path.join(REPO, 'public', 'uploads', 'app.png'), path.join(OUT, 'public', 'uploads', 'app.png'));
  fs.cpSync(path.join(REPO, 'public', 'uploads', 'play.svg'), path.join(OUT, 'public', 'uploads', 'play.svg'));

  const nav = NAV;

  /* ---------- quick-order popup demo data (window.CDP) ----------
     Production-এ /quick-order/{id} endpoint থেকে ডাটা আসে। স্ট্যাটিক
     প্রিভিউতে সেটা না-থাকায়, প্রতিটি পেজে product data embed করা হয়
     যাতে "Order Now" ক্লিকে পপআপ রিয়েল ডাটা নিয়ে খুলে। ---------- */
  function cdpFor(p) {
    if (!p || p.id == null) return null;
    const variants = Array.from(p.variantPrices || []).map(v => ({ s: v.size_id, c: v.color_id, p: v.price, st: v.stock }));
    const prosizes = Array.from(p.prosizes || []);
    const procolors = Array.from(p.procolors || []);
    const sizes = {}, colors = {};
    variants.forEach(v => {
      if (v.s != null) {
        if (!sizes[v.s]) {
          const sz = prosizes.find(s => s.id === v.s);
          sizes[v.s] = { id: v.s, name: sz ? sz.sizeName : 'S', stock: 0, has_stock: false };
        }
        if (v.st != null) { sizes[v.s].stock += Math.max(0, Number(v.st)); sizes[v.s].has_stock = true; }
      }
      if (v.c != null) {
        const cl = procolors.find(c => c.id === v.c);
        colors[v.c] = { id: v.c, name: cl ? cl.colorName : 'Color', hex: cl ? cl.color : '#cccccc' };
      }
    });
    return {
      id: p.id,
      name: p.name,
      img: (p.image && p.image.image) || '',
      url: '/product/' + p.slug,
      price: Number(p.new_price) || 0,
      old: Number(p.old_price) || 0,
      stock: Number(p.stock) || 0,
      shipping: p.is_digital ? 0 : 60,
      sizes: Object.values(sizes),
      colors: Object.values(colors),
      variants,
    };
  }
  const allProds = [];
  const seen = new Set();
  const collect = (list) => {
    const arr = Array.isArray(list) ? list : (list && typeof list[Symbol.iterator] === 'function' ? Array.from(list) : (list && Array.isArray(list.items) ? list.items : []));
    arr.forEach(p => {
      if (p && p.id != null && !seen.has(p.id)) { seen.add(p.id); allProds.push(p); }
    });
  };
  collect(Object.values(data.prods || {}));
  collect(data.all_products || []);
  collect(data.flas_sales || []);
  collect(data.hotdeal_top || []);
  collect(data.hotdeal_bottom || []);
  Array.from(data.homeproducts || []).forEach(sec => collect(sec.products || []));
  Object.values(data.cat || {}).forEach(cat => collect(cat.products ? cat.products.items || [] : []));
  const cdpEntries = allProds.map(cdpFor).filter(Boolean)
    .map(p => `  window.CDP[${JSON.stringify(String(p.id))}] = ${JSON.stringify(p)};`).join('\n');
  const CDP_SCRIPT = `<script>\n  window.CDP = window.CDP || {};\n${cdpEntries}\n</script>`;

  const indexLinks = pages.map(p => `<a class="card" href="${p.out}"><b>${p.out.replace('.html', '').replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}</b><span>${p.title}</span></a>`).join('');

  for (const page of pages) {
    const compiler = new Compiler(data, page.query);
    try {
      let html = compiler.render(page.view, page.extra, page.query);
      html = html.replace('</body>', CDP_SCRIPT + '\n' + nav + '\n</body>');
      fs.writeFileSync(path.join(OUT, page.out), html);
      console.log('✓', page.out, '(', Math.round(html.length / 1024), 'KB )');
    } catch (err) {
      console.error('✗ FAILED', page.out, '—', err.message);
      fs.writeFileSync(path.join(OUT, page.out + '.error.txt'), err.stack || String(err));
    }
  }

  /* index page */
  fs.writeFileSync(path.join(OUT, 'index.html'), `<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Shop Genie — Redesign Preview</title>
<style>
  body{font-family:'Manrope',system-ui,sans-serif;background:#f5f6f9;margin:0;padding:48px 24px;color:#16192c}
  .wrap{max-width:1100px;margin:0 auto}
  h1{font-size:28px;margin:0 0 6px}
  p.sub{color:#5c6270;margin:0 0 30px;font-size:15px}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
  a.card{background:#fff;border:1px solid #e4e7ee;border-radius:14px;padding:18px 20px;text-decoration:none;color:#16192c;transition:.2s;display:block}
  a.card:hover{border-color:#1c2a50;box-shadow:0 8px 24px rgba(16,24,40,.1);transform:translateY(-2px)}
  a.card b{display:block;font-size:15px;margin-bottom:4px}
  a.card span{font-size:12.5px;color:#8b91a0}
  .note{background:#eef2fb;border:1px solid #d9e1f5;border-radius:12px;padding:14px 18px;font-size:13.5px;color:#1c2a50;margin-bottom:28px;line-height:1.6}
</style></head><body>
<div class="wrap">
  <h1>🎨 Shop Genie — Storefront Redesign</h1>
  <p class="sub">Professional UI/UX redesign of every storefront page. Click any page to preview.</p>
  <div class="note">These preview pages are compiled from the <b>real Blade templates</b> in <code>resources/views/frontEnd</code> with sample data — the same files your Laravel site will use in production. Use the floating PREVIEW bar on each page to jump around.</div>
  <div class="grid">
    <a class="card" href="home.html"><b>🏠 Home</b><span>Hero, flash sale, categories, brands, shops</span></a>
    ${indexLinks}
  </div>
</div>
</body></html>`);
  console.log('✓ built', pages.length, 'pages →', OUT);
}

build();
