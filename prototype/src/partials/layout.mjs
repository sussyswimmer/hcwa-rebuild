import { html, raw } from "../lib/html.mjs";
import { routes } from "../routes.mjs";
import { icon, button } from "./components.mjs";

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
  return html`<div class="lang-switch${cls ? " " + cls : ""}" role="group" aria-label="${c.t("language")}">${link("vi", "Tiếng Việt")}<span aria-hidden="true">/</span>${link("en", "English")}</div>`;
};

const desktopNav = (c) => html`<nav class="nav" aria-label="${c.t("mainNav")}">
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
  return html`<a class="logo" href="${c.url("home")}">
    <img src="/img/hcwa-logo.png" width="97" height="128" alt="${c.tx(s.orgName)}">
    <span class="logo__name" aria-hidden="true">${s.logoLines[c.lang].map((l) => html`<span>${l}</span>`)}</span>
  </a>`;
};

const header = (c) => html`<div class="header-slot">
  <header class="site-header">
    <div class="container site-header__bar">
      ${logo(c)}
      ${desktopNav(c)}
      <div class="site-header__tools">
        ${langSwitch(c, "lang-switch--header")}
        ${button({ href: c.url("donate"), label: c.t("donate"), attrs: 'data-donate' })}
        <button class="icon-btn menu-btn" type="button" aria-haspopup="dialog" aria-controls="menu-sheet" data-open-sheet>${icon("menu")}<span class="sr-only">${c.t("menu")}</span></button>
      </div>
    </div>
  </header>
</div>`;

const footer = (c) => {
  const s = c.content.site;
  const col = (title, body) => html`<details class="foot-col" open><summary><h2>${title}</h2>${icon("chevron")}</summary><div class="foot-col__body">${body}</div></details>`;
  const links = (keys) => html`<ul>${keys.map((k) => html`<li><a href="${c.url(k)}">${c.t(k)}</a></li>`)}</ul>`;
  return html`<footer class="site-footer">
    <div class="container site-footer__grid">
      <div class="foot-brand">
        <img src="/img/hcwa-logo.png" width="73" height="96" alt="" loading="lazy">
        <p class="foot-brand__name">${c.tx(s.orgName)}</p>
        <p${c.d(s.description)}>${c.tx(s.description)}</p>
        <p class="foot-brand__reg"${c.d(s.registration)}>${c.tx(s.registration)}</p>
      </div>
      ${col(c.t("contact"), html`<ul class="foot-contact" data-draft>
        <li>${icon("pin")}<span>${c.tx(s.address)}</span></li>
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
    </div>
    <div class="container site-footer__bottom">
      <p>© ${new Date().getFullYear()} HCWA</p>
      <p class="foot-social"><a href="${s.facebook}">Facebook</a></p>
      ${langSwitch(c)}
    </div>
  </footer>`;
};

// English-speaking visitors on VI pages get a dismissible pointer to /en/ (07-BILINGUAL).
const enBar = (c) =>
  c.lang === "vi"
    ? html`<div class="en-bar" lang="en" data-en-bar hidden>
        <div class="container en-bar__inner">
          <a href="${routes[c.routeKey].en}" hreflang="en">${c.t("enBar")}${icon("arrow")}</a>
          <button class="icon-btn icon-btn--sm" type="button" data-dismiss-en-bar aria-label="${c.t("enBarDismiss")}">${icon("close")}</button>
        </div>
      </div>`
    : "";

const prototypeNote = (c) => (c.prod ? "" : html`<div class="proto-note"><div class="container"><p><span class="proto-note__full">${c.t("prototypeNote")}</span><span class="proto-note__short">${c.t("prototypeNoteShort")}</span></p></div></div>`);

// Strings the client script needs, in the page language.
const clientStrings = (c) =>
  raw(`<script type="application/json" id="i18n">${JSON.stringify(
    Object.fromEntries(["copy", "copied", "copyFailed", "f_sending", "f_send", "e_summary", "close"].map((k) => [k, c.t(k)])),
  ).replace(/</g, "\\u003c")}</script>`);

export function layout(c, { title, description, body, bodyClass = "" }) {
  const r = routes[c.routeKey];
  const site = c.tx(c.content.site.shortName);
  const fullTitle = c.routeKey === "home" ? `${c.tx(c.content.site.orgName)} (HCWA)` : `${title} | ${site}`;
  return html`<!doctype html>
<html lang="${c.lang}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${fullTitle}</title>
<meta name="description" content="${description}">
${c.prod ? "" : raw('<meta name="robots" content="noindex, nofollow">')}
<link rel="alternate" hreflang="vi" href="${BASE + r.vi}" vite-ignore>
<link rel="alternate" hreflang="en" href="${BASE + r.en}" vite-ignore>
<link rel="alternate" hreflang="x-default" href="${BASE + r.vi}" vite-ignore>
<meta property="og:title" content="${fullTitle}">
<meta property="og:description" content="${description}">
<meta property="og:locale" content="${c.lang === "vi" ? "vi_VN" : "en_GB"}">
<link rel="preload" href="/fonts/be-vietnam-pro-400.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/fraunces-vf.woff2" as="font" type="font/woff2" crossorigin>
<link rel="icon" href="/img/favicon-32.png" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="/img/apple-touch-icon.png">
<script type="module" src="/main.js"></script>
${clientStrings(c)}
</head>
<body class="${bodyClass}">
<a class="skip-link" href="#main">${c.t("skip")}</a>
${prototypeNote(c)}
${enBar(c)}
${header(c)}
<main id="main" tabindex="-1">
${body}
</main>
${footer(c)}
${mobileMenu(c)}
</body>
</html>
`;
}
