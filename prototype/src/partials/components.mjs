import { html, raw } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { holes, gachStrip, gachSpriteOnce } from "./blocks.mjs";
import { still, hasStill } from "./still.mjs";

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
  pause: '<rect x="7" y="5" width="3" height="14" rx="1"/><rect x="14" y="5" width="3" height="14" rx="1"/>',
  play: '<path d="M8 5.5v13a1 1 0 0 0 1.5.87l11-6.5a1 1 0 0 0 0-1.74l-11-6.5A1 1 0 0 0 8 5.5Z"/>',
  file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
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

export const pendingChip = (c) => html`<span class="chip-pending" data-pending>${c.t("pending")}</span>`;

export const statusPill = (c, status) => html`<span class="status status--${status}">${c.t("status_" + status)}</span>`;

// Program row (D7): rows, never cards; places are doors, programs are light passing through. The centers it
// runs at show as small door chips in their unit paint, names only (Little Rose: never a place or a link).
// Fix r2: the row carries the floor's pigments. Its areas are a strip of the same gạch tiles (one per area,
// decoration only: the areas are not named here; the light stays sunlight, never tinted). The page's tile
// sprite comes with the first row when nothing before it brought one.
export const programRow = (c, p) => {
  const centers = p.centers.map((id) => c.content.work.centers.find((x) => x.id === id)).filter(Boolean);
  const strip = gachStrip(p.areas, "gach-strip program-row__tiles");
  return html`<li class="program-row" data-areas="${p.areas.join(" ")}" data-status="${p.status}">
  <div class="program-row__head">
    ${strip ? html`<span class="program-row__floor" aria-hidden="true">${gachSpriteOnce(c)}${strip}</span>` : ""}
    <p class="program-row__meta">${statusPill(c, p.status)}<span class="program-row__years" data-draft>${c.tx(p.years)}</span></p>
    <h3 class="program-row__name"><a href="${c.tx(p.path)}">${viKeep(c.tx(p.name))}</a></h3>
  </div>
  <div class="program-row__body">
    <p class="program-row__summary"${c.d(p.summary)}>${c.tx(p.summary)}</p>
    ${p.partners.length || centers.length ? html`<dl class="program-row__facts">
      ${p.partners.length ? html`<div><dt>${c.t("fundedBy")}</dt><dd>${p.partners.join(", ")}</dd></div>` : ""}
      ${centers.length ? html`<div><dt>${c.t("runsAt")}</dt><dd><ul class="door-chips">${centers.map((x) => doorChip(c, x))}</ul></dd></div>` : ""}
    </dl>` : ""}
  </div>
  ${icon("arrow", "icon program-row__arrow")}
</li>`;
};

export const doorChip = (c, center) => html`<li class="door-chip" style="--unit:var(--color-unit-${center.accent})"><span class="door-chip__door" aria-hidden="true"></span>${c.tx(center.shortName)}</li>`;

// News list item with a big Archivo date (H6, typography-first). The date reads as one string for
// assistive tech ("25/05/2026", "25 May 2026"); only the year drops to its own line.
export const datedPost = (c, p) => {
  const [, dm, sep, year] = c.date(p.published).match(/^(.*?)(\D)(\d{4})$/);
  return html`<li class="dated">
  <time class="dated__date" datetime="${p.published}"><span class="dated__dm">${dm}</span><span class="sr-only">${sep}</span><span class="dated__y">${year}</span></time>
  <div class="dated__body">
    <p class="dated__cat">${c.t("cat_" + p.category)}</p>
    <h3 class="dated__title"><a href="${c.tx(p.url)}">${c.tx(p.title)}<span class="sr-only"> (${c.t("externalPost")})</span></a>${icon("external", "icon dated__ext")}</h3>
  </div>
</li>`;
};

// The day ends (05-DESIGN D0.5, H10): an evening sky from the page's last light into blue-900, the room's
// screen at 18:00 rising out of it (the dusk still, once rendered), the band's title and actions on the
// night. Pages that close on it carry the still here, so the footer starts at its parapet.
export const duskStill = () => (hasStill("dusk") ? html`<div class="dusk__still" aria-hidden="true">${still("dusk", "dusk__img")}</div>` : "");
export const duskBand = (c, { id, title, draft = false, body }) => html`<section class="dusk" aria-labelledby="${id}">
  ${duskStill()}
  <div class="container dusk__inner">
    <h2 class="dusk__title" id="${id}"${draft ? raw(" data-draft") : ""}>${viKeep(title)}</h2>
    ${body}
  </div>
</section>`;

export const breadcrumb = (c, trail) => html`<nav class="breadcrumb" aria-label="${c.t("breadcrumb")}">
  <ol>
    <li><a href="${c.url("home")}">${c.t("home")}</a></li>
    ${trail.map(([label, href], i) => html`<li>${i === trail.length - 1 ? html`<span aria-current="page">${label}</span>` : html`<a href="${href}">${label}</a>`}</li>`)}
  </ol>
</nav>`;

// Form field: label above, helper, error below, all tied with aria-describedby (05-DESIGN).
export const field = (c, { id, label, type = "text", required = false, help, error, autocomplete, options, rows }) => {
  const describe = [help && `${id}-help`, `${id}-error`].filter(Boolean).join(" ");
  const common = raw(`id="${id}" name="${id}" aria-describedby="${describe}"${required ? " required" : ""}${autocomplete ? ` autocomplete="${autocomplete}"` : ""}`);
  const control = options
    ? html`<span class="select"><select ${common}><option value="">${options.placeholder}</option>${options.items.map(([v, l]) => html`<option value="${v}">${l}</option>`)}</select></span>`
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

// Radio choices as pills (contact topics): one fieldset, one error, required as a group. The first radio
// carries the group id, so the error summary's link lands on it.
export const choiceField = (c, { id, label, items, required = false, error, value }) => html`<fieldset class="field field--choices" data-field aria-describedby="${id}-error">
    <legend>${label}</legend>
    <div class="choices">${items.map(([v, l], i) => html`<label class="choice"><input type="radio" id="${i ? `${id}-${v}` : id}" name="${id}" value="${v}"${v === value ? raw(" checked") : ""}${required ? raw(" required") : ""}><span>${l}</span></label>`)}</div>
    <p class="field__error" id="${id}-error" data-error="${error}" hidden>${icon("alert")}<span></span></p>
  </fieldset>`;

// The consent line every form ends on (CLAUDE.md: the wording is HCWA's to approve, so it stays draft).
export const consentField = (c, id = "consent") => html`<div class="field field--check" data-field>
    <input type="checkbox" id="${id}" name="${id}" required aria-describedby="${id}-error">
    <label for="${id}"><span data-draft>${c.t("f_consent")}</span> <a href="${c.url("privacy")}">${c.t("privacy")}</a></label>
    <p class="field__error" id="${id}-error" data-error="${c.t("e_consent")}" hidden>${icon("alert")}<span></span></p>
  </div>`;

// A form's two closing states: sending (the button) and sent (this panel replaces the fields; the
// prototype says plainly that nothing left the browser).
export const formSent = (c, message, { draft = false } = {}) => html`<div class="form__success" data-success tabindex="-1" role="status" hidden>
    ${icon("check")}
    <div class="form__success-text">
      <p class="form__sent"${draft ? raw(" data-draft") : ""}>${message}</p>
      ${c.prod ? "" : html`<p class="form__note">${c.t("f_prototypeSent")}</p>`}
      <button class="text-link form__again" type="button" data-form-again><span>${c.t("f_sendAnother")}</span>${icon("arrow")}</button>
    </div>
  </div>`;

// Copy button (D5.7): disabled until HCWA finance confirms the value it would copy; describedBy points at
// the line that says so. The accessible name starts with the visible label (WCAG 2.5.3).
export const copyButton = (c, { value = "", label, describedBy, disabled = !value } = {}) =>
  html`<button class="copy-btn" type="button" data-copy="${value}"${disabled ? raw(" disabled") : ""} aria-label="${c.t("copy")}: ${label}"${describedBy ? html` aria-describedby="${describedBy}"` : ""}>${icon("copy", "icon copy-btn__copy")}${icon("check", "icon copy-btn__done")}<span>${c.t("copy")}</span></button>`;

// The last sun of the day: one Hoa thị quatrefoil in sun-400, from the block's own numbers (footer address,
// the contact page's office).
export const quatrefoil = (cls = "quatrefoil") => raw(`<svg class="${cls}" viewBox="-112 -112 224 224" aria-hidden="true" focusable="false"><path d="${holes("hoaThi").split("Z")[0]}Z"/></svg>`);

// WCAG 2.2.2 pause (05-DESIGN D5.6): one pattern, a button whose label says what it will do. Playing:
// "Tạm dừng chuyển động" with the pause icon; paused: "Bật lại chuyển động" with the play icon and a
// sun-100 fill (ui/pause-toggle.js swaps them; no aria-pressed, the label carries the state). Hidden
// without JS and when the OS already reduces motion. In the hero it is a
// text button in the flow under the text; textSafe makes the framing solver keep the screen off it
// ("display": its 14 px label targets WCAG AA over the floor, never the silhouette).
export const pauseToggle = (c, cls = "", { textSafe } = {}) => html`<button class="pause-toggle${cls ? " " + cls : ""}" type="button" data-paused="false" data-motion-toggle${textSafe ? raw(` data-text-safe="${textSafe}"`) : ""}>
  ${icon("pause", "icon pause-toggle__pause")}${icon("play", "icon pause-toggle__play")}<span class="pause-toggle__label">${c.t("motionPause")}</span>
</button>`;

// The office's phone and email on the night of a dusk band (the same lines Get Involved ends on).
export const reachLines = (c, email = c.content.site.email.general) => {
  const { site } = c.content;
  return html`<dl class="dusk__lines" data-draft>
      <div><dt>${c.t("phone")}</dt><dd><a href="tel:${site.phone.tel}">${site.phone.display}</a></dd></div>
      <div><dt>${c.t("email")}</dt><dd><a href="mailto:${email}">${email}</a></dd></div>
    </dl>`;
};
