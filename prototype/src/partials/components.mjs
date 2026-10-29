import { html, raw } from "../lib/html.mjs";
import { visibleFigures } from "../lib/content.mjs";

// Lucide outline paths (ISC), drawn on a 24px grid at 1.5px stroke (05-DESIGN icons).
const PATHS = {
  arrow: '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
  chevron: '<path d="m6 9 6 6 6-6"/>',
  menu: '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
  close: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
  copy: '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
  alert: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
  info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
  phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
  mail: '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
  pin: '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
  external: '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
  prev: '<path d="m15 18-6-6 6-6"/>',
  next: '<path d="m9 18 6-6-6-6"/>',
  search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
};
export const icon = (name, cls = "icon") =>
  raw(`<svg class="${cls}" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${PATHS[name]}</svg>`);

// Buttons: primary (sun), secondary (blue outline), text link with arrow.
export const button = ({ href, label, kind = "primary", attrs = "", cls = "" }) =>
  href
    ? html`<a class="btn btn--${kind}${cls ? " " + cls : ""}" href="${href}"${raw(attrs ? " " + attrs : "")}>${label}</a>`
    : html`<button class="btn btn--${kind}${cls ? " " + cls : ""}" type="button"${raw(attrs ? " " + attrs : "")}>${label}</button>`;

export const textLink = (href, label, attrs = "") =>
  html`<a class="text-link" href="${href}"${raw(attrs ? " " + attrs : "")}><span>${label}</span>${icon("arrow")}</a>`;

// Labeled image slot (08 task 1.7): asset code from 10-ASSET-REQUEST, no stock photos.
export const placeholder = (c, img, extra = "") => {
  const label = `${img.code} · ${c.tx(img.label)} · ${img.ratio}`;
  const [w, h] = img.ratio.split(":");
  return html`<div class="ph${img.illustration ? " ph--art" : ""}${extra ? " " + extra : ""}" role="img" aria-label="${label}" style="--ratio:${w}/${h}"><span class="ph__code">${img.code}</span><span class="ph__label">${c.tx(img.label)} · ${img.ratio}</span></div>`;
};

export const pendingChip = (c) => html`<span class="chip-pending" data-pending>${c.t("pending")}</span>`;

// Impact figure: verified value, or a token + "Pending verification" chip (prototype only).
export const figure = (c, f) => {
  if (f.verified && typeof f.value !== "number") throw new Error(`Figure ${f.id} is verified but has no numeric value`);
  return figureHtml(c, f);
};
const figureHtml = (c, f) => html`<div class="figure"${c.d(f.label)}>
  <p class="figure__value">${f.verified ? c.number(f.value) + (f.suffix ?? "") : f.token}</p>
  <p class="figure__label">${c.tx(f.label)}${f.year ? html` <span class="figure__year">(${f.year})</span>` : ""}</p>
  ${f.verified ? "" : pendingChip(c)}
</div>`;

// Renders the strip only when at least `min` figures may show (06: homepage needs 2, else 1).
export const figureStrip = (c, scope, { min = 1, cls = "" } = {}) => {
  const figs = visibleFigures(c.content.impact.filter((f) => f.scope === scope), c.prod);
  if (figs.length < min) return "";
  return html`<div class="figures${cls ? " " + cls : ""}">${figs.map((f) => figure(c, f))}</div>`;
};

export const unitDot = (accent) => html`<span class="unit-dot" style="--unit:var(--color-unit-${accent})" aria-hidden="true"></span>`;

export const centerCard = (c, center) => html`<article class="center-card" style="--unit:var(--color-unit-${center.accent})">
  ${placeholder(c, center.image)}
  <div class="center-card__body">
    <h3 class="center-card__name"><a href="${c.tx(center.path)}">${c.tx(center.name)}</a></h3>
    <p class="center-card__since" data-draft>${c.t("since")} ${c.tx(center.sinceToken)}</p>
    <p class="center-card__serves"${c.d(center.serves)}>${c.tx(center.serves)}</p>
    <span class="text-link" aria-hidden="true"><span>${c.t("visitCenter")}</span>${icon("arrow")}</span>
  </div>
</article>`;

export const statusPill = (c, status) => html`<span class="status status--${status}">${c.t("status_" + status)}</span>`;

export const programRow = (c, p) => html`<li class="program-row" data-areas="${p.areas.join(" ")}" data-status="${p.status}">
  <div class="program-row__meta">${statusPill(c, p.status)}<span class="program-row__years" data-draft>${c.tx(p.years)}</span></div>
  <div class="program-row__main">
    <h3 class="program-row__name"><a href="${c.tx(p.path)}">${c.tx(p.name)}</a></h3>
    <p${c.d(p.summary)}>${c.tx(p.summary)}</p>
    ${p.partners.length ? html`<p class="program-row__partners">${p.partners.join(", ")}</p>` : ""}
  </div>
  ${icon("arrow", "icon program-row__arrow")}
</li>`;

export const storyCard = (c, s, { featured = false } = {}) => html`<article class="story-card${featured ? " story-card--featured" : ""}"${s.draft ? raw(" data-draft") : ""}>
  ${placeholder(c, s.image)}
  <p class="label">${c.t("cat_" + s.category)}</p>
  <h3 class="story-card__title"><a href="${c.url("news")}">${c.tx(s.title)}</a></h3>
  <p class="story-card__excerpt">${c.tx(s.excerpt)}</p>
  <p class="story-card__note">${c.t("namesChanged")}</p>
</article>`;

export const newsItem = (c, p) => html`<li class="news-item">
  <p class="news-item__meta"><span class="label">${c.t("cat_" + p.category)}</span><time datetime="${p.published}">${c.date(p.published)}</time></p>
  <h3 class="news-item__title"><a href="${c.tx(p.url)}">${c.tx(p.title)}</a></h3>
</li>`;

export const reportRow = (c, { title, year, type, docLang, size }) => html`<li class="report-row" data-draft>
  ${placeholder(c, { code: "C3", label: { vi: "Bìa báo cáo", en: "Report cover" }, ratio: "3:4" }, "report-row__cover")}
  <div>
    <h3 class="report-row__title">${title}</h3>
    <p class="report-row__meta">${year} · ${type}</p>
  </div>
  <span class="badge">PDF · <span lang="${docLang === "English" ? "en" : "vi"}">${docLang}</span></span>
  <span class="report-row__size">${size}</span>
</li>`;

export const ctaBand = (c, { title, actions, extra = "", draft = false }) => html`<section class="cta-band" aria-labelledby="cta-title">
  <div class="container cta-band__inner">
    <h2 id="cta-title"${draft ? raw(" data-draft") : ""}>${title}</h2>
    <div class="cta-band__actions">${actions}</div>
    ${extra}
  </div>
</section>`;

export const breadcrumb = (c, trail) => html`<nav class="breadcrumb" aria-label="${c.t("breadcrumb")}">
  <ol>
    <li><a href="${c.url("home")}">${c.t("home")}</a></li>
    ${trail.map(([label, href], i) => html`<li>${i === trail.length - 1 ? html`<span aria-current="page">${label}</span>` : html`<a href="${href}">${label}</a>`}</li>`)}
  </ol>
</nav>`;

export const langNotice = (c) => html`<aside class="lang-notice" lang="${c.lang}">
  ${icon("info")}
  <div>
    <p>${c.t("langNotice")}</p>
    <p class="lang-notice__links">${textLink("#", c.t("langNoticeRead"))} ${textLink(c.url("news"), c.t("langNoticeHub"))}</p>
  </div>
</aside>`;

// Form field: label above, helper, error below, all tied with aria-describedby (05-DESIGN).
export const field = (c, { id, label, type = "text", required = false, help, error, autocomplete, options, rows }) => {
  const describe = [help && `${id}-help`, `${id}-error`].filter(Boolean).join(" ");
  const common = raw(`id="${id}" name="${id}" aria-describedby="${describe}"${required ? " required" : ""}${autocomplete ? ` autocomplete="${autocomplete}"` : ""}`);
  const control = options
    ? html`<select ${common}><option value="">${options.placeholder}</option>${options.items.map(([v, l]) => html`<option value="${v}">${l}</option>`)}</select>`
    : rows
      ? html`<textarea ${common} rows="${rows}"></textarea>`
      : html`<input type="${type}" ${common}>`;
  return html`<div class="field" data-field>
    <label for="${id}">${label}</label>
    ${help ? html`<p class="field__help" id="${id}-help">${help}</p>` : ""}
    ${control}
    <p class="field__error" id="${id}-error" data-error="${error}" hidden>${icon("alert")}<span></span></p>
  </div>`;
};

export const sectionHead = (c, { id, title, intro, link, draftIntro }) => html`<div class="section-head">
  <h2 id="${id}">${title}</h2>
  ${intro ? html`<p class="section-head__intro"${draftIntro ? raw(" data-draft") : ""}>${intro}</p>` : ""}
  ${link ?? ""}
</div>`;
