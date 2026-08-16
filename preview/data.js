/* Mock data for the static design preview (mirrors Shop Genie's DB shapes). */
const { C } = require('./lib');

const P = (n) => '/preview/assets/products/' + n;   // product image path helper
const B = (n) => '/preview/assets/banners/' + n;

/* ---------- settings ---------- */
const generalsetting = {
  name: 'Shop Genie',
  white_logo: '/public/uploads/preview/logo-white.svg',
  dark_logo: '/public/uploads/preview/logo.svg',
  favicon: '/public/uploads/preview/logo.svg',
  primary_color: '#1C2A50',
  secodery_color: '#E02B20',
  footer_color: '#141c36',
  copyright_color: '#10172e',
  top_headline: 'Free delivery on orders over ৳1,000 — today only!',
  news_ticker_enabled: 1,
  quick_order_popup_enabled: 1,
  quick_order_popup_title: '🛒 দ্রুত অর্ডার করুন',
  quick_order_confirm_text: 'অর্ডার কনফার্ম করুন →',
  quick_order_cart_text: 'কার্টে রাখুন',
  quick_order_cart_toast: 'কার্টে যোগ হয়েছে ✔',
  flash_sale_end_date: '2027-11-09',
  hot_deal_end_date: '2027-11-10',
  show_all_products: 1,
  show_category_wise_products: 1,
  vendor_enabled: 1,
  reseller_enabled: 1,
  google_play_link: '#',
  app_store_link: '#',
  og_baner: B('b1.jpg'),
  footer_about_text: 'Shop Genie is your trusted online shopping destination — quality products, honest prices and fast delivery across Bangladesh.',
};

const seo = { meta_title: 'Shop Genie — Online Shopping in Bangladesh', meta_description: 'Shop fashion, electronics, home essentials and more at the best prices with fast delivery across Bangladesh.', meta_tags: 'online shopping, bangladesh, shop genie', search_console_verification: '' };

const contact = { hotline: '09678-112233', whatsapp: '8801712345678', email: 'support@shopgenie.com.bd', address: 'House 12, Road 7, Dhanmondi, Dhaka 1205' };

const socialicons = C([
  { icon: 'fa-brands fa-facebook-f', link: '#' },
  { icon: 'fa-brands fa-youtube', link: '#' },
  { icon: 'fa-brands fa-instagram', link: '#' },
  { icon: 'fa-brands fa-tiktok', link: '#' },
]);

const pages = C([
  { name: 'About Us', slug: 'about-us' },
  { name: 'Privacy Policy', slug: 'privacy-policy' },
  { name: 'Terms & Conditions', slug: 'terms-conditions' },
]);
const pagesright = C([
  { name: 'Refund & Return Policy', slug: 'refund-policy' },
  { name: 'Shipping Policy', slug: 'shipping-policy' },
  { name: 'FAQ', slug: 'faq' },
]);
const cmnmenu = pages;

/* ---------- reviews helper ---------- */
const rev = (...ratings) => C(ratings.map((r, i) => ({ id: i + 1, ratting: r, name: ['Rahim U.', 'Sadia K.', 'Tanvir A.', 'Nusrat J.'][i % 4], review: ['Excellent quality, exactly as described. Delivery was super fast!', 'Very good product for the price. Will buy again.', 'Good but the color is slightly different from the photo.', 'Best purchase of the month. Highly recommended.'][i % 4], created_at: new Date(2026, 7, 10 - i), image: null })));

/* ---------- categories ---------- */
const cat = (id, name, slug, icon) => ({ id, name, slug, icon, image: icon, status: 1, parent_id: 0 });

const fashion = cat(1, 'Fashion', 'fashion', P('p01.jpg'));
const electronics = cat(2, 'Electronics', 'electronics', P('p13.jpg'));
const home = cat(3, 'Home & Living', 'home-living', P('p09.jpg'));
const beauty = cat(4, 'Beauty & Care', 'beauty-care', P('p11.jpg'));
const sports = cat(5, 'Sports & Fitness', 'sports-fitness', P('p03.jpg'));
const kids = cat(6, "Kids & Toys", 'kids-toys', P('p05.jpg'));

const sub = (id, cat, name, slug) => ({ id, subcategoryName: name, slug, category_id: cat.id, childcategories: C([]) });
const child = (id, sub, name, slug) => ({ id, childcategoryName: name, slug, subcategory_id: sub.id });

const sMens = sub(1, fashion, "Men's Clothing", 'mens-clothing');
const sShoes = sub(2, fashion, 'Shoes', 'shoes');
const sWear = sub(3, electronics, 'Wearables', 'wearables');
const sAudio = sub(4, electronics, 'Audio', 'audio');
const sDecor = sub(5, home, 'Home Decor', 'home-decor');
const sSkin = sub(6, beauty, 'Skincare', 'skincare');

sMens.childcategories = C([child(1, sMens, 'T-Shirts', 't-shirts'), child(2, sMens, 'Polo Shirts', 'polo-shirts')]);
sShoes.childcategories = C([child(3, sShoes, 'Sneakers', 'sneakers'), child(4, sShoes, 'Sandals', 'sandals')]);
sWear.childcategories = C([child(5, sWear, 'Smart Watches', 'smart-watches')]);
sAudio.childcategories = C([child(6, sAudio, 'Headphones', 'headphones'), child(7, sAudio, 'Earbuds', 'earbuds')]);
sDecor.childcategories = C([child(8, sDecor, 'Wall Art', 'wall-art')]);
sSkin.childcategories = C([child(9, sSkin, 'Face Care', 'face-care')]);

fashion.subcategories = C([sMens, sShoes]);
electronics.subcategories = C([sWear, sAudio]);
home.subcategories = C([sDecor]);
beauty.subcategories = C([sSkin]);
sports.subcategories = C([]);
kids.subcategories = C([]);

const menucategories = C([fashion, electronics, home, beauty, sports, kids]);
const frontcategory = C([fashion, electronics, home, beauty, sports, kids]);

/* ---------- sizes / colors ---------- */
const sizes = {
  1: { id: 1, sizeName: 'S' }, 2: { id: 2, sizeName: 'M' }, 3: { id: 3, sizeName: 'L' }, 4: { id: 4, sizeName: 'XL' },
  5: { id: 5, sizeName: '40' }, 6: { id: 6, sizeName: '41' }, 7: { id: 7, sizeName: '42' }, 8: { id: 8, sizeName: '43' },
};
const colors = {
  1: { id: 1, colorName: 'Black', color: '#22262e' },
  2: { id: 2, colorName: 'White', color: '#f4f4f4' },
  3: { id: 3, colorName: 'Navy', color: '#1C2A50' },
  4: { id: 4, colorName: 'Red', color: '#E02B20' },
};

/* ---------- products ---------- */
let pid = 0;
const prod = (opts) => {
  pid += 1;
  const old = opts.old_price || 0;
  const sizesSel = (opts.sizes || []).map(id => sizes[id]);
  const colorsSel = (opts.colors || []).map(id => colors[id]);
  /* সাইজ/কালার থাকলে সব কম্বিনেশনের ভ্যারিয়েন্ট অটো-জেনারেট (quick-order পপআপ ও details পেজের জন্য) */
  const autoVariants = () => {
    if (sizesSel.length && colorsSel.length) {
      return sizesSel.flatMap(s => colorsSel.map(c => ({ s: s.id, c: c.id, p: opts.new_price, st: opts.stock })));
    }
    if (sizesSel.length) return sizesSel.map(s => ({ s: s.id, c: null, p: opts.new_price, st: opts.stock }));
    if (colorsSel.length) return colorsSel.map(c => ({ s: null, c: c.id, p: opts.new_price, st: opts.stock }));
    return [];
  };
  const variantPrices = C((opts.variants || autoVariants()).map(v => ({
    size_id: v.s, color_id: v.c, price: v.p, stock: v.st,
    size: v.s ? sizes[v.s] : null, color: v.c ? colors[v.c] : null,
  })));
  return {
    id: pid, name: opts.name, slug: opts.slug, new_price: opts.new_price, old_price: old,
    sold: opts.sold || Math.floor(Math.random() * 400), stock: opts.stock ?? 15,
    image: { image: opts.img }, images: C([{ image: opts.img, color_id: null }, ...(opts.extraImgs || []).map(i => ({ image: i, color_id: null }))]),
    reviews: rev(...(opts.ratings || [5, 4, 5, 4, 5])), prosizes: C(sizesSel), procolors: C(colorsSel),
    variantPrices, category: opts.category, subcategory: opts.subcategory || null, childcategory: opts.childcategory || null,
    brand: opts.brand || null, is_digital: opts.is_digital || 0, free_delivery: opts.free_delivery || 0,
    short_description: opts.short || `<p>Premium quality product with excellent build, designed for everyday use. Backed by Shop Genie's quality promise and 7-day easy return.</p>`,
    description: opts.desc || `<h3>Product Details</h3><p>Premium quality product with excellent build, designed for everyday use. Crafted from carefully selected materials to deliver comfort, durability and style.</p><ul><li>High-grade materials</li><li>Ergonomic design</li><li>Easy to clean & maintain</li></ul><p>Backed by Shop Genie's quality promise and 7-day easy return.</p>`,
    product_code: 'SG-' + (1000 + pid), meta_title: null, meta_description: null, meta_keywords: null, meta_image: null,
    is_wholesale: opts.wholesale || 0, wholesalePrices: opts.wholesale ? C([{ min_quantity: 5, max_quantity: 19, wholesale_price: Math.round(opts.new_price * 0.92), stock: 120 }, { min_quantity: 20, max_quantity: null, wholesale_price: Math.round(opts.new_price * 0.85), stock: 300 }]) : C([]),
    pro_unit: null, pro_video: null, pro_video_type: null, pro_video_path: null,
    created_at: new Date(2026, 7, 1),
  };
};

const pTeeBlack = prod({ name: 'Premium Cotton T-Shirt — Soft Touch Black', slug: 'premium-cotton-tshirt-black', new_price: 550, old_price: 850, img: P('p01.jpg'), extraImgs: [P('p02.jpg'), P('p04.jpg')], category: fashion, subcategory: sMens, childcategory: sMens.childcategories.items[0], sizes: [1, 2, 3, 4], colors: [1, 2, 3], variants: [{ s: 1, c: 1, p: 550, st: 12 }, { s: 2, c: 1, p: 550, st: 8 }, { s: 3, c: 1, p: 570, st: 5 }, { s: 4, c: 1, p: 580, st: 0 }, { s: 1, c: 2, p: 540, st: 10 }, { s: 2, c: 2, p: 540, st: 14 }, { s: 1, c: 3, p: 560, st: 6 }], sold: 320, stock: 55, ratings: [5, 4, 5, 5] });
const pTeeWhite = prod({ name: 'Classic White T-Shirt — Regular Fit', slug: 'classic-white-tshirt', new_price: 499, old_price: 750, img: P('p03.jpg'), category: fashion, subcategory: sMens, childcategory: sMens.childcategories.items[0], sizes: [1, 2, 3], colors: [2, 3], ratings: [4, 5, 4], sold: 280, stock: 24 });
const pPolo = prod({ name: 'Men\'s Polo Shirt — Breathable Pique', slug: 'mens-polo-shirt', new_price: 690, old_price: 990, img: P('p02.jpg'), category: fashion, subcategory: sMens, childcategory: sMens.childcategories.items[1], sizes: [1, 2, 3, 4], colors: [3, 4], ratings: [5, 4, 4], sold: 150, stock: 9 });
const pDenim = prod({ name: 'Slim Fit Denim Shirt — Indigo Wash', slug: 'slim-fit-denim-shirt', new_price: 1250, old_price: 1800, img: P('p04.jpg'), category: fashion, subcategory: sMens, childcategory: sMens.childcategories.items[1], sizes: [1, 2, 3], colors: [3], ratings: [5, 5, 4, 5], sold: 96, stock: 4 });
const pSneakWhite = prod({ name: 'Urban White Sneakers — Everyday Comfort', slug: 'urban-white-sneakers', new_price: 1650, old_price: 2400, img: P('p06.jpg'), extraImgs: [P('p07.jpg'), P('p08.jpg')], category: fashion, subcategory: sShoes, childcategory: sShoes.childcategories.items[0], sizes: [5, 6, 7, 8], colors: [2, 1], variants: [{ s: 5, c: 2, p: 1650, st: 7 }, { s: 6, c: 2, p: 1650, st: 11 }, { s: 7, c: 2, p: 1680, st: 9 }, { s: 8, c: 2, p: 1720, st: 3 }, { s: 6, c: 1, p: 1700, st: 5 }], ratings: [5, 5, 4], sold: 430, stock: 35 });
const pSneakCanvas = prod({ name: 'Canvas Casual Sneakers — Retro Style', slug: 'canvas-casual-sneakers', new_price: 1150, old_price: 1500, img: P('p07.jpg'), category: fashion, subcategory: sShoes, childcategory: sShoes.childcategories.items[0], sizes: [5, 6, 7], colors: [2], ratings: [4, 4, 5], sold: 205, stock: 18 });
const pRunner = prod({ name: 'Pro Running Shoes — Lightweight Mesh', slug: 'pro-running-shoes', new_price: 2100, old_price: 3100, img: P('p08.jpg'), category: fashion, subcategory: sShoes, childcategory: sShoes.childcategories.items[0], sizes: [5, 6, 7, 8], colors: [1, 4], ratings: [5, 5, 5, 4], sold: 510, stock: 2 });
const pSandal = prod({ name: 'Comfort Sandals — Anti-Skid Sole', slug: 'comfort-sandals', new_price: 850, old_price: 1200, img: P('p05.jpg'), category: fashion, subcategory: sShoes, childcategory: sShoes.childcategories.items[1], sizes: [5, 6, 7], colors: [1], ratings: [4, 4], sold: 130, stock: 0 });

const pWatchPro = prod({ name: 'Smart Watch Pro X — AMOLED, Heart Rate, IP68', slug: 'smart-watch-pro-x', new_price: 2450, old_price: 3800, img: P('p14.jpg'), extraImgs: [P('p15.jpg')], category: electronics, subcategory: sWear, childcategory: sWear.childcategories.items[0], wholesale: 1, ratings: [5, 5, 4, 5, 5], sold: 760, stock: 42 });
const pWatchFit = prod({ name: 'Fit Band S2 — Sleep & Step Tracker', slug: 'fit-band-s2', new_price: 1350, old_price: 1900, img: P('p15.jpg'), category: electronics, subcategory: sWear, childcategory: sWear.childcategories.items[0], colors: [1, 2], ratings: [4, 5, 4], sold: 390, stock: 11 });
const pWatchMini = prod({ name: 'Mini Smart Watch — Kids Edition', slug: 'mini-smart-watch', new_price: 990, old_price: 1400, img: P('p13.jpg'), category: electronics, subcategory: sWear, childcategory: sWear.childcategories.items[0], ratings: [4, 4, 5], sold: 120, stock: 16 });

const pHeadOver = prod({ name: 'Studio Over-Ear Headphones — Noise Cancelling', slug: 'studio-over-ear-headphones', new_price: 3250, old_price: 4600, img: P('p18.jpg'), category: electronics, subcategory: sAudio, childcategory: sAudio.childcategories.items[0], wholesale: 1, ratings: [5, 5, 5, 4], sold: 610, stock: 21 });
const pHeadLight = prod({ name: 'Light Wireless Headphones — 30h Battery', slug: 'light-wireless-headphones', new_price: 1850, old_price: 2600, img: P('p16.jpg'), category: electronics, subcategory: sAudio, childcategory: sAudio.childcategories.items[0], ratings: [4, 5, 5], sold: 260, stock: 14 });
const pBud = prod({ name: 'True Wireless Earbuds — TWS 5.3', slug: 'true-wireless-earbuds', new_price: 1250, old_price: 1800, img: P('p17.jpg'), category: electronics, subcategory: sAudio, childcategory: sAudio.childcategories.items[1], ratings: [4, 4, 5, 4], sold: 890, stock: 60 });

const pLamp = prod({ name: 'Minimal Table Lamp — Warm LED', slug: 'minimal-table-lamp', new_price: 1450, old_price: 0, img: P('p09.jpg'), category: home, subcategory: sDecor, childcategory: sDecor.childcategories.items[0], ratings: [5, 4], sold: 75, stock: 8 });
const pPlanter = prod({ name: 'Ceramic Planter Set — Nordic Design', slug: 'ceramic-planter-set', new_price: 1100, old_price: 1600, img: P('p10.jpg'), category: home, subcategory: sDecor, childcategory: sDecor.childcategories.items[0], ratings: [5, 5, 4], sold: 140, stock: 12 });
const pSkinSet = prod({ name: 'Vitamin C Face Serum — Brightening', slug: 'vitamin-c-face-serum', new_price: 780, old_price: 1100, img: P('p11.jpg'), category: beauty, subcategory: sSkin, childcategory: sSkin.childcategories.items[0], ratings: [4, 5, 5], sold: 520, stock: 33 });
const pSunscreen = prod({ name: 'SPF 50 Sunscreen — Oil Free', slug: 'spf-50-sunscreen', new_price: 640, old_price: 900, img: P('p12.jpg'), category: beauty, subcategory: sSkin, childcategory: sSkin.childcategories.items[0], ratings: [5, 4, 5], sold: 300, stock: 25 });
const pBottle = prod({ name: 'Insulated Steel Bottle — 750ml', slug: 'insulated-steel-bottle', new_price: 900, old_price: 1300, img: P('p19.jpg'), category: sports, ratings: [5, 5], sold: 210, stock: 30 });
const pBag = prod({ name: 'Gym Duffel Bag — 30L Waterproof', slug: 'gym-duffel-bag', new_price: 1150, old_price: 1600, img: P('p20.jpg'), category: sports, ratings: [4, 5], sold: 165, stock: 7 });

fashion.products = C([pTeeBlack, pTeeWhite, pPolo, pDenim, pSneakWhite, pSneakCanvas, pRunner]);
electronics.products = C([pWatchPro, pWatchFit, pWatchMini, pHeadOver, pHeadLight, pBud]);
home.products = C([pLamp, pPlanter]);
beauty.products = C([pSkinSet, pSunscreen]);
sports.products = C([pBottle, pBag]);
kids.products = C([]);
frontcategory.items.forEach(c => { c.products = c.products || C([]); });

const allProducts = [pTeeBlack, pTeeWhite, pPolo, pDenim, pSneakWhite, pSneakCanvas, pRunner, pSandal, pWatchPro, pWatchFit, pWatchMini, pHeadOver, pHeadLight, pBud, pLamp, pPlanter, pSkinSet, pSunscreen, pBottle, pBag];

/* ---------- banners ---------- */
const sliders = C([
  { id: 1, image: B('b1.jpg'), link: '#' },
  { id: 2, image: B('b4.jpg'), link: '#' },
]);
const campaognads = C([{ id: 3, image: B('b2.jpg'), link: '#' }]);
const sliderbottomads = C([{ id: 4, image: B('b3.jpg'), link: '#' }, { id: 5, image: B('b1.jpg'), link: '#' }]);
const homepageads = C([{ id: 6, image: B('b3.jpg'), link: '#' }]);
const homepageads2 = C([{ id: 7, image: B('b4.jpg'), link: '#' }]);
const hitdealsbaner = C([{ id: 8, image: B('b2.jpg'), link: '#' }]);
const footertopads = C([]);
const reviews = C([]);

/* ---------- brands / blogs / vendors ---------- */
const brands = C([
  { id: 1, name: 'UrbanWear', slug: 'urbanwear', image: P('p02.jpg') },
  { id: 2, name: 'StepOne', slug: 'stepone', image: P('p07.jpg') },
  { id: 3, name: 'TechNova', slug: 'technova', image: P('p14.jpg') },
  { id: 4, name: 'SoundLabs', slug: 'soundlabs', image: P('p17.jpg') },
  { id: 5, name: 'HomeNest', slug: 'homenest', image: P('p09.jpg') },
  { id: 6, name: 'GlowCare', slug: 'glowcare', image: P('p11.jpg') },
]);

const blogs = C([
  { id: 1, title: '10 Style Tips to Look Sharp This Season', slug: 'style-tips-season', image: P('p04.jpg'), description: 'Fashion moves fast. Here are ten easy, affordable style upgrades that will instantly refresh your wardrobe — from layering basics to picking the right sneakers for every outfit.', created_at: new Date(2026, 7, 2) },
  { id: 2, title: 'Smartwatch Buying Guide 2026: What Matters Most', slug: 'smartwatch-buying-guide-2026', image: P('p14.jpg'), description: 'Battery life, display, health tracking or notifications? We break down exactly what to check before buying a smartwatch so you never overpay for features you won\'t use.', created_at: new Date(2026, 6, 24) },
  { id: 3, title: 'How We Deliver in 24 Hours Across Bangladesh', slug: '24-hour-delivery', image: P('p06.jpg'), description: 'Ever wondered how your order reaches you so fast? A behind-the-scenes look at our warehouses, courier partners and quality checks that make same-week delivery possible.', created_at: new Date(2026, 6, 10) },
]);

const vendors = C([
  { id: 1, shop_name: 'UrbanWear BD', slug: 'urbanwear-bd', logo: P('p02.jpg'), banner: B('b2.jpg'), average_rating: 4.8, total_reviews: 214, products_count: 46, verification_status: 'approved' },
  { id: 2, shop_name: 'TechNova Store', slug: 'technova-store', logo: P('p14.jpg'), banner: B('b1.jpg'), average_rating: 4.6, total_reviews: 158, products_count: 32, verification_status: 'approved' },
  { id: 3, shop_name: 'HomeNest Living', slug: 'homenest-living', logo: P('p09.jpg'), banner: B('b3.jpg'), average_rating: 4.7, total_reviews: 96, products_count: 25, verification_status: 'approved' },
]);

/* ---------- hot deal / flash ---------- */
const flas_sales = C([pWatchPro, pTeeBlack, pSneakWhite, pHeadOver, pSkinSet, pBottle, pTeeWhite, pRunner]);
const hotdeal_top = C([pSneakWhite, pWatchPro, pHeadOver, pTeeBlack, pBud, pSkinSet, pLamp, pPolo]);
const hotdeal_bottom = C([pSneakCanvas, pWatchFit, pHeadLight, pDenim, pPlanter, pSunscreen]);
const homeproducts = C([fashion, electronics, home].map(c => ({ ...c, products: c.products })));
const all_products = C(allProducts.slice(0, 12));

/* ---------- customer / cart / session ---------- */
const customer = { id: 7, name: 'Rahim Uddin', phone: '01712345678', email: 'rahim@example.com', address: 'House 22, Road 5, Dhanmondi, Dhaka', image: null, district: 'Dhaka', area: 'Dhanmondi' };

const cartContent = C([
  { rowId: 'r1', id: pTeeBlack.id, name: pTeeBlack.name, qty: 2, price: 550, subtotal: 1100, options: { slug: pTeeBlack.slug, image: pTeeBlack.image.image, product_size: 'M', product_color: 'Black', old_price: 850 } },
  { rowId: 'r2', id: pWatchPro.id, name: pWatchPro.name, qty: 1, price: 2450, subtotal: 2450, options: { slug: pWatchPro.slug, image: pWatchPro.image.image, product_size: '', product_color: '', old_price: 3800 } },
  { rowId: 'r3', id: pSneakWhite.id, name: pSneakWhite.name, qty: 1, price: 1650, subtotal: 1650, options: { slug: pSneakWhite.slug, image: pSneakWhite.image.image, product_size: '41', product_color: 'White', old_price: 2400 } },
]);

const session = { shipping: 60, discount: 100, coupon_code: 'SAVE100', shipping_id: 1 };

/* ---------- orders ---------- */
const orderStatuses = { 0: 'Processing', 1: 'Confirmed', 2: 'Packed', 3: 'Shipped', 6: 'Delivered', 11: 'Cancelled' };
const mkOrder = (id, inv, statusId, items, amount) => ({
  id, invoice_id: inv, order_status: statusId, amount,
  discount: 100, shipping_charge: 60,
  created_at: new Date(2026, 7, 14 - id),
  status: { id: statusId, name: orderStatuses[statusId] },
  payment: { payment_method: 'COD' },
  customer_id: 7,
  orderdetails: C(items.map((p, i) => ({ id: i + 1, product: p, product_name: p.name, qty: 1, price: p.new_price, total_price: p.new_price, image: { image: p.image.image } }))),
  shipping: { name: 'Rahim Uddin', phone: '01712345678', address: 'House 22, Road 5, Dhanmondi, Dhaka', area: 'Dhanmondi' },
  admin_note: id === 1 ? '<p>Your parcel has been handed over to the courier. Expected delivery within 24 hours. <b>Thank you for shopping with us!</b></p>' : null,
});

const orders = C([
  mkOrder(3, 'INV-20260814-1023', 3, [pTeeBlack, pSneakWhite], 2260),
  mkOrder(2, 'INV-20260810-0845', 6, [pWatchPro], 2510),
  mkOrder(1, 'INV-20260801-0612', 1, [pHeadLight, pBud], 3200),
]);
const order = orders.items[0];
const recentOrders = orders;

const refunds = C([
  { id: 1, refund_id: 'RF-10234', status: 'pending', amount: 1150, shipping_charge: 60, reason: 'Product size does not match the description. Would like a refund.', created_at: new Date(2026, 7, 13), order: orders.items[0], refund_method: 'bkash', refund_account_name: 'Rahim Uddin', refund_account: '01712345678' },
  { id: 2, refund_id: 'RF-10211', status: 'processed', amount: 780, shipping_charge: 60, reason: 'Changed my mind after delivery.', created_at: new Date(2026, 6, 28), order: orders.items[1], refund_method: 'nagad', refund_account_name: 'Rahim Uddin', refund_account: '01712345678' },
]);

/* ---------- checkout support ---------- */
const shippingcharge = C([
  { id: 1, name: 'Inside Dhaka — ৳60', amount: 60 },
  { id: 2, name: 'Outside Dhaka — ৳120', amount: 120 },
]);
const bkash_gateway = { id: 1, type: 'bkash' };
const shurjopay_gateway = { id: 2, type: 'shurjopay' };
const uddoktapay_gateway = { id: 3, type: 'uddoktapay' };
const aamarpay_gateway = { id: 4, type: 'aamarpay' };
const advanceTotal = 0;
const hasAdvance = false;
const hasDigital = false;
const hasAllFreeDelivery = false;

const districts = C([
  { id: 1, district: 'Dhaka', area_name: 'Dhanmondi' },
  { id: 2, district: 'Dhaka', area_name: 'Mirpur' },
  { id: 3, district: 'Chattogram', area_name: 'Agrabad' },
]);
const areas = C([{ id: 1, area_name: 'Dhanmondi' }]);
const profile_edit = { ...customer, image: null };

const gtm_code = C([]);
const pixels = C([]);
const tiktok_pixels = C([]);
const demoMode = false;

module.exports = {
  generalsetting, seo, menucategories, frontcategory, pages, pagesright, socialicons, contact,
  sliders, campaognads, sliderbottomads, homepageads, homepageads2, hitdealsbaner, footertopads, reviews,
  brands, blogs, vendors, flas_sales, hotdeal_top, hotdeal_bottom, homeproducts, all_products,
  allProducts, customer, cartContent, session, orders, order, refunds,
  shippingcharge, bkash_gateway, shurjopay_gateway, uddoktapay_gateway, aamarpay_gateway,
  advanceTotal, hasAdvance, hasDigital, hasAllFreeDelivery,
  districts, areas, profile_edit, cmnmenu, gtm_code, pixels, tiktok_pixels, demoMode,
  cat: { fashion, electronics, home, beauty, sports, kids },
  subs: { sMens, sShoes, sWear, sAudio, sDecor, sSkin },
  prods: { pTeeBlack, pTeeWhite, pPolo, pDenim, pSneakWhite, pSneakCanvas, pRunner, pSandal, pWatchPro, pWatchFit, pWatchMini, pHeadOver, pHeadLight, pBud, pLamp, pPlanter, pSkinSet, pSunscreen, pBottle, pBag },
  B, P,
};
