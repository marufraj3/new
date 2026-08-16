/* Static server for the storefront redesign preview. No dependencies. */
const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 8000;
const WWW = path.join(__dirname, 'www');

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.gif': 'image/gif',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.ttf': 'font/ttf',
  '.ico': 'image/x-icon',
  '.map': 'application/json',
};

function resolve(urlPath) {
  let p = urlPath.split('?')[0];
  if (p === '/') p = '/index.html';
  /* map /preview/assets → www/assets */
  if (p.startsWith('/preview/assets/')) {
    return path.join(WWW, 'assets', p.slice('/preview/assets/'.length));
  }
  /* map /public → www/public (repo public assets mirrored) */
  if (p.startsWith('/public/')) {
    return path.join(WWW, p);
  }
  return path.join(WWW, p);
}

const server = http.createServer((req, res) => {
  const file = resolve(req.url);
  const safe = path.normalize(file);
  if (!safe.startsWith(WWW)) {
    res.writeHead(403); res.end('forbidden'); return;
  }
  fs.readFile(safe, (err, buf) => {
    if (err) {
      /* fallback: repo public dir (serves original images referenced by templates) */
      const alt = path.join(__dirname, '..', 'public', req.url.split('?')[0].replace(/^\/public\//, ''));
      fs.readFile(alt, (err2, buf2) => {
        if (err2) { res.writeHead(404, { 'Content-Type': 'text/plain' }); res.end('404: ' + req.url); return; }
        const ext = path.extname(alt).toLowerCase();
        res.writeHead(200, { 'Content-Type': MIME[ext] || 'application/octet-stream' });
        res.end(buf2);
      });
      return;
    }
    const ext = path.extname(safe).toLowerCase();
    res.writeHead(200, { 'Content-Type': MIME[ext] || 'application/octet-stream' });
    res.end(buf);
  });
});

server.listen(PORT, '0.0.0.0', () => {
  console.log('Shop Genie design preview → http://0.0.0.0:' + PORT);
});
