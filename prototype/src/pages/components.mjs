import { readFileSync } from "node:fs";
import { html, raw } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { litStill, stillReady } from "../lib/posters.mjs";
import { parse } from "../../scripts/tokens-to-js.mjs";
import { button, textLink, icon, breadcrumb, statusPill, pendingChip, programRow, doorChip, datedPost, field, choiceField, consentField, copyButton, pauseToggle } from "../partials/components.mjs";
import { screenArt, KINDS, AREA_BLOCK, gachStrip, gachSpriteOnce } from "../partials/blocks.mjs";
import { louver } from "../partials/louvers.mjs";
import { doorWall } from "./work.mjs";

// The components page (PLAN P8, prototype only: render.mjs skips it in PROD). Every component in every
// state (05-DESIGN D7), the colour tokens with their contrast measured at build time, the block set,
// the light layer and the motion tokens with live demos (still under reduced motion), a tier switcher
// and the reviewer notes HCWA has to sign off. Its copy lives in content/draft/kit.json.
const read = (rel) => readFileSync(new URL(rel, import.meta.url), "utf8");
const K = JSON.parse(read("../../../content/draft/kit.json"));
const TOKENS = parse(read("../styles/tokens.css"));
const PAIRS = JSON.parse(read("../../../qa/contrast-pairs.json"));

// UI pairs (field borders, focus rings, unit paint) are graphics at 3:1, so their sample is a stroke, not
// text. WCAG 2.x relative luminance and contrast ratio, from the token hex values.
const lum = (hex) => {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255).map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};
const contrast = (a, b) => {
  const [hi, lo] = [lum(a), lum(b)].sort((x, y) => y - x);
  return (hi + 0.05) / (lo + 0.05);
};
const MIN = { texto: 4.5, "texto-grande": 3, ui: 3 };
const KIND = { texto: "pairText", "texto-grande": "pairLarge", ui: "pairUi" };
const tok = (v) => v.replace(/^--color-/, "");

// Which block stands for what (D6.5, PLAN H5a): the focus areas, the two unit emblems, the main screen.
const UNIT_EMBLEM = { dotTre: "green-bamboo", vomCau: "cau-han" };

const EASES = ["out", "in-out", "drawer", "sun", "hover"];
const DURS = ["press", "hover", "ui", "sheet", "reveal", "flood", "sun-in", "sun"];

export function components(c) {
  const { work, news, involve, site } = c.content;
  const k = (f) => c.tx(f);
  const S = K.sections;
  const sec = (id, inner, note) => html`<section class="kit-sec" id="k-${id}" aria-labelledby="k-${id}-t">
    <h2 class="kit-sec__title" id="k-${id}-t">${k(S[id])}</h2>
    ${note ? html`<p class="kit-sec__note"${c.d(note)}>${k(note)}</p>` : ""}
    ${inner}
  </section>`;
  const state = (name, inner) => html`<figure class="kit-state"><div class="kit-state__demo">${inner}</div><figcaption>${k(K.states[name] ?? K.kinds[name])}</figcaption></figure>`;

  // ---- type ----
  const R = K.typeRoles;
  const typeRows = [
    ["display", "--fs-display", "display"], ["h1", "--fs-h1", "display"], ["h2", "--fs-h2", "display"], ["h3", "--fs-h3", "display"],
    ["statement", "--fs-statement", "statement"], ["lead", "--fs-lg", "lead"], ["body", "--fs-base", "body"], ["small", "--fs-sm", "body"], ["meta", "--fs-meta", "body"],
  ];
  const type = html`<div class="specimen">
    ${typeRows.map(([role, v, kind]) => html`<div class="specimen__row"><p class="specimen__tag">${k(R[role])} <code>${v}</code></p><p class="specimen__line specimen__line--${kind}" lang="vi" style="font-size:var(${v})">${k(K.testLine)}</p></div>`)}
    <div class="specimen__row"><p class="specimen__tag">${k(R.figure)} <code>tabular-nums</code></p><p class="specimen__line specimen__line--figure num">0123456789 · 12.345 · 25/05/2026</p></div>
  </div>`;

  // ---- colour ----
  const swatches = Object.entries(TOKENS.colors).filter(([n]) => !n.startsWith("world-"));
  const color = html`<ul class="swatches">${swatches.map(([n, hex]) => html`<li><span class="swatch" style="background:var(--color-${n})"></span><code>--color-${n}</code><span class="swatch__hex">${hex}</span></li>`)}</ul>
  <div class="pairs-box"><table class="pairs">
    <caption class="sr-only">${k(S.color)}</caption>
    <tbody>${PAIRS.map((p) => {
      const fg = TOKENS.colors[tok(p.texto)], bg = TOKENS.colors[tok(p.fondo)];
      const r = contrast(fg, bg), ok = r >= MIN[p.tipo];
      return html`<tr class="pair${ok ? "" : " pair--fail"}">
        <td>${p.tipo === "ui"
          ? html`<span class="pair__sample pair__sample--ui" style="--fg:var(${p.texto});background:var(${p.fondo})" aria-hidden="true"></span>`
          : html`<span class="pair__sample" style="color:var(${p.texto});background:var(${p.fondo})" aria-hidden="true">Ắa</span>`}</td>
        <td><code>${tok(p.texto)}</code> <span class="pair__on">${k(K.pairOn)}</span> <code>${tok(p.fondo)}</code></td>
        <td class="pair__kind">${k(K[KIND[p.tipo]])} · ${MIN[p.tipo]}:1</td>
        <td class="pair__ratio num">${r.toFixed(2)}:1</td>
        <td class="pair__result">${ok ? k(K.pass) : k(K.fail)}</td>
      </tr>`;
    })}</tbody>
  </table></div>`;

  // ---- block set ----
  const usesOf = (kind) => [
    ...work.focusAreas.filter((a) => AREA_BLOCK[a.id] === kind).map((a) => `${k(K.blockUse.area)}: ${c.tx(a.name)}`),
    ...(UNIT_EMBLEM[kind] ? [`${k(K.blockUse.unit)}: ${c.tx(work.centers.find((x) => x.id === UNIT_EMBLEM[kind]).shortName)}`] : []),
    ...(kind === "hoaThi" ? [k(K.blockUse.screen)] : []),
  ];
  const blocks = html`<ul class="blockset">${KINDS.map((kind) => html`<li class="blockset__item">
    ${screenArt(kind, { cols: 2, rows: 2, cls: "block-art blockset__art" })}
    <p class="blockset__name">${k(K.blocks[kind])}</p>
    <p class="blockset__use">${usesOf(kind).join(" · ")}</p>
  </li>`)}</ul>`;

  // ---- light ----
  const light = stillReady("header-our-work")
    ? html`<div class="kit-light" data-kit-light>${litStill("header-our-work")}</div>
      <p class="kit-row"><button class="btn btn--secondary" type="button" data-kit-sun>${k(K.replay)}</button></p>`
    : "";

  // ---- buttons ----
  const buttons = html`<div class="kit-states">
      ${state("rest", button({ label: k(K.kinds.primary) }))}
      ${state("hover", button({ label: k(K.kinds.primary), cls: "is-hover" }))}
      ${state("focus", button({ label: k(K.kinds.primary), cls: "is-focus" }))}
      ${state("active", button({ label: k(K.kinds.primary), cls: "is-active" }))}
      ${state("disabled", button({ label: k(K.kinds.primary), attrs: "disabled" }))}
      ${state("loading", html`<button class="btn btn--primary" type="button" aria-busy="true" aria-disabled="true"><span class="spinner" aria-hidden="true"></span>${c.t("f_sending")}</button>`)}
    </div>
    <div class="kit-states">
      ${state("rest", button({ label: k(K.kinds.secondary), kind: "secondary" }))}
      ${state("hover", button({ label: k(K.kinds.secondary), kind: "secondary", cls: "is-hover" }))}
      ${state("focus", button({ label: k(K.kinds.secondary), kind: "secondary", cls: "is-focus" }))}
      ${state("disabled", button({ label: k(K.kinds.secondary), kind: "secondary", attrs: "disabled" }))}
      ${state("textLink", textLink("#k-buttons", c.t("readMore")))}
    </div>
    <div class="kit-states kit-states--dusk">
      ${state("onDusk", button({ label: c.t("contactUs"), kind: "on-dusk" }))}
      ${state("focus", button({ label: c.t("contactUs"), kind: "on-dusk", cls: "is-focus" }))}
      ${state("rest", pauseToggle(c, "pause-toggle--dusk"))}
    </div>`;

  // ---- chips ----
  const area = work.focusAreas[0];
  const chips = html`${gachSpriteOnce(c)}<div class="kit-states">
      ${state("rest", html`<a class="chip" href="#k-chips">${gachStrip([area.id], "gach-strip chip__tile")}${c.tx(area.name)}</a>`)}
      ${state("chosen", html`<a class="chip" href="#k-chips" aria-current="true">${gachStrip([area.id], "gach-strip chip__tile")}${c.tx(area.name)}</a>`)}
      ${state("rest", html`<span class="kit-inline">${statusPill(c, "active")}${statusPill(c, "completed")}${statusPill(c, "planned")}</span>`)}
      ${state("rest", pendingChip(c))}
      ${state("rest", html`<ul class="door-chips">${work.centers.map((x) => doorChip(c, x))}</ul>`)}
    </div>
    <div class="kit-states">
      ${state("rest", html`<nav class="seg" aria-label="${c.t("filterStatus")}"><a class="seg__opt" href="#k-chips" aria-current="true">${c.t("all")}</a>${["active", "completed"].map((s) => html`<a class="seg__opt" href="#k-chips"><span class="seg__light seg__light--${s}" aria-hidden="true"></span>${c.t("status_" + s)}</a>`)}</nav>`)}
    </div>`;

  // ---- rows ----
  const rows = html`<ul class="program-list">${work.programs.slice(0, 2).map((p) => programRow(c, p))}</ul>
    <ol class="dated-list kit-dated">${news.posts.slice(0, 2).map((p) => datedPost(c, p))}</ol>
    <ul class="louvers kit-louvers">${involve.ways.slice(0, 2).map((w) => louver(c, w))}</ul>`;

  // ---- figures ----
  const sample = { value: 12345, label: K.sampleNote };
  const pending = c.content.impact.find((f) => !f.verified);
  const figures = html`<ul class="counts__list">
    <li class="count"><p class="count__value num">${c.number(sample.value)}</p><p class="count__label">${k(sample.label)}</p></li>
    ${pending ? html`<li class="count" data-draft><p class="count__value num">${pending.token}</p><p class="count__label">${c.tx(pending.label)}</p>${pendingChip(c)}</li>` : ""}
  </ul>`;

  // ---- forms ----
  const topics = ["donate", "partner", "volunteer"].map((t) => [t, c.t("topic_" + t)]);
  const forms = html`<div class="kit-forms">
      ${field(c, { id: "k-name", label: c.t("f_name"), autocomplete: "off", error: c.t("e_name") })}
      ${field(c, { id: "k-email", label: c.t("f_email"), type: "email", help: c.t("f_emailHelp"), autocomplete: "off", error: c.t("e_email") })}
      <div class="field is-invalid" data-field>
        <label for="k-err">${c.t("f_email")}</label>
        <input id="k-err" type="email" value="ten@" aria-invalid="true" aria-describedby="k-err-error">
        <p class="field__error" id="k-err-error">${icon("alert")}<span>${c.t("e_email")}</span></p>
      </div>
      <div class="field" data-field><label for="k-dis">${c.t("f_phone")}</label><input id="k-dis" disabled></div>
      ${field(c, { id: "k-topic", label: c.t("f_topic"), options: { placeholder: c.t("f_topicChoose"), items: topics }, error: "" })}
      ${choiceField(c, { id: "k-choice", label: c.t("f_topic"), items: topics, error: c.t("e_topic"), value: "partner" })}
      ${consentField(c, "k-consent")}
    </div>
    <div class="kit-forms kit-forms--states">
      <div class="form__summary" role="presentation"><p>${c.t("e_summary")}</p><ul><li><a href="#k-err">${c.t("e_email")}</a></li><li><a href="#k-choice">${c.t("e_topic")}</a></li></ul></div>
      <div class="form__success">${icon("check")}<div class="form__success-text"><p class="form__sent">${c.t("f_sent")}</p><p class="form__note">${c.t("f_prototypeSent")}</p><span class="text-link"><span>${c.t("f_sendAnother")}</span>${icon("arrow")}</span></div></div>
    </div>`;

  // ---- disclosure ----
  const ask = (f, open) => html`<details class="ask"${open ? raw(" open") : ""}><summary><span class="ask__q">${c.tx(f.q)}</span><span class="ask__mark" aria-hidden="true">${icon("chevron")}</span></summary><div class="ask__a"><p data-draft>${c.tx(involve.faqAnswer)}</p></div></details>`;
  const disclosure = html`<div class="faq__list kit-faq">${ask(involve.faq[0], false)}${ask(involve.faq[1], true)}</div>`;

  // ---- payment ----
  const D = involve.donatePage;
  const pay = html`<div class="slip" data-tabs>
      <div class="tabs" role="tablist" aria-label="${k(D.tabsLabel)}" hidden>
        <button class="tabs__tab" type="button" role="tab" id="tab-k-pay-a" aria-controls="k-pay-a" aria-selected="true">${k(D.inVietnamShort)}</button>
        <button class="tabs__tab" type="button" role="tab" id="tab-k-pay-b" aria-controls="k-pay-b" aria-selected="false" tabindex="-1">${k(D.fromAbroadShort)}</button>
      </div>
      <div class="slip__panels">
        <section class="slip__panel" id="k-pay-a" aria-labelledby="k-pay-a-t"><h3 class="slip__title" id="k-pay-a-t">${k(D.inVietnam)}</h3>
          <div class="slip__body"><dl class="slip__rows">
            <div class="slip__row"><dt>${c.tx(involve.bankFields[1].label)}</dt><dd><span class="pending-value" data-donation-placeholder>${c.t("pendingFinance")}</span></dd>${copyButton(c, { label: c.tx(involve.bankFields[1].label), describedBy: "k-pay-note" })}</div>
            <div class="slip__row"><dt>${k(K.copySample)}</dt><dd><code>${K.copySampleValue}</code></dd>${copyButton(c, { value: K.copySampleValue, label: k(K.copySample) })}</div>
          </dl>
          <div class="slip__qr" role="img" aria-label="${k(D.qr)}: ${c.t("pendingFinance")}" data-donation-placeholder><span class="slip__qr-name">${k(D.qr)}</span><span class="pending-value">${c.t("pendingFinance")}</span></div></div>
        </section>
        <section class="slip__panel" id="k-pay-b" aria-labelledby="k-pay-b-t"><h3 class="slip__title" id="k-pay-b-t">${k(D.fromAbroad)}</h3>
          <div class="slip__body"><dl class="slip__rows"><div class="slip__row"><dt>${c.tx(involve.abroadFields[1].label)}</dt><dd><span class="pending-value" data-donation-placeholder>${c.t("pendingFinance")}</span></dd>${copyButton(c, { label: c.tx(involve.abroadFields[1].label), describedBy: "k-pay-note" })}</div></dl></div>
        </section>
      </div>
      <p class="slip__note" id="k-pay-note">${icon("info")}<span>${k(K.payNote)}</span></p>
    </div>
    <p class="sr-only" aria-live="polite" data-live></p>`;

  // ---- navigation ----
  const navigation = html`<div class="kit-states">
      ${state("rest", breadcrumb(c, [[c.t("ourWork"), "#k-nav"], [c.tx(work.centers[0].name)]]))}
      ${state("rest", html`<div class="lang-switch" role="group" aria-label="${c.t("language")}"><a href="#k-nav" lang="vi" aria-current="true">VI</a><span aria-hidden="true">/</span><a href="#k-nav" lang="en">EN</a></div>`)}
      ${state("rest", pauseToggle(c))}
    </div>`;

  // ---- dusk ----
  const dusk = html`<div class="kit-dusk">
      <p class="kit-dusk__title">${viKeep(c.tx(site.contactBand.title))}</p>
      <dl class="dusk__lines" data-draft><div><dt>${c.t("phone")}</dt><dd><a href="tel:${site.phone.tel}">${site.phone.display}</a></dd></div></dl>
      <div class="kit-inline">${button({ href: "#k-dusk", label: c.t("donate") })}${button({ href: "#k-dusk", label: c.t("contactUs"), kind: "on-dusk" })}</div>
    </div>`;

  // ---- motion ----
  const track = (name, ease, dur) => html`<li class="track" style="--e:var(--ease-${ease});--d:var(--d-${dur})">
      <code class="track__name">${name}</code><span class="track__rail"><span class="track__dot"></span></span>
    </li>`;
  const motion = html`<div class="kit-motion">
      <div class="kit-motion__group" data-kit-demo>
        <div class="kit-motion__head"><h3 class="kit-motion__title">${k(K.easings)}</h3><button class="btn btn--secondary btn--small" type="button" data-kit-play>${icon("next")}<span>${k(K.play)}</span></button></div>
        <ul class="tracks">${EASES.map((e) => track(`--ease-${e}`, e, "sun"))}</ul>
      </div>
      <div class="kit-motion__group" data-kit-demo>
        <div class="kit-motion__head"><h3 class="kit-motion__title">${k(K.durations)}</h3><button class="btn btn--secondary btn--small" type="button" data-kit-play>${icon("next")}<span>${k(K.play)}</span></button></div>
        <ul class="tracks">${DURS.map((d) => track(`--d-${d} · ${TOKENS.dur[d.replace(/-(\w)/g, (_, x) => x.toUpperCase())]} ms`, "out", d))}</ul>
      </div>
    </div>`;

  // ---- tiers ----
  const home = c.url("home");
  const tiers = html`<ul class="tier-links">${[["t0", "?tier=0"], ["t1", "?tier=1"], ["t2", "?tier=2"], ["off", "?motion=off"]].map(([id, q]) => html`<li>${textLink(home + q, k(K.tierLinks[id]))}</li>`)}</ul>`;

  // ---- review ----
  const review = html`<div class="kit-review">
      <p class="kit-review__note"${c.d(K.reviewLittleRose)}>${k(K.reviewLittleRose)}</p>
      <p class="kit-review__note"${c.d(K.reviewTraces)}>${k(K.reviewTraces)}</p>
      <p class="kit-review__label">${k(K.reviewDoors)}</p>
    </div>`;

  const order = ["type", "color", "blocks", "light", "buttons", "chips", "rows", "figures", "forms", "disclosure", "pay", "nav", "dusk", "motion", "tiers", "review"];
  const body = html`
<header class="sun-head sun-head--kit">
  <div class="container sun-head__inner">
    ${breadcrumb(c, [[k(K.title)]])}
    <h1 class="sun-head__title">${viKeep(k(K.title))}</h1>
    <div class="sun-head__side"><p class="sun-head__intro">${k(K.intro)}</p></div>
  </div>
</header>
<div class="day day--kit">
  <div class="container kit">
    <nav class="kit__index" aria-labelledby="kit-index"><p class="kit__index-title" id="kit-index">${k(K.index)}</p>
      <ol>${order.map((id) => html`<li><a href="#k-${id}">${k(S[id])}</a></li>`)}</ol>
    </nav>
    <div class="kit__body">
      ${sec("type", type, K.typeNote)}
      ${sec("color", color, K.colorNote)}
      ${sec("blocks", blocks, K.blocksNote)}
      ${light ? sec("light", light, K.lightNote) : ""}
      ${sec("buttons", buttons, K.buttonsNote)}
      ${sec("chips", chips)}
      ${sec("rows", rows)}
      ${sec("figures", figures, K.figuresNote)}
      ${sec("forms", forms)}
      ${sec("disclosure", disclosure)}
      ${sec("pay", pay)}
      ${sec("nav", navigation)}
      ${sec("dusk", dusk)}
      ${sec("motion", motion, K.motionNote)}
      ${sec("tiers", tiers, K.tiersNote)}
      ${sec("review", html`${review}${doorWall(c, work.centers, { cls: "kit-doors" })}`)}
    </div>
  </div>
</div>`;
  return { title: k(K.title), description: k(K.intro), body, bodyClass: "page-kit" };
}
