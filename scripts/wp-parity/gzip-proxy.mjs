// A local gzip front for WordPress during LCP runs (docs/plan/11-WP-PORT.md 5.1, 5.5).
//
// PHP's built-in server sends no compression, while the prototype's LCP server (prototype/scripts/lcp.cjs)
// gzips text the way GitHub Pages does. This proxy puts WordPress on the same footing: it forwards every
// request to the WordPress instance and gzips text responses (level 9) with a Content-Length.
//
// WordPress prints absolute URLs with its own origin (WP_HOME), so text responses also have that origin
// rewritten to the proxy's, or the browser would fetch the assets around the proxy. That changes a few
// bytes per URL and nothing else.
//
//   node scripts/wp-parity/gzip-proxy.mjs http://127.0.0.1:8881 [port=0]
//
// Prints "listening http://127.0.0.1:<port>" once ready. The parity driver starts it, reads that line and
// stops it by PID when the LCP runs are done.
import http from "node:http";
import zlib from "node:zlib";

const target = new URL(process.argv[2] || "http://127.0.0.1:8881");
const port = Number(process.argv[3] || 0);
const TEXT = /^(text\/|application\/(json|javascript|xml|rss\+xml|ld\+json)|image\/svg)/;

const server = http.createServer((req, res) => {
  const self = `http://${req.headers.host}`;
  const headers = { ...req.headers, host: target.host, "accept-encoding": "identity" };
  const up = http.request({ hostname: target.hostname, port: target.port, path: req.url, method: req.method, headers }, (r) => {
    const chunks = [];
    r.on("data", (c) => chunks.push(c));
    r.on("end", () => {
      let body = Buffer.concat(chunks);
      const out = { ...r.headers };
      delete out["transfer-encoding"];
      delete out["content-length"];
      const type = String(r.headers["content-type"] || "");
      if (out.location) out.location = String(out.location).split(target.origin).join(self);
      if (TEXT.test(type)) {
        const text = body.toString("utf8");
        // Plain and JSON-escaped forms of the origin (wp_json_encode escapes slashes).
        const escaped = target.origin.replace(/\//g, "\\/");
        body = Buffer.from(text.split(target.origin).join(self).split(escaped).join(self.replace(/\//g, "\\/")), "utf8");
        if (/gzip/.test(String(req.headers["accept-encoding"] || ""))) {
          body = zlib.gzipSync(body, { level: 9 });
          out["content-encoding"] = "gzip";
          out.vary = "Accept-Encoding";
        }
      }
      // A length, as GitHub Pages sends: Chrome's low-entropy LCP rule reads it.
      out["content-length"] = body.length;
      res.writeHead(r.statusCode || 502, out).end(req.method === "HEAD" ? undefined : body);
    });
  });
  up.on("error", (e) => res.writeHead(502, { "content-type": "text/plain" }).end("proxy error: " + e.message));
  req.pipe(up);
});

server.listen(port, "127.0.0.1", () => {
  console.log(`listening http://127.0.0.1:${server.address().port}`);
});
for (const sig of ["SIGINT", "SIGTERM"]) process.on(sig, () => server.close(() => process.exit(0)));
