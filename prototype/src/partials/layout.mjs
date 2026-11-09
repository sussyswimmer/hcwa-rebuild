import { html, raw } from "../lib/html.mjs";
import { routes } from "../routes.mjs";
import { icon, button, pauseToggle, quatrefoil } from "./components.mjs";
import { worldCanvas } from "./world.mjs";
import { headInline } from "../lib/head-inline.mjs";
import { pageReveal } from "../motion/transitions.js";
import { viKeep } from "../lib/vikeep.mjs";
import { still, hasStill } from "./still.mjs";

// Absolute hreflang URLs need the deployed origin; Netlify sets URL during builds.
const BASE = (process.env.SITE_URL ?? process.env.URL ?? "").replace(/\/$/, "");

// Menu tree from 04-SITEMAP. Top items link to their hub; children open from a toggle.
const nav = (c) => [
  { key: "about", children: ["about:whoWeAre", "ourStory", "team", "partners", "policies"] },
  { key: "ourWork", children: ["ourWork:centers", "ourWork:programs", "ourWork:allWork"] },
  { key: "impact" },
  { key: "news" },
  { key: "getInvolved", children: ["donate", "volunteer", "partner:partnerWithUs", "inKind", "bakery"] },
  { key: "contact" },
];

// "route:label" lets a child reuse a hub URL under a different label.
const child = (c, spec) => {
  const [route, label = route] = spec.split(":");
  const hash = { centers: "#co-so", programs: "#du-an" }[label];
  const enHash = { centers: "#centers", programs: "#programs" }[label];
  const href = c.url(route) + (hash ? (c.lang === "vi" ? hash : enHash) : "");
  return { href, label: c.t(label === "partner" ? "partnerWithUs" : label) };
};

// "page" on the exact page, "true" on the parent hub of a child page.
const isCurrent = (c, key) =>
  c.routeKey === key ? "page" : (key === "ourWork" && ["greenBamboo", "thriveTogether"].includes(c.routeKey)) || (key === "getInvolved" && c.routeKey === "donate") ? "true" : "";

const langSwitch = (c, cls = "") => {
  const r = routes[c.routeKey];
  // Visible "VI" stays inside the accessible name (WCAG 2.5.3): "VI (Tiếng Việt)".
  const link = (l, name) =>
    html`<a href="${r[l]}" lang="${l}" hreflang="${l}"${l === c.lang ? raw(' aria-current="true"') : ""}>${l.toUpperCase()}<span class="sr-only"> (${name})</span></a>`;
  return html`<div class="lang-switch${cls ? " " + cls : ""}" role="group" aria-label="${c.t("language")}" data-lang-switch>${link("vi", "Tiếng Việt")}<span aria-hidden="true">/</span>${link("en", "English")}</div>`;
};

const desktopNav = (c) => html`<nav class="nav" aria-label="${c.t("mainNav")}" data-text-safe>
  <ul class="nav__list">
    ${nav(c).map(
      (item, i) => html`<li class="nav__item${item.children ? " has-sub" : ""}">
        <a class="nav__link" href="${c.url(item.key)}"${isCurrent(c, item.key) ? html` aria-current="${isCurrent(c, item.key)}"` : ""}>${c.t(item.key)}</a>
        ${item.children
          ? html`<button class="nav__toggle" type="button" aria-expanded="false" aria-controls="sub-${i}" aria-label="${c.t("submenu", { x: c.t(item.key) })}">${icon("chevron")}</button>
          <ul class="nav__sub" id="sub-${i}" hidden>${item.children.map((s) => {
            const ch = child(c, s);
            return html`<li><a href="${ch.href}">${ch.label}</a></li>`;
          })}</ul>`
          : ""}
      </li>`,
    )}
  </ul>
</nav>`;

const mobileMenu = (c) => html`<dialog class="sheet" id="menu-sheet" aria-labelledby="menu-title">
  <div class="sheet__head">
    <p class="sheet__title" id="menu-title">${c.t("menu")}</p>
    <button class="icon-btn" type="button" data-close-sheet aria-label="${c.t("close")}">${icon("close")}</button>
  </div>
  <nav aria-label="${c.t("mainNav")}">
    <ul class="sheet__list">
      ${nav(c).map((item) =>
        item.children
          ? html`<li><details><summary>${c.t(item.key)}${icon("chevron")}</summary><ul>
              <li><a href="${c.url(item.key)}">${c.t(item.key)}</a></li>
              ${item.children.map((s) => {
                const ch = child(c, s);
                return html`<li><a href="${ch.href}">${ch.label}</a></li>`;
              })}</ul></details></li>`
          : html`<li><a class="sheet__link" href="${c.url(item.key)}">${c.t(item.key)}</a></li>`,
      )}
    </ul>
  </nav>
  <div class="sheet__foot">${button({ href: c.url("donate"), label: c.t("donate") })}${langSwitch(c)}</div>
</dialog>`;

const logo = (c) => {
  const s = c.content.site;
  return html`<a class="logo" href="${c.url("home")}" data-text-safe>
    <picture><source type="image/webp" srcset="/img/hcwa-logo.webp"><img src="/img/hcwa-logo.png" width="97" height="128" alt="${c.tx(s.orgName)}"></picture>
    <span class="logo__name" aria-hidden="true">${s.logoLines[c.lang].map((l) => html`<span>${l}</span>`)}</span>
  </a>`;
};

// 05-DESIGN D7 header: transparent with a scrim over the home hero, solid lime-50 elsewhere and
// after 80 px of scroll. Never hides. At 390: mark, VI / EN, Ủng hộ and a "Menu" text button.
const header = (c, over) => html`<div class="header-slot${over ? " header-slot--over" : ""}">
  <header class="site-header${over ? " site-header--over" : ""}" data-header>
    <div class="container site-header__bar">
      ${logo(c)}
      ${desktopNav(c)}
      <div class="site-header__tools" data-text-safe>
        ${langSwitch(c, "lang-switch--header")}
        ${button({ href: c.url("donate"), label: c.t("donate"), attrs: "data-donate", cls: "btn--header" })}
        <button class="menu-btn" type="button" aria-haspopup="dialog" aria-controls="menu-sheet" data-open-sheet>${c.t("menu")}</button>
      </div>
    </div>
  </header>
</div>`;

// H11 the dusk footer, every page (05-DESIGN D7): the room at 18:00 as a still band (once the world has
// rendered it), a row of breeze blocks against the evening, the house's name in big type, four columns
// (accordions below 1024 px) with the day's last sun by the address, and the blue-950 bar.
const footer = (c) => {
  const s = c.content.site;
  // A disclosure button below 1024 px (ui/footer.js folds the columns there), a plain heading above it.
  // Without JS every column stays open.
  let n = 0;
  const col = (title, body) => {
    const id = `foot-col-${++n}`;
    return html`<div class="foot-col"><h2 class="foot-col__title"><span class="foot-col__label">${title}</span><button class="foot-col__toggle" type="button" aria-expanded="true" aria-controls="${id}">${quatrefoil("foot-col__mark")}<span>${title}</span>${icon("chevron")}</button></h2><div class="foot-col__body" id="${id}">${body}</div></div>`;
  };
  const links = (keys) => html`<ul>${keys.map((k) => html`<li><a href="${c.url(k)}">${c.t(k)}</a></li>`)}</ul>`;
  return html`<footer class="site-footer">
    ${hasStill("dusk") ? html`<div class="site-footer__still" aria-hidden="true">${still("dusk", "site-footer__dusk")}</div>` : ""}
    <div class="footer-edge" aria-hidden="true"></div>
    <div class="container site-footer__sign">
      <picture><source type="image/webp" srcset="/img/hcwa-logo.webp"><img src="/img/hcwa-logo.png" width="73" height="96" alt="" loading="lazy"></picture>
      <p class="site-footer__name">${viKeep(c.tx(s.orgName))}</p>
    </div>
    <div class="site-footer__cols"><div class="container site-footer__grid">
      <div class="foot-brand">
        <p${c.d(s.description)}>${c.tx(s.description)}</p>
        <p class="foot-brand__reg"${c.d(s.registration)}>${c.tx(s.registration)}</p>
      </div>
      ${col(c.t("contact"), html`<ul class="foot-contact" data-draft>
        <li class="foot-contact__address">${quatrefoil("foot-contact__sun")}<span>${c.tx(s.address)}</span></li>
        <li>${icon("phone")}<a href="tel:${s.phone.tel}">${s.phone.display}</a></li>
        <li>${icon("mail")}<a href="mailto:${s.email.general}">${s.email.general}</a></li>
        <li>${icon("external")}<a href="${s.mapUrl}">${c.t("openMap")}</a></li>
      </ul>`)}
      ${col(c.t("explore"), links(["about", "ourWork", "impact", "news", "getInvolved"]))}
      ${col(c.t("trust"), html`<ul>
        <li><a href="${c.url("reports")}">${c.t("annualReports")}</a></li>
        <li><a href="${c.url("safeguarding")}">${c.t("safeguarding")}</a></li>
        <li><a href="${c.url("privacy")}">${c.t("privacy")}</a></li>
        <li><a href="${c.url("partners")}">${c.t("partners")}</a></li>
      </ul>`)}
    </div></div>
    <div class="site-footer__bar">
      <div class="container site-footer__bottom">
        <p>© ${new Date().getFullYear()} HCWA</p>
        <p class="foot-social">${c.t("followUs")}: <a href="${s.facebook}">Facebook</a></p>
        ${langSwitch(c)}
        ${pauseToggle(c, "pause-toggle--dusk")}
      </div>
    </div>
  </footer>`;
};


// English-speaking visitors on VI pages get a dismissible pointer to /en/ (07-BILINGUAL).
const enBar = (c) =>
  c.lang === "vi"
    ? html`<aside class="en-bar" aria-label="${c.t("enBarLabel")}" data-en-bar hidden>
        <div class="container en-bar__inner" lang="en">
          <a href="${routes[c.routeKey].en}" hreflang="en">${c.t("enBar")}${icon("arrow")}</a>
          <button class="icon-btn icon-btn--sm" type="button" data-dismiss-en-bar aria-label="${c.t("enBarDismiss")}">${icon("close")}</button>
        </div>
      </aside>`
    : "";

// Staging only. The reviewer toggle shows the dashed draft outlines for this session; off by default (05-DESIGN D7).
const prototypeNote = (c) => (c.prod ? "" : html`<div class="proto-note"><div class="container proto-note__inner"><p><span class="proto-note__full">${c.t("prototypeNote")}</span><span class="proto-note__short">${c.t("prototypeNoteShort")}</span></p><button class="proto-note__toggle" type="button" data-draft-toggle>${c.t("draftShow")}</button></div></div>`);

// The View Transition type (back, lang) is chosen in pagereveal, before first render: a classic script in
// <head>, after head-inline.js has set data-motion (motion/transitions.js).
const vtScript = raw(`<script>(${pageReveal})();</script>`);

// Strings the client script needs, in the page language.
const clientStrings = (c) =>
  raw(`<script type="application/json" id="i18n">${JSON.stringify(
    Object.fromEntries(["copy", "copied", "copyFailed", "f_sending", "f_send", "e_summary", "close", "motionPause", "motionPlay", "draftHide", "draftShow"].map((k) => [k, c.t(k)])),
  ).replace(/</g, "\\u003c")}</script>`);

// lit: the page opens on a lit header (base still + light layer), so the head script may set sun-pending.
// The bar stays above the still (its feathered foot crosses the still's ceiling), so a transom or a
// screen at the top of a still is never under the nav.
// world: the page carries the fixed world canvas (home); its header starts transparent over the hero.
export function layout(c, { title, description, body, bodyClass = "", lit = false, world = false }) {
  const r = routes[c.routeKey];
  const site = c.tx(c.content.site.shortName);
  const fullTitle = c.routeKey === "home" ? `${c.tx(c.content.site.orgName)} (HCWA)` : `${title} | ${site}`;
  return html`<!doctype html>
<html lang="${c.lang}"${lit ? raw(" data-lit") : ""}>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
${headInline(c.prod)}
${vtScript}
<title>${fullTitle}</title>
<meta name="description" content="${description}">
${c.prod ? "" : raw('<meta name="robots" content="noindex, nofollow">')}
<link rel="alternate" hreflang="vi" href="${BASE + r.vi}" vite-ignore>
<link rel="alternate" hreflang="en" href="${BASE + r.en}" vite-ignore>
<link rel="alternate" hreflang="x-default" href="${BASE + r.vi}" vite-ignore>
<meta property="og:title" content="${fullTitle}">
<meta property="og:description" content="${description}">
<meta property="og:locale" content="${c.lang === "vi" ? "vi_VN" : "en_GB"}">
<link rel="preload" href="/fonts/archivo-w78-600-700.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/be-vietnam-pro-400.woff2" as="font" type="font/woff2" crossorigin>
<link rel="icon" href="/img/favicon-32.png" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="/img/apple-touch-icon.png">
<script type="module" src="/main.js"></script>
${clientStrings(c)}
</head>
<body class="${bodyClass}">
<a class="skip-link" href="#main">${c.t("skip")}</a>
${prototypeNote(c)}
${enBar(c)}
${header(c, world)}
${world ? worldCanvas() : ""}
<main id="main" tabindex="-1">
${body}
</main>
${footer(c)}
${mobileMenu(c)}
</body>
</html>
`;
}
