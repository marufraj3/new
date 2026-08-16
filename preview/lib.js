/* Shared runtime helpers + Collection for the static design preview. */

class Collection {
  constructor(items = []) { this.items = Array.isArray(items) ? items : []; }
  get length() { return this.items.length; }
  [Symbol.iterator]() { return this.items[Symbol.iterator](); }

  all() { return this.items; }
  count() { return this.items.length; }
  first() { return this.items[0]; }
  last() { return this.items[this.items.length - 1]; }
  take(n) { return new Collection(this.items.slice(0, n)); }
  skip(n) { return new Collection(this.items.slice(n)); }
  map(fn) { return new Collection(this.items.map(fn)); }
  filter(fn) { return new Collection(this.items.filter(fn || ((v) => v))); }
  pluck(key, key2) { return new Collection(this.items.map(i => i ? i[key] : undefined).filter(v => v !== undefined && v !== null)); }
  unique(key) {
    const seen = new Set();
    return new Collection(this.items.filter(i => {
      const k = key ? (i && i[key]) : JSON.stringify(i);
      if (seen.has(k)) return false;
      seen.add(k);
      return true;
    }));
  }
  sortBy(key) {
    return new Collection([...this.items].sort((a, b) => ((a && a[key]) ?? 0) - ((b && b[key]) ?? 0)));
  }
  contains(fn) { return this.items.some(fn); }
  sum(fn) { return this.items.reduce((acc, i) => acc + (fn ? Number(fn(i) || 0) : Number(i || 0)), 0); }
  avg(key) {
    if (!this.items.length) return 0;
    const vals = this.items.map(i => (typeof key === 'function' ? key(i) : i ? i[key] : undefined)).filter(v => v !== undefined && v !== null);
    if (!vals.length) return 0;
    return vals.reduce((a, b) => a + Number(b), 0) / vals.length;
  }
  where(key, op, val) {
    if (val === undefined) { val = op; op = '='; }
    return new Collection(this.items.filter(i => {
      const v = i ? i[key] : undefined;
      switch (op) {
        case '=': case '==': return String(v) == String(val);
        case '!=': return String(v) != String(val);
        case '>': return v > val; case '<': return v < val;
        case '>=': return v >= val; case '<=': return v <= val;
        default: return String(v) == String(val);
      }
    }));
  }
  whereIn(key, vals) { return new Collection(this.items.filter(i => vals.map(String).includes(String(i ? i[key] : undefined)))); }
  whereNotIn(key, vals) { return new Collection(this.items.filter(i => !vals.map(String).includes(String(i ? i[key] : undefined)))); }
  latest() { return new Collection([...this.items].reverse()); }
  inRandomOrder() { return new Collection([...this.items].sort(() => Math.random() - 0.5)); }
  orderBy() { return this; }
  limit(n) { return this.take(n); }
  get() { return this; }
  toArray() { return this.items; }

  /* paginator emulation */
  paginate(n) {
    const self = this;
    return {
      items: self.items,
      count() { return self.items.length; },
      total() { return self.items.length; },
      [Symbol.iterator]() { return self.items[Symbol.iterator](); },
      onEachSide() { return this; },
      links() { return ''; },
    };
  }
}

function C(items) { return new Collection(items); }

module.exports = { Collection, C };


/* Date formatting for mock data (mirrors Carbon::format for common tokens) */
Date.prototype.format = function (fmt) {
  var M = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  var s = String(fmt);
  s = s.replace('d', String(this.getDate()).padStart(2, '0'));
  s = s.replace('M', M[this.getMonth()]);
  s = s.replace('Y', String(this.getFullYear()));
  var h24 = this.getHours();
  s = s.replace('h', String(h24 % 12 || 12).padStart(2, '0'));
  s = s.replace('i', String(this.getMinutes()).padStart(2, '0'));
  s = s.replace('A', h24 >= 12 ? 'PM' : 'AM');
  return s;
};
