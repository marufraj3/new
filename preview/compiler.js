/* ============================================================
   Mini Blade compiler — renders the real Blade templates with
   mock data so the redesign can be previewed without PHP.
   Supports the Blade subset used in this project's storefront.
   ============================================================ */
const fs = require('fs');
const path = require('path');
const { Collection, C } = require('./lib');

const VIEWS = path.join(__dirname, '..', 'resources', 'views');

/* ---------------------------------------------------------- */
/*  Runtime helpers                                           */
/* ---------------------------------------------------------- */
class Runtime {
  constructor(vars) {
    this.vars = vars;
    this.cart = {
      count: () => 3,
      subtotal: () => '4,750.00',
      content: () => vars.cartContent,
    };
    this.session = { shipping: vars.session.shipping, discount: vars.session.discount, coupon_code: vars.session.coupon_code };
    this.queryParams = {};
    this.includes = {};
    this.sections = {};
    this.stacks = {};
  }
  setQuery(q) { this.queryParams = q; }
  h() {
    const rt = this;
    const g = (o, p) => (o === null || o === undefined ? undefined : o[p]);
    return {
      route: (name, params) => {
        const map = {
          home: '/', shop: '/shop', search: '/search', livesearch: '/livesearch',
          category: '/category/' + (params || 'fashion'), subcategory: '/subcategory/mens-clothing',
          products: '/products/t-shirts', product: '/product/' + (params ? (typeof params === 'string' ? params : params.id) : 'premium-cotton-tshirt-black'),
          'cart.show': '/cart', 'cart.store': '/cart/store', 'cart.remove': '/cart/remove', 'cart.increment': '/cart/increment', 'cart.decrement': '/cart/decrement',
          'cart.count': '/cart/count', 'mobile.cart.count': '/mobilecart/count', 'cart.sidebar': '/cart/sidebar', 'shipping.charge': '/shipping-charge',
          quickview: '/quick-view', 'customer.checkout': '/checkout', 'customer.ordersave': '/order-save',
          'customer.login': '/login', 'customer.register': '/register', 'customer.signin': '/signin', 'customer.store': '/store',
          'customer.account': '/customer/account', 'customer.orders': '/customer/orders', 'customer.order_track': '/customer/order-track',
          'customer.order_track_result': '/customer/order-track/result', 'customer.logout': '/logout',
          'customer.profile_edit': '/customer/profile-edit', 'customer.profile_update': '/customer/profile-update',
          'customer.change_pass': '/customer/change-password', 'customer.password_update': '/customer/password-update',
          'customer.refunds': '/customer/refunds', 'customer.refunds.create': '/customer/refunds/request/3', 'customer.refunds.store': '/customer/refunds/request',
          'customer.refunds.show': '/customer/refunds/1', 'customer.refunds.cancel': '/customer/refunds/1/cancel',
          'customer.order_success': '/customer/order-success/3', 'customer.order_note': '/customer/invoice/order-note', 'customer.invoice': '/customer/invoice',
          'customer.review': '/post/review', 'customer.verify': '/verify', 'customer.account.verify': '/verify-account', 'customer.resendotp': '/resend-otp',
          'customer.forgot.password': '/forgot-password', 'customer.forgot.verify': '/forgot-verify', 'customer.forgot.store': '/forgot-password/store', 'customer.forgot.resendotp': '/forgot-password/resendotp',
          'customer.forgot.reset': '/forgot-password/reset',
          flashsales: '/flash-sales', hotdeals: '/hot-deals', sellers: '/sellers', 'vendor.shop': '/shop/' + (params || 'urbanwear-bd'),
          'brand.products': '/brand/' + (params || 'urbanwear'), blogs: '/blogs', 'blog.details': '/blog/' + (params || 'style-tips-season'),
          contact: '/contact', complaint: '/complaint', 'complaint.store': '/complaint/store', 'frontend.contact.store': '/contact/store',
          page: '/page/' + (params ? (typeof params === 'string' ? params : params.slug) : 'about-us'),
          'frontend.newsletter.subscribe': '/newsletter/subscribe', offers: '/offer',
          'coupon.apply': '/cart/apply-coupon', 'coupon.remove': '/cart/remove-coupon',
          'incomplete.order.store': '/incomplete-order/store', districts: '/districts',
          login: '/login', register: '/register', logout: '/logout',
          'users.index': '/users', 'roles.index': '/roles', 'products.index': '/products',
          'password.request': '/password/reset',
        };
        return map[name] !== undefined ? map[name] : '/#' + name;
      },
      url: (u) => u,
      asset: (p) => {
        if (!p) return '';
        if (typeof p !== 'string') return String(p);
        if (p.startsWith('http')) return p;
        if (p.startsWith('/')) return p;
        return '/' + p;
      },
      e: (s) => String(s === null || s === undefined ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'),
      count: (x) => (x == null ? 0 : (x.length !== undefined ? x.length : x.count ? x.count() : 0)),
      empty: (x) => !x || (x.length !== undefined && x.length === 0),
      nfmt: (x, d) => {
        const n = Number(x) || 0;
        if (d === 0) return Math.round(n).toLocaleString('en-US');
        return n.toFixed(d === undefined ? 0 : d).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      },
      strLimit: (s, n, end) => {
        s = String(s === null || s === undefined ? '' : s);
        return s.length > n ? s.slice(0, n).trimEnd() + (end || '…') : s;
      },
      strBefore: (s, sep) => String(s === null || s === undefined ? '' : s).split(sep)[0],
      strAfter: (s, sep) => { const p = String(s || '').split(sep); return p.length > 1 ? p.slice(1).join(sep) : ''; },
      stripTags: (s) => String(s === null || s === undefined ? '' : s).replace(/<[^>]*>/g, ''),
      json: (x) => JSON.stringify(x === undefined ? null : x),
      strReplace: (a, b, c) => String(c === null || c === undefined ? '' : c).split(a).join(b),
      floatval: (x) => parseFloat(String(x).replace(/[^\d.]/g, '')) || 0,
      pregReplaceClean: (x) => parseFloat(String(x).replace(/[^\d.]/g, '')) || 0,
      collect: () => new Collection([]),
      optional: (x) => new Proxy(x === null || x === undefined ? {} : x, {
        get: (t, p) => {
          if (p === Symbol.iterator) return undefined;
          const v = x === null || x === undefined ? undefined : x[p];
          if (v === undefined || v === null) {
            return new Proxy(function () { return undefined; }, {
              get: () => undefined,
              apply: () => undefined,
            });
          }
          return typeof v === 'function' ? v.bind(x) : v;
        },
      }),
      old: () => '',
      session: (k) => rt.session[k],
      query: (k, def) => rt.queryParams[k] !== undefined && rt.queryParams[k] !== null ? rt.queryParams[k] : def,
      config: (k) => (k === 'app.name' ? 'Shop Genie' : ''),
      iter: (x) => (x == null ? [] : Array.isArray(x) ? x : (x.items || [])),
      def: (a, b) => (a === undefined || a === null ? b : a),
      date: (fmt) => (fmt === 'Y' ? '2026' : new Date().toISOString()),
      strtotime: (s) => Math.round(new Date(String(s).replace(/-/g, '/')).getTime() / 1000),
      app: (cls) => ({ productScript: () => '' }),
      staticCall: (klass, method, args) => rt.staticCall(klass, method, args),
      toastrMessage: () => '',
      authUser: () => rt.vars.customer || null,
      routeIs: () => false,
      requestIs: () => false,
      urlencode: (s) => encodeURIComponent(String(s || '')),
      jsonArr: (x) => JSON.stringify(x && x.items !== undefined ? x.items : (x === undefined ? null : x)),
      csrfToken: () => 'csrf-token-preview',
      ucfirst: (s) => { s = String(s || ''); return s.charAt(0).toUpperCase() + s.slice(1); },
      nl2br: (s) => String(s || '').replace(/\n/g, '<br>'),
      htmlRaw: (s) => s,
    };
  }
  staticCall(klass, method, args) {
    const V = this.vars;
    if (klass === 'Str') {
      const strMap = { limit: 'strLimit', before: 'strBefore', after: 'strAfter', upper: 'strUpper', lower: 'strLower', slug: 'strSlug' };
      const fn = this.h()[strMap[method] || method];
      if (typeof fn !== 'function') return '';
      return fn(...args);
    }
    if (klass === 'Cart') {
      if (method === 'instance') return this.cart;
    }
    if (klass === 'Session') {
      if (method === 'get') return this.session[args[0]] || 0;
      if (method === 'put') { this.session[args[0]] = args[1]; return null; }
      if (method === 'has') return this.session[args[0]] !== undefined;
    }
    if (klass === 'Auth') {
      if (method === 'guard') {
        return {
          user: () => V.customer,
          check: () => !!V.customer,
          id: () => (V.customer ? V.customer.id : null),
        };
      }
    }
    if (klass === 'Product') {
      if (method === 'find') return V.allProducts.find(p => String(p.id) === String(args[0])) || null;
      if (method === 'where') {
        return new ModelQuery(V.allProducts, method, args);
      }
    }
    if (klass === 'Order') {
      if (method === 'where') return new ModelQuery(V.orders.items, method, args);
    }
    if (klass === 'GeneralSetting') {
      if (method === 'where') return new ModelQuery([V.generalsetting], method, args);
      if (method === 'first') return V.generalsetting;
    }
    if (klass === 'ShoppingController') {
      if (method === 'hasAllFreeDeliveryProducts') return false;
      if (method === 'getCartAdvanceAmount') return 0;
    }
    if (klass === 'Request') {
      if (method === 'is') return false;
      if (method === 'url') return '/';
    }
    if (klass === 'Route') {
      if (method === 'is') return false;
    }
    if (klass === 'Toastr') {
      if (method === 'message') return '';
    }
    return undefined;
  }
}

class ModelQuery {
  constructor(rows, method, args) {
    this.rows = rows.slice();
    if (method === 'where') this.where(args[0], args.length > 2 ? args[1] : '=', args.length > 2 ? args[2] : args[1]);
    if (method === 'whereIn') this.whereIn(args[0], args[1]);
    if (method === 'whereNotIn') this.whereNotIn(args[0], args[1]);
  }
  where(k, op, v) {
    if (v === undefined) { v = op; op = '='; }
    this.rows = this.rows.filter(r => r && ((op === '=' || op === '==') ? String(r[k]) == String(v) : op === '!=' ? String(r[k]) != String(v) : op === '>' ? r[k] > v : op === '<' ? r[k] < v : true));
    return this;
  }
  whereIn(k, vals) { this.rows = this.rows.filter(r => r && vals.map(String).includes(String(r[k]))); return this; }
  whereNotIn(k, vals) { this.rows = this.rows.filter(r => r && !vals.map(String).includes(String(r[k]))); return this; }
  latest() { this.rows.reverse(); return this; }
  limit(n) { this.rows = this.rows.slice(0, n); return this; }
  with() { return this; }
  orderBy() { return this; }
  count() { return this.rows.length; }
  sum(k) { return this.rows.reduce((a, r) => a + (Number(r[k]) || 0), 0); }
  first() { return this.rows[0]; }
  get() { return C(this.rows); }
  paginate() {
    return { items: this.rows, count: () => this.rows.length, total: () => this.rows.length, [Symbol.iterator]: function* () { yield* this.rows; }, onEachSide: function () { return this; }, links: () => '' };
  }
}

/* ---------------------------------------------------------- */
/*  Expression translator (PHP subset → JS)                   */
/* ---------------------------------------------------------- */
class ExprParser {
  constructor(src, varMap) {
    this.src = src;
    this.i = 0;
    this.varMap = varMap || {};
  }
  ws() { while (this.i < this.src.length && /\s/.test(this.src[this.i])) this.i++; }
  err(msg) { throw new Error(msg + ' at pos ' + this.i + ' in: ' + this.src.slice(Math.max(0, this.i - 30), this.i + 40)); }
  peek(ch) { this.ws(); return this.src[this.i] === ch; }
  eat(ch) { this.ws(); if (this.src[this.i] !== ch) this.err('expected ' + ch); this.i++; }
  parse() { return this.ternary(); }
  ternary() {
    let c = this.binary(0);
    this.ws();
    if (this.src[this.i] === '?') {
      this.i++;
      const a = this.ternary();
      this.eat(':');
      const b = this.ternary();
      c = '(' + c + ' ? ' + a + ' : ' + b + ')';
    }
    return c;
  }
  binary(minPrec) {
    const ops = [
      ['??', 1, true], ['||', 2, false], ['&&', 3, false],
      ['===', 4, false], ['!==', 4, false], ['==', 4, false], ['!=', 4, false],
      ['<=', 4, false], ['>=', 4, false], ['<', 4, false], ['>', 4, false],
      ['+', 5, false], ['-', 5, false], ['.', 5, false],
      ['*', 6, false], ['/', 6, false], ['%', 6, false],
    ];
    let left = this.unary();
    for (;;) {
      this.ws();
      let matched = null;
      for (const [op, prec] of ops) {
        if (prec >= minPrec && this.src.startsWith(op, this.i)) { matched = [op, prec]; break; }
      }
      if (!matched) break;
      const [op, prec] = matched;
      this.i += op.length;
      const right = this.binary(prec + 1);
      if (op === '.') left = 'String(' + left + ') + String(' + right + ')';
      else if (op === '??') left = '_h.def(' + left + ', ' + right + ')';
      else left = '(' + left + ' ' + op + ' ' + right + ')';
    }
    return left;
  }
  unary() {
    this.ws();
    if (this.src[this.i] === '!') { this.i++; return '(!' + this.unary() + ')'; }
    if (this.src[this.i] === '-') { this.i++; return '(-' + this.unary() + ')'; }
    return this.postfix();
  }
  postfix() {
    let e = this.primary();
    for (;;) {
      this.ws();
      if (this.src.startsWith('->', this.i) || this.src.startsWith('?->', this.i)) {
        const q = this.src[this.i] === '?';
        this.i += q ? 3 : 2;
        this.ws();
        let name = '';
        while (this.i < this.src.length && /[A-Za-z0-9_]/.test(this.src[this.i])) { name += this.src[this.i]; this.i++; }
        if (!name) this.err('expected prop name');
        this.ws();
        if (this.src[this.i] === '(') {
          const args = this.argList();
          e = '(' + e + ' && ' + e + '.' + name + ' !== undefined ? ' + e + '.' + name + '(' + args + ') : undefined)';
        } else {
          e = '(' + e + ' && ' + e + '.' + name + ' !== undefined ? ' + e + '.' + name + ' : undefined)';
        }
        continue;
      }
      if (this.src[this.i] === '[') {
        this.i++;
        const idx = this.ternary();
        this.eat(']');
        e = '(' + e + ' || {})[' + idx + ']';
        continue;
      }
      break;
    }
    return e;
  }
  argList() {
    this.eat('(');
    const args = [];
    this.ws();
    if (this.src[this.i] !== ')') {
      for (;;) {
        args.push(this.ternary());
        this.ws();
        if (this.src[this.i] === ',') { this.i++; continue; }
        break;
      }
    }
    this.eat(')');
    return args.join(', ');
  }
  primary() {
    this.ws();
    const c = this.src[this.i];
    if (c === undefined) this.err('unexpected end');
    /* numbers */
    if (/[0-9]/.test(c)) {
      let num = '';
      while (this.i < this.src.length && /[0-9.]/.test(this.src[this.i])) num += this.src[this.i++];
      return num;
    }
    /* strings */
    if (c === "'" || c === '"') {
      const q = c;
      this.i++;
      let s = '';
      while (this.i < this.src.length) {
        const ch = this.src[this.i];
        if (ch === '\\') { s += this.src[this.i + 1]; this.i += 2; continue; }
        if (ch === q) { this.i++; break; }
        s += ch; this.i++;
      }
      return JSON.stringify(s);
    }
    /* variables */
    if (c === '$') {
      this.i++;
      let name = '';
      while (this.i < this.src.length && /[A-Za-z0-9_]/.test(this.src[this.i])) name += this.src[this.i++];
      if (this.varMap[name] !== undefined) return this.varMap[name];
      return "_v['" + name + "']";
    }
    /* casts (int)/(float)/(string) */
    if (c === '(') {
      const castM = this.src.slice(this.i).match(/^\((int|float|string|bool|boolean)\)\s*/);
      if (castM) {
        this.i += castM[0].length;
        const inner = this.unary();
        if (castM[1] === 'int' || castM[1] === 'float') return 'Number(' + inner + ' || 0)';
        return 'String(' + inner + ')';
      }
    }
    /* parens */
    if (c === '(') {
      this.i++;
      const e = this.ternary();
      this.eat(')');
      return '(' + e + ')';
    }
    /* arrays */
    if (c === '[') {
      this.i++;
      const items = [];
      let isAssoc = false;
      this.ws();
      if (this.src[this.i] !== ']') {
        for (;;) {
          this.ws();
          if (this.src[this.i] === ']') break; /* trailing comma tolerance */
          let k = null, v;
          /* look ahead: k => */
          const save = this.i;
          const possibleKey = this.maybeKey();
          if (possibleKey !== null && this.src[this.i] === '=' && this.src[this.i + 1] === '>') {
            this.i += 2;
            isAssoc = true;
            k = possibleKey;
            v = this.ternary();
          } else {
            this.i = save;
            v = this.ternary();
          }
          items.push([k, v]);
          this.ws();
          if (this.src[this.i] === ',') { this.i++; continue; }
          break;
        }
      }
      this.eat(']');
      if (isAssoc) {
        return '{ ' + items.map(([k, v]) => JSON.stringify(String(k)) + ': ' + v).join(', ') + ' }';
      }
      return '[ ' + items.map(([k, v]) => v).join(', ') + ' ]';
    }
    /* closures: function (...) { return expr; } */
    if (this.src.startsWith('function', this.i)) {
      this.i += 8;
      this.ws();
      this.eat('(');
      const params = [];
      this.ws();
      if (this.src[this.i] !== ')') {
        for (;;) {
          if (this.src[this.i] !== '$') this.err('expected $param');
          this.i++;
          let pn = '';
          while (this.i < this.src.length && /[A-Za-z0-9_]/.test(this.src[this.i])) pn += this.src[this.i++];
          params.push(pn);
          this.ws();
          if (this.src[this.i] === ',') { this.i++; continue; }
          break;
        }
      }
      this.eat(')');
      this.ws();
      this.eat('{');
      this.ws();
      let retExpr = null;
      const retM = this.src.slice(this.i).match(/^return\s+/);
      if (retM) {
        this.i += retM[0].length;
        retExpr = this.ternary();
        this.ws();
        this.eat(';');
      }
      this.ws();
      this.eat('}');
      const map = { ...this.varMap };
      params.forEach((p, idx) => { map[p] = '_cl' + idx; });
      if (retExpr === null) return '(() => undefined)';
      return '(' + params.map((p, idx) => '_cl' + idx).join(', ') + ') => (' + retExpr + ')';
    }
    /* identifiers / functions / class refs */
    if (/[A-Za-z_\\]/.test(c)) {
      let name = '';
      while (this.i < this.src.length && /[A-Za-z0-9_\\]/.test(this.src[this.i])) name += this.src[this.i++];
      this.ws();
      if (name === 'fn' && this.src[this.i] === '(') {
        /* arrow fn: fn($a, $b) => expr */
        this.i++;
        const params = [];
        this.ws();
        if (this.src[this.i] !== ')') {
          for (;;) {
            if (this.src[this.i] !== '$') this.err('expected $param');
            this.i++;
            let pn = '';
            while (this.i < this.src.length && /[A-Za-z0-9_]/.test(this.src[this.i])) pn += this.src[this.i++];
            params.push(pn);
            this.ws();
            if (this.src[this.i] === ',') { this.i++; continue; }
            break;
          }
        }
        this.eat(')');
        this.ws();
        if (this.src[this.i] !== '=' || this.src[this.i + 1] !== '>') this.err('expected => in arrow fn');
        this.i += 2;
        const bodyStart = this.i;
        this.ternary(); /* consume + validate */
        const bodyRaw = this.src.slice(bodyStart, this.i);
        const map = { ...this.varMap };
        params.forEach((p, idx) => { map[p] = '_fp' + idx; });
        return '(' + params.map((p, idx) => '_fp' + idx).join(', ') + ') => (' + translateExpr(bodyRaw, map) + ')';
      }
      if (name === 'true') return 'true';
      if (name === 'false') return 'false';
      if (name === 'null') return 'null';
      if (this.src[this.i] === '(') {
        const args = this.argList();
        return this.fn(name, args);
      }
      if (name.endsWith('::class')) return JSON.stringify(name.split('\\').pop().replace('::class', ''));
      if (this.src.startsWith('::', this.i)) {
        this.i += 2;
        this.ws();
        let method = '';
        while (this.i < this.src.length && /[A-Za-z0-9_]/.test(this.src[this.i])) method += this.src[this.i++];
        this.ws();
        const args = this.src[this.i] === '(' ? this.argList() : '';
        const klass = name.split('\\').pop();
        return '_h.staticCall(' + JSON.stringify(klass) + ', ' + JSON.stringify(method) + ', [' + args + '])';
      }
      return 'undefined /* ident:' + name + ' */';
    }
    this.err('unexpected char ' + c);
  }
  maybeKey() {
    /* returns string key if next token is a simple string, else null (and leaves i) */
    const save = this.i;
    const c = this.src[this.i];
    if (c === "'" || c === '"') {
      const parsed = this.primary();
      this.ws();
      return parsed.slice(1, -1);
    }
    if (c === '$') {
      this.i++;
      let n = '';
      while (this.i < this.src.length && /[A-Za-z0-9_]/.test(this.src[this.i])) n += this.src[this.i++];
      this.ws();
      return '_k:' + n;
    }
    this.i = save;
    return null;
  }
  fn(name, args) {
    const map = {
      route: '_h.route', url: '_h.url', asset: '_h.asset', e: '_h.e', count: '_h.count', empty: '_h.empty',
      number_format: '_h.nfmt', optional: '_h.optional', json_encode: '_h.json', date: '_h.date',
      strtotime: '_h.strtotime', floatval: '_h.floatval', preg_replace: '_h.pregReplaceClean', str_replace: '_h.strReplace',
      strtoupper: 'String.prototype.toUpperCase.call', strtolower: 'String.prototype.toLowerCase.call', substr: 'String.prototype.substr.call',
      in_array: '_h.inArray', round: 'Math.round', floor: 'Math.floor', ceil: 'Math.ceil', max: 'Math.max', min: 'Math.min', abs: 'Math.abs',
      config: '_h.config', collect: '_h.collect', session: '_h.session', request: '_h.query', app: '_h.app', urlencode: '_h.urlencode',
      strip_tags: '_h.stripTags', view: '_h.viewObj', old: '_h.old', intval: 'parseInt',
    };
    if (name === 'csrf_token') return '_h.csrfToken()';
    if (name === 'nl2br') return '_h.nl2br(' + args + ')';
    if (name === 'isset') return '((' + args + ' !== undefined && ' + args + ' !== null))';
    if (name === 'ucfirst') return '_h.ucfirst(' + args + ')';
    if (name === 'substr') return '(_h.strSub(' + args + '))';
    if (name === 'strtoupper') return '(String(' + args + ').toUpperCase())';
    if (name === 'strtolower') return '(String(' + args + ').toLowerCase())';
    if (map[name]) {
      return map[name] + '(' + args + ')';
    }
    /* Str::xxx handled by staticCall → but as function call it looks like Str::limit — handled by :: branch */
    return '(_h.glob(' + JSON.stringify(name) + ')(' + args + '))';
  }
}

function translateExpr(src, varMap) {
  return new ExprParser(src, varMap).parse();
}

/* ---------------------------------------------------------- */
/*  Statement parser for @php blocks                          */
/* ---------------------------------------------------------- */
function parseStmts(src, varMap, out, indent) {
  let i = 0;
  const ws = () => { while (i < src.length && /\s/.test(src[i])) i++; };
  const readBalancedUntil = (open, close) => {
    /* assumes open already consumed; returns inner text */
    let depth = 1, j = i, q = null, esc = false;
    while (j < src.length) {
      const ch = src[j];
      if (esc) { esc = false; j++; continue; }
      if (ch === '\\') { esc = true; j++; continue; }
      if (q) { if (ch === q) q = null; j++; continue; }
      if (ch === "'" || ch === '"') { q = ch; j++; continue; }
      if (ch === open) depth++;
      else if (ch === close) { depth--; if (depth === 0) return [src.slice(i, j), j]; }
      j++;
    }
    throw new Error('unbalanced ' + open + close + ' in php block');
  };
  const readTo = (chars) => {
    /* read until one of chars at depth 0 (quotes/brackets aware) */
    let j = i, q = null, esc = false;
    const stack = [];
    while (j < src.length) {
      const ch = src[j];
      if (esc) { esc = false; j++; continue; }
      if (ch === '\\') { esc = true; j++; continue; }
      if (q) { if (ch === q) q = null; j++; continue; }
      if (ch === "'" || ch === '"') { q = ch; j++; continue; }
      if (ch === '(' || ch === '[' || ch === '{') stack.push(ch);
      else if (ch === ')' || ch === ']' || ch === '}') stack.pop();
      if (stack.length === 0 && chars.includes(ch)) return [src.slice(i, j), j];
      j++;
    }
    return [src.slice(i), src.length];
  };
  const line = (js) => out.push('  '.repeat(indent) + js);

  while (i < src.length) {
    ws();
    if (i >= src.length) break;
    const rest = src.slice(i);
    /* foreach */
    let m = rest.match(/^foreach\s*\(/);
    if (m) {
      i += m[0].length;
      const [inner, end] = readBalancedUntil('(', ')');
      i = end + 1;
      /* inner: EXPR as $k => $v | EXPR as $v */
      const asMatch = inner.match(/^(.*?)\s+as\s+(?:\$([A-Za-z0-9_]+)\s*=>\s*)?\$([A-Za-z0-9_]+)\s*$/);
      if (!asMatch) throw new Error('bad foreach: ' + inner);
      const [, expr, keyVar, valVar] = asMatch;
      const exprJs = translateExpr(expr, varMap);
      ws();
      let body = '';
      if (src[i] === '{') {
        i++;
        const [innerBody, end2] = readBalancedUntil('{', '}');
        i = end2 + 1;
        body = innerBody;
      } else {
        const [stmt, end2] = readTo([';']);
        i = end2 + 1;
        body = stmt;
      }
      const kid = 'k' + (Math.random() * 1e9 | 0);
      if (keyVar) {
        line('for (const [' + kid + ', _' + valVar + '] of Object.entries(_h.iter(' + exprJs + '))) {');
        const newMap = { ...varMap, [valVar]: '_' + valVar, [keyVar]: kid, loop: '_loop' + kid };
        parseStmts(body, newMap, out, indent + 1);
        line('}');
      } else {
        line('{ const _' + valVar + 's = _h.iter(' + exprJs + '); for (const [_loop' + kid + ', _' + valVar + '] of _' + valVar + 's.entries()) { const _loop' + kid + 'x = { first: _loop' + kid + ' === 0, last: _loop' + kid + ' === _' + valVar + 's.length - 1, index: _loop' + kid + ' };');
        const newMap = { ...varMap, [valVar]: '_' + valVar, loop: '_loop' + kid + 'x' };
        parseStmts(body, newMap, out, indent + 1);
        line('} }');
      }
      continue;
    }
    /* for loop */
    m = rest.match(/^for\s*\(/);
    if (m) {
      i += m[0].length;
      const [inner, end] = readBalancedUntil('(', ')');
      i = end + 1;
      const parts = inner.split(';');
      const init = parts[0].trim().replace(/^\$([A-Za-z0-9_]+)\s*=\s*/, (_, v) => 'let _lv_' + v + ' = ');
      const lvName = (parts[0].trim().match(/^\$([A-Za-z0-9_]+)/) || [])[1];
      const cond = translateExpr(parts[1].trim(), { [lvName]: '_lv_' + lvName });
      const incr = parts[2].trim().replace(/\$([A-Za-z0-9_]+)\+\+/, '_lv_$1++').replace(/\$([A-Za-z0-9_]+)--/, '_lv_$1--');
      ws();
      let body = '';
      if (src[i] === '{') {
        i++;
        const [innerBody, end2] = readBalancedUntil('{', '}');
        i = end2 + 1;
        body = innerBody;
      } else {
        const [stmt, end2] = readTo([';']);
        i = end2 + 1;
        body = stmt;
      }
      line('for (' + init + '; ' + cond + '; ' + incr + ') {');
      parseStmts(body, { ...varMap, [lvName]: '_lv_' + lvName }, out, indent + 1);
      line('}');
      continue;
    }
    /* if / elseif / else — handled as one chain */
    m = rest.match(/^(if)\s*\(/);
    if (m) {
      i += m[0].length;
      const [inner, end] = readBalancedUntil('(', ')');
      i = end + 1;
      const cond = translateExpr(inner, varMap);
      ws();
      let body = '';
      if (src[i] === '{') {
        i++;
        const [innerBody, end2] = readBalancedUntil('{', '}');
        i = end2 + 1;
        body = innerBody;
      } else {
        const [stmt, end2] = readTo([';']);
        i = end2 + 1;
        body = stmt;
      }
      line('if (' + cond + ') {');
      parseStmts(body, varMap, out, indent + 1);
      /* chain: elseif / else */
      for (;;) {
        const save = i;
        ws();
        const chainRest = src.slice(i);
        let cm = chainRest.match(/^elseif\s*\(/);
        if (cm) {
          i += cm[0].length;
          const [cinner, cend] = readBalancedUntil('(', ')');
          i = cend + 1;
          const ccond = translateExpr(cinner, varMap);
          ws();
          let cbody = '';
          if (src[i] === '{') {
            i++;
            const [ib, e2] = readBalancedUntil('{', '}');
            i = e2 + 1;
            cbody = ib;
          } else {
            const [st, e2] = readTo([';']);
            i = e2 + 1;
            cbody = st;
          }
          line('} else if (' + ccond + ') {');
          parseStmts(cbody, varMap, out, indent + 1);
          continue;
        }
        cm = chainRest.match(/^else\b/);
        if (cm) {
          const after = chainRest.slice(4).trimStart();
          if (after.startsWith('{')) {
            i += 4; ws(); i++;
            const [ib, e2] = readBalancedUntil('{', '}');
            i = e2 + 1;
            line('} else {');
            parseStmts(ib, varMap, out, indent + 1);
            line('}');
          } else {
            i += 4;
            /* else followed by if — handled by next iteration of chain */
            ws();
            const elseRest = src.slice(i);
            if (/^if\s*\(/.test(elseRest)) { continue; }
          }
        } else {
          line('}');
        }
        i = save === i ? i : i;
        break;
      }
      continue;
    }
    if (/^break\s*;/.test(rest)) { i += 6; line('break;'); continue; }
    if (/^return\s/.test(rest)) {
      i += 6;
      const [expr, end2] = readTo([';']);
      i = end2 + 1;
      line('return ' + translateExpr(expr.trim(), varMap) + ';');
      continue;
    }
    /* assignment / call / echo */
    const [stmt, end2] = readTo([';']);
    i = end2 + 1;
    const s = stmt.trim();
    if (!s) continue;
    const assign = s.match(/^\$([A-Za-z0-9_]+)\s*=(?!=)\s*([\s\S]*)$/);
    if (assign) {
      const [, v, expr] = assign;
      const target = varMap[v] !== undefined ? varMap[v] : "_v['" + v + "']";
      line(target + ' = ' + translateExpr(expr, varMap) + ';');
      continue;
    }
    const pushArr = s.match(/^\$([A-Za-z0-9_]+)\[\]\s*=\s*([\s\S]*)$/);
    if (pushArr) {
      const [, v, expr] = pushArr;
      const target = varMap[v] !== undefined ? varMap[v] : "_v['" + v + "']";
      line(target + '.push(' + translateExpr(expr, varMap) + ');');
      continue;
    }
    const assignOp = s.match(/^\$([A-Za-z0-9_]+)\s*(\.=|\+=|-=)\s*([\s\S]*)$/);
    if (assignOp) {
      const [, v, op, expr] = assignOp;
      const target = varMap[v] !== undefined ? varMap[v] : "_v['" + v + "']";
      const jsOp = op === '.=' ? '+' : op[0];
      line(target + ' = ' + target + ' ' + jsOp + ' (' + translateExpr(expr, varMap) + ');');
      continue;
    }
    /* bare expression (e.g. view()->share(...)) → no-op call */
    line(translateExpr(s, varMap) + ';');
  }
}

/* ---------------------------------------------------------- */
/*  Template → JS                                             */
/* ---------------------------------------------------------- */
function compileTemplate(template, mode) {
  /* mode: 'child' collects sections/stacks; 'layout' emits */
  template = template.replace(/\{\{--[\s\S]*?--\}\}/g, '');
  const out = [];
  let i = 0;
  const push = (s) => out.push(s);
  const N = () => 'n' + (Math.random() * 1e9 | 0);

  const readDirective = () => {
    /* at '@', parse name + optional balanced parens */
    let j = i + 1;
    let name = '';
    while (j < template.length && /[a-zA-Z]/.test(template[j])) { name += template[j]; j++; }
    let args = null;
    let k = j;
    while (k < template.length && /\s/.test(template[k])) k++;
    if (template[k] === '(') {
      let depth = 0, q = null, esc = false;
      for (let m = k; m < template.length; m++) {
        const ch = template[m];
        if (esc) { esc = false; continue; }
        if (ch === '\\') { esc = true; continue; }
        if (q) { if (ch === q) q = null; continue; }
        if (ch === "'" || ch === '"') { q = ch; continue; }
        if (ch === '(') depth++;
        else if (ch === ')') { depth--; if (depth === 0) { args = template.slice(k + 1, m); j = m + 1; break; } }
      }
      if (args === null) throw new Error('unbalanced @' + name + '(...)');
    }
    return [name, args, j];
  };

  const echoParse = (open, close, esc) => {
    let j = i + open.length, q = null, escp = false;
    for (; j < template.length - close.length + 1; j++) {
      const ch = template[j];
      if (escp) { escp = false; continue; }
      if (ch === '\\') { escp = true; continue; }
      if (q) { if (ch === q) q = null; continue; }
      if (ch === "'" || ch === '"') { q = ch; continue; }
      if (template.startsWith(close, j)) {
        const expr = template.slice(i + open.length, j).trim();
        i = j + close.length;
        return expr;
      }
    }
    throw new Error('unterminated echo: ' + template.slice(i, i + 40));
  };

  while (i < template.length) {
    const ch = template[i];
    if (ch === '{' && template.startsWith('{{--', i)) {
      const end = template.indexOf('--}}', i);
      i = end === -1 ? template.length : end + 4;
      continue;
    }
    if (ch === '{' && template.startsWith('{!!', i)) {
      const expr = echoParse('{!!', '!!}', false);
      push("_o.push(" + translateExpr(expr, {}) + ");");
      continue;
    }
    if (ch === '{' && template.startsWith('{{{', i)) {
      const expr = echoParse('{{{', '}}}', true);
      push("_o.push(_h.e(" + translateExpr(expr, {}) + "));");
      continue;
    }
    if (ch === '{' && template.startsWith('{{', i)) {
      const expr = echoParse('{{', '}}', true);
      push("_o.push(_h.e(" + translateExpr(expr, {}) + "));");
      continue;
    }
    if (ch === '@') {
      const [name, args, j] = readDirective();
      i = j;
      switch (name) {
        case 'extends': {
          const pathExpr = args.trim().slice(1, -1);
          if (mode === 'child') push('_ctx.extend(' + JSON.stringify(pathExpr) + ');');
          else push('/* extends ignored in layout */');
          break;
        }
        case 'section': {
          /* args: 'name', expr | 'name' */
          const m = args.match(/^\s*['"]([^'"]+)['"]\s*(?:,\s*([\s\S]*))?$/);
          if (!m) throw new Error('bad @section: ' + args);
          const [, secName, expr] = m;
          if (expr) {
            push("_ctx.section('" + secName + "', " + translateExpr(expr, {}) + ');');
          } else {
            const id = N();
            push('_ctx.beginSection(' + JSON.stringify(secName) + ');');
            push('{ const _b' + id + ' = _o; _o = [];');
            push('/* section body start */');
            compileUntil(['endsection'], secName, true);
            push('/* section body end */');
            push('_ctx.endSection(_o.join("")); _o = _b' + id + '; }');
          }
          break;
        }
        case 'endsection': break;
        case 'show': break;
        case 'yield': {
          const m = args.match(/^\s*['"]([^'"]+)['"]\s*(?:,\s*([\s\S]*))?$/);
          const [, yn, defExpr] = m;
          if (defExpr) push('_o.push(_h.def(_ctx.sections[' + JSON.stringify(yn) + '], ' + translateExpr(defExpr, {}) + '));');
          else push('_o.push(_ctx.sections[' + JSON.stringify(yn) + '] || "");');
          break;
        }
        case 'stack':
          push('_o.push((_ctx.stacks[' + JSON.stringify(args.trim().slice(1, -1)) + '] || []).join("\\n"));');
          break;
        case 'push': {
          const id = N();
          push('_ctx.beginStack(' + JSON.stringify(args.trim().slice(1, -1)) + ');');
          push('{ const _b' + id + ' = _o; _o = [];');
          compileUntil(['endpush'], null, true);
          push('_ctx.endStack(_o.join("\\n")); _o = _b' + id + '; }');
          break;
        }
        case 'endpush': break;
        case 'include': {
          const m = args.match(/^\s*['"]([^'"]+)['"]\s*(?:,\s*([\s\S]*))?$/);
          if (!m) throw new Error('bad @include: ' + args);
          const [, incPath, params] = m;
          if (params) {
            push('_o.push(_ctx.include(' + JSON.stringify(incPath) + ', ' + translateExpr(params, {}) + '));');
          } else {
            push('_o.push(_ctx.include(' + JSON.stringify(incPath) + ', {}));');
          }
          break;
        }
        case 'php': {
          const start = i;
          const end = template.indexOf('@endphp', start);
          if (end === -1) throw new Error('missing @endphp');
          const code = template.slice(start, end);
          i = end + '@endphp'.length;
          const stmts = [];
          parseStmts(code, {}, stmts, 0);
          push(stmts.join('\n'));
          break;
        }
        case 'if': push('if (' + translateExpr(args, {}) + ') {'); break;
        case 'elseif': push('} else if (' + translateExpr(args, {}) + ') {'); break;
        case 'else': push('} else {'); break;
        case 'endif': push('}'); break;
        case 'isset': push('if ((typeof ' + translateExpr(args, {}) + ' !== "undefined" && ' + translateExpr(args, {}) + ' !== null)) {'); break;
        case 'endisset': push('}'); break;
        case 'unless': push('if (!(' + translateExpr(args, {}) + ')) {'); break;
        case 'endunless': push('}'); break;
        case 'foreach': {
          const m = args.match(/^(.*?)\s+as\s+(?:\$([A-Za-z0-9_]+)\s*=>\s*)?\$([A-Za-z0-9_]+)$/);
          if (!m) throw new Error('bad @foreach: ' + args);
          const [, expr, keyVar, valVar] = m;
          const id = N();
          if (keyVar) {
            push('for (const [k' + id + ', _' + valVar + '] of Object.entries(_h.iter(' + translateExpr(expr, {}) + '))) {');
            push('const _loop' + id + ' = { first: Number(k' + id + ') === 0, last: Number(k' + id + ') === _h.iter(' + translateExpr(expr, {}) + ').length - 1, index: Number(k' + id + ') };');
            push('_v[\'loop\'] = _loop' + id + ';');
            push('_v[' + JSON.stringify(keyVar) + '] = k' + id + '; _v[' + JSON.stringify(valVar) + '] = _' + valVar + ';');
            push('_varMap[' + JSON.stringify(valVar) + '] = _' + valVar + '; _varMap[' + JSON.stringify(keyVar) + '] = k' + id + '; _varMap.loop = _loop' + id + ';');
          } else {
            push('for (const [idx' + id + ', _' + valVar + '] of _h.iter(' + translateExpr(expr, {}) + ').entries()) {');
            push('const _loop' + id + ' = { first: idx' + id + ' === 0, last: idx' + id + ' === _h.iter(' + translateExpr(expr, {}) + ').length - 1, index: idx' + id + ' };');
            push('_v[' + JSON.stringify(valVar) + '] = _' + valVar + '; _v[\'loop\'] = _loop' + id + '; _varMap[' + JSON.stringify(valVar) + '] = _' + valVar + '; _varMap.loop = _loop' + id + ';');
          }
          break;
        }
        case 'endforeach': push('}'); break;
        case 'forelse': {
          const m = args.match(/^(.*?)\s+as\s+(?:\$([A-Za-z0-9_]+)\s*=>\s*)?\$([A-Za-z0-9_]+)$/);
          if (!m) throw new Error('bad @forelse: ' + args);
          const [, expr, keyVar, valVar] = m;
          const id = N();
          push('{ const _arr' + id + ' = _h.iter(' + translateExpr(expr, {}) + '); if (_arr' + id + '.length) {');
          push('for (const [idx' + id + ', _' + valVar + '] of _arr' + id + '.entries()) {');
          push('const _loop' + id + ' = { first: idx' + id + ' === 0, last: idx' + id + ' === _arr' + id + '.length - 1, index: idx' + id + ' };');
          push('_v[' + JSON.stringify(valVar) + '] = _' + valVar + '; _varMap[' + JSON.stringify(valVar) + '] = _' + valVar + '; _varMap.loop = _loop' + id + ';');
          break;
        }
        case 'empty': push('} } else {'); break;
        case 'endforelse': push('} }'); break;
        case 'for': {
          const m = args.match(/^\s*\$([A-Za-z0-9_]+)\s*=\s*(.*?)\s*;\s*(.*?)\s*;\s*(.*?)\s*$/);
          if (!m) throw new Error('bad @for: ' + args);
          const [, fv, init, cond, incr] = m;
          const id = N();
          let incrJs;
          if (/^\$[A-Za-z0-9_]+\+\+$/.test(incr.trim())) incrJs = '_lv' + id + '++';
          else if (/^\$[A-Za-z0-9_]+--$/.test(incr.trim())) incrJs = '_lv' + id + '--';
          else incrJs = translateExpr(incr, { [fv]: '_lv' + id });
          push('for (let _lv' + id + ' = ' + translateExpr(init, {}) + '; ' + translateExpr(cond, { [fv]: '_lv' + id }) + '; ' + incrJs + ') {');
          push('_varMap[' + JSON.stringify(fv) + '] = _lv' + id + '; _v[' + JSON.stringify(fv) + '] = _lv' + id + ';');
          break;
        }
        case 'endfor': push('}'); break;
        case 'csrf': push('_o.push(\'<input type="hidden" name="_token" value="csrf-token">\');'); break;
        case 'method': push('_o.push(\'<input type="hidden" name="_method" value="\' + ' + args.trim() + ' + \'">\');'); break;
        case 'json': push('_o.push(_h.jsonArr(' + translateExpr(args, {}) + '));'); break;
        case 'auth': push('if (_h.authUser()) {'); break;
        case 'endauth': push('}'); break;
        case 'guest': push('if (!_h.authUser()) {'); break;
        case 'endguest': push('}'); break;
        case 'error': push('{ const _e1 = ' + args.trim() + '; if (_v.errors && _v.errors.first(_e1)) {'); break;
        case 'enderror': push('} }'); break;
        case 'break': push('break;'); break;
        case 'continue': push('continue;'); break;
        case 'verbatim': {
          const start = i;
          const end = template.indexOf('@endverbatim', start);
          push(JSON.stringify(template.slice(start, end)));
          i = end + '@endverbatim'.length;
          break;
        }
        default:
          /* unknown directives (like @media in CSS) → literal */
          push('_o.push(' + JSON.stringify('@' + name + (args !== null ? '(' + args + ')' : '')) + ');');
      }
      continue;
    }
    /* plain text */
    let j = i;
    while (j < template.length && template[j] !== '@' && !template.startsWith('{{', j) && !template.startsWith('{!!', j)) j++;
    push('_o.push(' + JSON.stringify(template.slice(i, j)) + ');');
    i = j;
  }

  function compileUntil(endNames, label, inner) {
    while (i < template.length) {
      const ch = template[i];
      if (ch === '{' && template.startsWith('{{--', i)) { const end = template.indexOf('--}}', i); i = end === -1 ? template.length : end + 4; continue; }
      if (ch === '{' && template.startsWith('{!!', i)) { const expr = echoParse('{!!', '!!}', false); push('_o.push(' + translateExpr(expr, {}) + ');'); continue; }
      if (ch === '{' && template.startsWith('{{{', i)) { const expr = echoParse('{{{', '}}}', true); push('_o.push(_h.e(' + translateExpr(expr, {}) + '));'); continue; }
      if (ch === '{' && template.startsWith('{{', i)) { const expr = echoParse('{{', '}}', true); push('_o.push(_h.e(' + translateExpr(expr, {}) + '));'); continue; }
      if (ch === '@') {
        const [name, args, j] = readDirective();
        if (endNames.includes(name)) { i = j; return; }
        i = j;
        switch (name) {
          case 'if': push('if (' + translateExpr(args, {}) + ') {'); break;
          case 'elseif': push('} else if (' + translateExpr(args, {}) + ') {'); break;
          case 'else': push('} else {'); break;
          case 'endif': push('}'); break;
          case 'foreach': {
            const m = args.match(/^(.*?)\s+as\s+(?:\$([A-Za-z0-9_]+)\s*=>\s*)?\$([A-Za-z0-9_]+)$/);
            const [, expr, keyVar, valVar] = m;
            const id = N();
            push('for (const [idx' + id + ', _' + valVar + '] of _h.iter(' + translateExpr(expr, {}) + ').entries()) {');
            push('const _loop' + id + ' = { first: idx' + id + ' === 0, last: idx' + id + ' === _h.iter(' + translateExpr(expr, {}) + ').length - 1, index: idx' + id + ' };');
            push('_v[' + JSON.stringify(valVar) + '] = _' + valVar + '; _v[\'loop\'] = _loop' + id + '; _varMap[' + JSON.stringify(valVar) + '] = _' + valVar + '; _varMap.loop = _loop' + id + ';');
            break;
          }
          case 'endforeach': push('}'); break;
          case 'forelse': {
            const m = args.match(/^(.*?)\s+as\s+(?:\$([A-Za-z0-9_]+)\s*=>\s*)?\$([A-Za-z0-9_]+)$/);
            const [, expr, keyVar, valVar] = m;
            const id = N();
            push('{ const _arr' + id + ' = _h.iter(' + translateExpr(expr, {}) + '); if (_arr' + id + '.length) {');
            push('for (const [idx' + id + ', _' + valVar + '] of _arr' + id + '.entries()) {');
            push('const _loop' + id + ' = { first: idx' + id + ' === 0, last: idx' + id + ' === _arr' + id + '.length - 1, index: idx' + id + ' };');
            push('_v[' + JSON.stringify(valVar) + '] = _' + valVar + '; _v[\'loop\'] = _loop' + id + '; _varMap[' + JSON.stringify(valVar) + '] = _' + valVar + '; _varMap.loop = _loop' + id + ';');
            break;
          }
          case 'empty': push('} } else {'); break;
          case 'endforelse': push('} }'); break;
          case 'for': {
            const m = args.match(/^\s*\$([A-Za-z0-9_]+)\s*=\s*(.*?)\s*;\s*(.*?)\s*;\s*(.*?)\s*$/);
            const [, fv, init, cond, incr] = m;
            const id = N();
            let incrJs;
            if (/^\$[A-Za-z0-9_]+\+\+$/.test(incr.trim())) incrJs = '_lv' + id + '++';
            else if (/^\$[A-Za-z0-9_]+--$/.test(incr.trim())) incrJs = '_lv' + id + '--';
            else incrJs = translateExpr(incr, { [fv]: '_lv' + id });
            push('for (let _lv' + id + ' = ' + translateExpr(init, {}) + '; ' + translateExpr(cond, { [fv]: '_lv' + id }) + '; ' + incrJs + ') {');
            push('_varMap[' + JSON.stringify(fv) + '] = _lv' + id + '; _v[' + JSON.stringify(fv) + '] = _lv' + id + ';');
            break;
          }
          case 'endfor': push('}'); break;
          case 'isset': push('if ((' + translateExpr(args, {}) + ') !== undefined && (' + translateExpr(args, {}) + ') !== null) {'); break;
          case 'endisset': push('}'); break;
          case 'php': {
            const start = i;
            const end = template.indexOf('@endphp', start);
            const code = template.slice(start, end);
            i = end + '@endphp'.length;
            const stmts = [];
            parseStmts(code, {}, stmts, 0);
            push(stmts.join('\n'));
            break;
          }
          case 'csrf': push('_o.push(\'<input type="hidden" name="_token" value="csrf-token">\');'); break;
          case 'method': push('_o.push(\'<input type="hidden" name="_method" value="\' + ' + args.trim() + ' + \'">\');'); break;
          case 'json': push('_o.push(_h.jsonArr(' + translateExpr(args, {}) + '));'); break;
          case 'include': {
            const m = args.match(/^\s*['"]([^'"]+)['"]\s*(?:,\s*([\s\S]*))?$/);
            const [, incPath, params] = m;
            if (params) push('_o.push(_ctx.include(' + JSON.stringify(incPath) + ', ' + translateExpr(params, {}) + '));');
            else push('_o.push(_ctx.include(' + JSON.stringify(incPath) + ', {}));');
            break;
          }
          default:
            push('_o.push(' + JSON.stringify('@' + name + (args !== null ? '(' + args + ')' : '')) + ');');
        }
        continue;
      }
      let j = i;
      while (j < template.length && template[j] !== '@' && !template.startsWith('{{', j) && !template.startsWith('{!!', j)) j++;
      push('_o.push(' + JSON.stringify(template.slice(i, j)) + ');');
      i = j;
    }
  }

  return out.join('\n');
}

/* ---------------------------------------------------------- */
/*  Compiler API                                             */
/* ---------------------------------------------------------- */
class Compiler {
  constructor(vars, query) {
    this.rt = new Runtime(vars);
    if (query) this.rt.setQuery(query);
    this.cache = {};
    this.h = this.rt.h();
    this.h.inArray = (a, b) => (a || []).map(String).includes(String(b));
    this.h.strSub = (s, a, b) => String(s || '').substr(a, b);
    this.h.strSub0 = () => true;
    this.h.glob = (name) => {
      if (name === 'preg_match') return (p, s) => new RegExp(p).test(String(s || ''));
      if (name === 'trim') return (s) => String(s || '').trim();
      throw new Error('unknown global function: ' + name);
    };
    this.h.viewObj = () => ({ share: () => null });
  }
  resolve(name) {
    let p = path.join(VIEWS, name.replace(/\./g, '/') + '.blade.php');
    if (!fs.existsSync(p)) {
      /* try relative from layouts */
      p = path.join(VIEWS, name.replace(/\./g, '/') + '.blade.php');
    }
    if (!fs.existsSync(p)) throw new Error('view not found: ' + name);
    return p;
  }
  read(name) {
    if (!this.cache[name]) this.cache[name] = fs.readFileSync(this.resolve(name), 'utf8');
    return this.cache[name];
  }
  render(name, extraVars, query) {
    const ctx = this.rt;
    if (query) ctx.setQuery(query);
    const _v = Object.assign({}, ctx.vars, extraVars || {}, { errors: { any: () => false, first: () => '' } });
    ctx.vars = _v;
    const _h = ctx.h();
    /* merge extra helpers */
    for (const k of Object.keys(this.h)) _h[k] = this.h[k];
    _h.inArray = (needle, hay) => (hay && hay.items ? hay.items : (hay || [])).map(String).includes(String(needle));
    _h.strSub = (s, a, b) => String(s || '').substr(a, b);
    _h.glob = (n) => {
      if (n === 'preg_match') return (p, s) => new RegExp(p).test(String(s || ''));
      if (n === 'trim') return (s) => String(s || '').trim();
      return () => { throw new Error('unknown global fn ' + n); };
    };
    _h.viewObj = () => ({ share: () => null });
    const _o = [];
    const _varMap = {};

    const childTpl = this.read(name);
    let extendTo = null;
    const _ctx = {
      extend: (p) => { extendTo = p; },
      sections: {}, stacks: {},
      section: (n, v) => { this.rt.sections[n] = String(v === undefined || v === null ? '' : v); },
      beginSection: (n) => { },
      endSection: (html) => { },
      beginStack: (n) => { },
      endStack: (html) => { if (!this.rt.stacks[n]) this.rt.stacks[n] = []; this.rt.stacks[n].push(html); },
      include: (p, params) => {
        const child = new Compiler(Object.assign({}, this.rt.vars), this.rt.queryParams);
        child.rt.sections = this.rt.sections;
        child.rt.stacks = this.rt.stacks;
        child.rt.session = this.rt.session;
        child.rt.cart = this.rt.cart;
        return child.renderRaw(p, params);
      },
    };

    const childJs = compileTemplate(childTpl, 'child');
    const childFn = new Function('_o', '_v', '_varMap', '_h', '_ctx', '_include', childJs);
    const _include = _ctx.include;

    /* first pass: just find @extends */
    const extMatch = childTpl.match(/@extends\s*\(\s*['"]([^'"]+)['"]\s*\)/);
    if (extMatch) extendTo = extMatch[1];

    /* collect sections via a dedicated pass */
    const sectMatch = compileTemplate(childTpl, 'child');
    const sectCtx = {
      extend: () => { },
      sections: {},
      stacks: {},
      section: (n, v) => { this.rt.sections[n] = String(v === undefined || v === null ? '' : v); },
      beginSection: (n) => { this._curSec = n; this._tmpOut = []; this._outer = _o; },
      endSection: (html) => { this.rt.sections[this._curSec] = html; },
      beginStack: (n) => { this._curStk = n; this._tmpOut = []; },
      endStack: (html) => { if (!this.rt.stacks[this._curStk]) this.rt.stacks[this._curStk] = []; this.rt.stacks[this._curStk].push(html); },
      include: _ctx.include,
    };
    /* run the section-collector with its own output buffer */
    let collectFn;
    try { collectFn = new Function('_o', '_v', '_varMap', '_h', '_ctx', compileTemplate(childTpl, 'child')); }
    catch (err) { require('fs').writeFileSync('/tmp/child-gen.js', compileTemplate(childTpl, 'child')); throw err; }
    /* Wrap: the child template's section bodies need _o to be the buffer they push to */
    /* We'll run compileTemplate output but with _ctx = sectCtx and a swapped _o inside section body via generated code */
    /* The generated code does: { const _bN = _o; _o = []; ... _ctx.endSection(_o.join("")); _o = _bN; } — perfect with any ctx. */
    const secOut = [];
    collectFn(secOut, _v, _varMap, _h, sectCtx);
    /* now inject sectCtx.collected into this.rt.sections (already done via endSection) */

    /* render layout */
    if (!extendTo) {
      throw new Error('no @extends in ' + name);
    }
    const layoutTpl = this.read(extendTo);
    const layoutJs = compileTemplate(layoutTpl, 'layout');
    const layoutCtx = {
      sections: this.rt.sections,
      stacks: this.rt.stacks,
      include: _ctx.include,
    };
    let layoutFn;
    try { layoutFn = new Function('_o', '_v', '_varMap', '_h', '_ctx', layoutJs); }
    catch (err) { require('fs').writeFileSync('/tmp/layout-gen.js', layoutJs); throw err; }
    const out2 = [];
    layoutFn(out2, _v, _varMap, _h, layoutCtx);
    return out2.join('');
  }
  renderRaw(name, extraVars) {
    const _v = Object.assign({}, this.rt.vars, extraVars || {}, { errors: { any: () => false, first: () => '' } });
    const _h = this.rt.h();
    for (const k of Object.keys(this.h)) _h[k] = this.h[k];
    _h.inArray = (needle, hay) => (hay && hay.items ? hay.items : (hay || [])).map(String).includes(String(needle));
    _h.strSub = (s, a, b) => String(s || '').substr(a, b);
    const _o = [];
    const _varMap = {};
    const ctx = {
      extend: () => { }, sections: this.rt.sections, stacks: this.rt.stacks,
      section: (n, v) => { }, beginSection: (n) => { this._cur = n; this._tmp = _o; },
      endSection: () => { }, beginStack: () => { }, endStack: () => { },
      include: (p, params) => {
        const child = new Compiler(Object.assign({}, this.rt.vars));
        return child.renderRaw(p, params);
      },
    };
    const tpl = this.read(name);
    const js = compileTemplate(tpl, 'child');
    const fn = new Function('_o', '_v', '_varMap', '_h', '_ctx', js);
    fn(_o, _v, _varMap, _h, ctx);
    return _o.join('');
  }
}

module.exports = { Compiler, translateExpr };
