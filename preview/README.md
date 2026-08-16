# Storefront Design Preview

A zero-dependency static preview of the **Shop Genie storefront redesign**.
It compiles the **real Blade templates** from `resources/views/frontEnd` with
sample data, so what you see here is exactly what production will render.

## How it works

- `data.js` — mock data mirroring the real database shapes (products, categories, orders, settings…)
- `compiler.js` — a mini Blade compiler supporting the Blade subset used by this storefront
- `build.js` — renders all 33 pages into `www/`
- `server.js` — serves `www/` on port 8000 (no dependencies)

## Run

```bash
node build.js          # rebuild all pages
node server.js         # start preview server → http://localhost:8000
```

## Pages

`/` (index) is a navigation page. Individual pages: `home.html`, `category.html`,
`subcategory.html`, `childcategory.html`, `product.html`, `search.html`, `cart.html`,
`checkout.html`, `login.html`, `register.html`, `account.html`, `orders.html`,
`order-track.html`, `tracking-result.html`, `order-success.html`, `order-note.html`,
`invoice.html`, `profile-edit.html`, `change-password.html`, `refunds.html`,
`refund-request.html`, `refund-details.html`, `flash-sale.html`, `hot-deals.html`,
`sellers.html`, `vendor-shop.html`, `brand.html`, `blog.html`, `blog-details.html`,
`contact.html`, `complaint.html`, `page.html`, `offers.html`.

Each page has a floating PREVIEW bar at the bottom to jump between pages.
