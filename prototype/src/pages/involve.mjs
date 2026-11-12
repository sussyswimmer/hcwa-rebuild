import { html, raw } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { still as stillPicture, stillReady } from "../lib/posters.mjs";
import { button, textLink, field, choiceField, consentField, formSent, copyButton, quatrefoil, icon, breadcrumb, duskBand } from "../partials/components.mjs";
import { litHeader } from "../partials/lit-header.mjs";
import { litWall } from "../partials/still.mjs";
import { stage, litStageVars, skipOpening } from "../partials/scroll3d.mjs";
import { involveWindow } from "../partials/involve-window.mjs";
import { contactGate } from "../partials/contact-gate.mjs";
import { blockIcon } from "../partials/blocks.mjs";

// Get Involved, Donate, Contact and the 404 (PLAN P4-P7). Same day as the rest of the site (05-DESIGN
// D0.5): each page is one stretch of light from its header to the dusk, no section fills, no rules.
// Get Involved is late afternoon through louvers; Donate keeps every picture away from the payment
// details; Contact is the street side of the screen; the 404 is the wall with one block missing.

// The phone and email on the night (home's contact band, D7): every page that ends on a dusk band
// with nothing else to say ends on these.
const reachLines = (c, email) => {
  const { site } = c.content;
  return html`<dl class="dusk__lines" data-draft>
      <div><dt>${c.t("phone")}</dt><dd><a href="tel:${site.phone.tel}">${site.phone.display}</a></dd></div>
      <div><dt>${c.t("email")}</dt><dd><a href="mailto:${email}">${email}</a></dd></div>
    </dl>`;
};

// ---------- Get Involved (P4) ----------

// "Cửa mở" (M6): the first glance says come in. The header is a room in late-morning light with its
// window's blue shutters thrown open onto a sunny street (partials/involve-window.mjs), a welcoming H1,
// and the four ways to help as a bar of tiles, each with its own button, all above the fold at 390 and
// 1440. On the stage (html.stages, motion on) the shutters, already open, swing wide and the street comes
// in as the visitor scrolls (styles/pages.css, the --s3d-cam track); at rest they stand wide open. Below
// it (M7, Maxwell 12 Nov: the four ways were listed twice in a row) no second list of the same names and buttons:
// what each way involves and where it starts, then the questions, then the evening.
const WAY_ROUTE = { donate: "donate", volunteer: "volunteer", partner: "partner", "in-kind": "inKind" };

const waysBar = (c, involve) => html`<nav class="ways" aria-label="${c.tx(involve.waysLabel)}">
  <ul class="ways__list">${involve.ways.map((w) => {
    const donate = w.id === "donate";
    return html`<li class="way way--${w.id}">
      <span class="way__tile" aria-hidden="true">${blockIcon("hoaThi", "way__block")}</span>
      ${button({ href: c.url(WAY_ROUTE[w.id]), label: donate ? c.t("donate") : c.tx(w.cta), kind: donate ? "primary" : "secondary", cls: "way__btn", attrs: !donate && w.cta.draft ? "data-draft" : "" })}
    </li>`;
  })}</ul>
</nav>`;

// "Mỗi cách bắt đầu thế nào" (M7): the detail under the header's tiles, not a repeat of them. Each way says what it
// involves (its line from involve.json), what is in it (the subpage's own list: the volunteer areas, the partner
// forms) and where to start, as text links into the right part of its page (the donate page's two ways to give, what
// to expect and the child-safety rules, the partner form, the needs list and the bakery). Every word comes from
// content/draft; nothing new is claimed. No buttons here: the tiles above carry the one button per way.
const waysStart = (c) => {
  const { involve, involvePages: M } = c.content;
  const D = involve.donatePage;
  const pay = PAY_IDS[c.lang];
  const V = M.volunteer, P = M.partner, K = M.inKind;
  // [href, words] pairs: into the right part of the way's own page.
  const at = (route, id, words) => [c.url(route) + "#" + id, c.tx(words)];
  const detail = {
    donate: { links: [at("donate", pay.vn, D.inVietnam), at("donate", pay.abroad, D.fromAbroad), at("donate", pay.tell, D.tellTitle)] },
    volunteer: { label: V.lookTitle, list: V.areas.map((a) => a.title), links: [at("volunteer", "expect-title", V.expectTitle), at("volunteer", "safe-title", V.safetyTitle)] },
    partner: { label: P.waysTitle, list: P.ways.map((w) => w.title), links: [at("partner", "partner-form", P.contactTitle)] },
    "in-kind": { note: K.first, links: [at("inKind", "needs-title", K.needsTitle), [c.url("bakery"), c.t("bakery")]] },
  };
  return html`<section class="start container" aria-labelledby="start-title">
    <h2 class="start__title" id="start-title"${c.d(involve.startTitle)} data-reveal="rise"><span>${viKeep(c.tx(involve.startTitle))}</span></h2>
    <ul class="start__ways" data-reveal="stagger">${involve.ways.map((w) => {
      const x = detail[w.id];
      return html`<li class="start__way start__way--${w.id}" id="way-${w.id}">
        <h3 class="start__name"><span class="start__tile" aria-hidden="true">${blockIcon("hoaThi", "start__block")}</span><span>${viKeep(c.tx(w.title))}</span></h3>
        <p class="start__text"${c.d(w.text)}>${c.tx(w.text)}</p>
        ${x.list ? html`<p class="start__label">${c.tx(x.label)}</p><ul class="start__list">${x.list.map((t) => html`<li>${c.tx(t)}</li>`)}</ul>` : ""}
        ${x.note ? html`<p class="start__note"${c.d(x.note)}>${c.tx(x.note)}</p>` : ""}
        <ul class="start__go">${x.links.map(([href, words]) => html`<li>${textLink(href, words)}</li>`)}</ul>
      </li>`;
    })}</ul>
  </section>`;
};

export function getInvolved(c) {
  const { involve, site } = c.content;
  const head = html`<header class="gi-head">
  ${involveWindow()}
  <div class="container gi-head__text">
    ${breadcrumb(c, [[c.t("getInvolved")]])}
    <h1 class="gi-head__title"${c.d(involve.welcomeTitle)}>${viKeep(c.tx(involve.welcomeTitle))}</h1>
    <p class="gi-head__intro"${c.d(involve.intro)}>${c.tx(involve.intro)}</p>
    ${waysBar(c, involve)}
  </div>
</header>`;
  const body = html`
${stage("involve", head, {}, { min: 0.6 })}
<div class="day day--involve">
  ${waysStart(c)}
  <section class="faq container" aria-labelledby="faq-title">
    <h2 class="faq__title" id="faq-title" data-reveal="rise"><span>${viKeep(c.tx(involve.faqTitle))}</span></h2>
    <div class="faq__list" data-reveal="stagger">
      ${involve.faq.map((f) => html`<details class="ask">
        <summary><span class="ask__q">${c.tx(f.q)}</span><span class="ask__mark" aria-hidden="true">${icon("chevron")}</span></summary>
        <div class="ask__a"><p data-draft>${c.tx(involve.faqAnswer)}</p></div>
      </details>`)}
    </div>
  </section>
</div>
${duskBand(c, {
  id: "gi-contact",
  title: c.tx(site.contactBand.title),
  body: html`${reachLines(c, site.email.general)}${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk", cls: "dusk__btn" })}`,
})}`;
  return { title: c.t("getInvolved"), description: c.tx(involve.intro), body, bodyClass: "page-involve" };
}

// ---------- Donate (P5) ----------

// Fragment ids in the page language: the header's jump links and the tabs share them.
const PAY_IDS = { vi: { vn: "trong-nuoc", abroad: "tu-nuoc-ngoai", tell: "bao-cho-hoi" }, en: { vn: "in-vietnam", abroad: "from-abroad", tell: "tell-us" } };
const PAY_NOTE = "pay-note";

// One bank form, as a transfer slip: every value is HCWA finance's to confirm, so every value shows the
// pending chip and carries data-donation-placeholder (PROD refuses the page until content/verified/
// donation.json exists). Nothing in the slip animates (D5.1).
const slipRows = (c, fields) => html`<dl class="slip__rows">${fields.map((f) => html`<div class="slip__row">
    <dt>${c.tx(f.label)}</dt>
    <dd><span class="pending-value" data-donation-placeholder>${c.t("pendingFinance")}</span>${f.hint ? html`<span class="slip__hint">${c.tx(f.hint)}</span>` : ""}</dd>
    ${f.copy ? copyButton(c, { label: c.tx(f.label), describedBy: PAY_NOTE }) : ""}
  </div>`)}</dl>`;

// The QR frame stays empty: a dashed outline in the pending colour, never a drawing that could pass
// for a code.
const qrSlot = (c, D) => html`<div class="slip__qr" role="img" aria-label="${c.tx(D.qr)}: ${c.t("pendingFinance")}" data-donation-placeholder>
    <span class="slip__qr-name">${c.tx(D.qr)}</span><span class="pending-value">${c.t("pendingFinance")}</span>
  </div>`;

export function donate(c) {
  const { involve, site, work } = c.content;
  const D = involve.donatePage;
  const id = PAY_IDS[c.lang];
  const tabs = [[id.vn, D.inVietnamShort], [id.abroad, D.fromAbroadShort]];
  // Gift examples carry [amount] tokens: staging only until HCWA sets the amounts.
  const gifts = c.prod
    ? ""
    : html`<section class="gifts container" aria-labelledby="gift-title">
    <h2 class="gifts__title" id="gift-title">${c.tx(D.giftTitle)}</h2>
    <ul class="gifts__list" data-reveal="stagger">${involve.giftExamples.map((g) => html`<li class="gift" data-draft>
      <span class="gift__amount" data-pending>${c.tx(g.amount)}</span>
      <span class="gift__text">${c.tx(g.text)}</span>
    </li>`)}</ul>
  </section>`;
  const body = html`
<header class="sun-head">
  <span class="sun-layer sun-layer--donate" aria-hidden="true"></span>
  <div class="container sun-head__inner">
    ${breadcrumb(c, [[c.t("getInvolved"), c.url("getInvolved")], [c.t("donate")]])}
    <h1 class="sun-head__title">${viKeep(c.t("donate"))}</h1>
    <div class="sun-head__side">
      <p class="sun-head__intro"${c.d(D.intro)}>${c.tx(D.intro)}</p>
      ${button({ href: "#" + id.vn, label: c.tx(D.inVietnam), cls: "sun-head__go" })}
      <ul class="sun-head__jump">
        <li>${textLink("#" + id.abroad, c.tx(D.fromAbroad))}</li>
        <li>${textLink("#" + id.tell, c.tx(D.tellTitle))}</li>
      </ul>
    </div>
  </div>
</header>
<div class="day day--donate">
  ${gifts}
  <div class="give-grid container">
    <section class="slip" aria-labelledby="how-title" data-tabs>
      <h2 class="slip__head" id="how-title">${c.tx(D.howTitle)}</h2>
      <div class="tabs" role="tablist" aria-label="${c.tx(D.tabsLabel)}" hidden>
        ${tabs.map(([panel, label], i) => html`<button class="tabs__tab" type="button" role="tab" id="tab-${panel}" aria-controls="${panel}" aria-selected="${i ? "false" : "true"}"${i ? raw(' tabindex="-1"') : ""}>${c.tx(label)}</button>`)}
      </div>
      <div class="slip__panels">
        <section class="slip__panel" id="${id.vn}" aria-labelledby="${id.vn}-title" data-tab-panel>
          <h3 class="slip__title" id="${id.vn}-title">${c.tx(D.inVietnam)}</h3>
          <div class="slip__body">${slipRows(c, involve.bankFields)}${qrSlot(c, D)}</div>
        </section>
        <section class="slip__panel" id="${id.abroad}" aria-labelledby="${id.abroad}-title" data-tab-panel>
          <h3 class="slip__title" id="${id.abroad}-title">${c.tx(D.fromAbroad)}</h3>
          <div class="slip__body">${slipRows(c, involve.abroadFields)}</div>
          <p class="slip__alt">${c.tx(D.paypal)} <button class="btn btn--secondary btn--small" type="button" disabled aria-describedby="${PAY_NOTE}" data-donation-placeholder>PayPal</button></p>
        </section>
      </div>
      <p class="slip__note" id="${PAY_NOTE}"${c.d(D.pendingNote)}>${icon("info")}<span>${c.tx(D.pendingNote)}</span></p>
      <div class="slip__center">
        <h3 class="slip__center-title">${c.tx(D.toCenterTitle)}</h3>
        <p${c.d(D.toCenter)}>${c.tx(D.toCenter)}</p>
      </div>
    </section>
    <section class="tell" id="${id.tell}" aria-labelledby="tell-title">
      <h2 class="tell__title" id="tell-title">${viKeep(c.tx(D.tellTitle))}</h2>
      <p class="tell__lead"${c.d(D.tellLead)}>${c.tx(D.tellLead)}</p>
      <form class="form" data-form novalidate aria-labelledby="tell-title">
        <div class="form__summary" data-summary tabindex="-1" role="alert" hidden></div>
        ${field(c, { id: "d-name", label: c.t("f_name"), required: true, autocomplete: "name", error: c.t("e_name") })}
        ${field(c, { id: "d-email", label: c.t("f_email"), type: "email", required: true, autocomplete: "email", error: c.t("e_email") })}
        ${field(c, { id: "d-amount", label: c.tx(D.amount), required: true, error: c.tx(D.amountError) })}
        ${field(c, { id: "d-center", label: c.tx(D.giftFor), options: { placeholder: c.tx(D.giftForAny), items: work.centers.map((x) => [x.id, c.tx(x.name)]) }, error: "" })}
        ${consentField(c, "d-consent")}
        <button class="btn btn--primary form__send" type="submit">${c.t("f_send")}</button>
        ${formSent(c, c.tx(D.thanks), { draft: D.thanks.draft })}
      </form>
    </section>
  </div>
  <nav class="trust container" aria-labelledby="trust-title">
    <h2 class="trust__title" id="trust-title">${c.t("trust")}</h2>
    <ul class="trust__list">
      <li>${textLink(c.url("reports"), c.t("annualReports"))}</li>
      <li>${textLink(c.url("safeguarding"), c.t("safeguarding"))}</li>
      <li>${textLink(c.url("privacy"), c.t("privacy"))}</li>
    </ul>
  </nav>
</div>
${duskBand(c, {
  id: "donate-questions",
  title: c.tx(D.questionsTitle),
  draft: D.questionsTitle.draft,
  body: html`${reachLines(c, site.email.sponsorship)}${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk", cls: "dusk__btn" })}`,
})}`;
  return { title: c.t("donate"), description: c.tx(D.intro), body, bodyClass: "page-donate" };
}

// ---------- Contact (P6) ----------

// A center's way in: its door in unit paint and its name. Little Rose has no address and no line of its
// own; the office takes everything, and the privacy note says why.
const unitReach = (c, x) => {
  const { work, involve } = c.content;
  const P = involve.contactPage;
  const lr = x.id === "little-rose";
  const page = x.built && !lr;
  return html`<li class="unit" data-unit="${x.id}" data-door="${lr ? "closed" : "open"}" style="--unit:var(--color-unit-${x.accent})">
    <span class="unit__door" aria-hidden="true"><span class="unit__leaf"></span></span>
    <div class="unit__text">
      <h3 class="unit__name">${page ? html`<a href="${c.tx(x.path)}">${viKeep(c.tx(x.name))}</a>` : viKeep(c.tx(x.name))}</h3>
      ${lr
        ? html`<p class="unit__line"${c.d(P.littleRose)}>${c.tx(P.littleRose)}</p><p class="unit__note"${c.d(work.littleRose.privacyNote)}>${c.tx(work.littleRose.privacyNote)}</p>`
        : html`<p class="unit__line"${c.d(P.centerPending)}>${c.tx(P.centerPending)}</p>`}
    </div>
  </li>`;
};

export function contact(c) {
  const { site, work, involve } = c.content;
  const P = involve.contactPage;
  const topics = ["donate", "partner", "volunteer", "media", "other"].map((k) => [k, c.t("topic_" + k)]);
  // The opening, "Qua cổng vào văn phòng" (M6; openings.css): on the stage (Tier 1 and 2, motion on) the
  // first screen is the house seen through its courtyard gate (the header's still, with the courtyard, the
  // gate and the tamarind drawn over it: partials/contact-gate.mjs). As the visitor scrolls, the gate's
  // leaves swing open, the camera comes through the gate toward the office and three letters drop into the
  // letterbox, then the page rises into the stage. At rest (no JS, reduced motion, pause, ?tier=0) it is the
  // lit header with the gate half open. The H1 never moves; it scrolls away. The stage line stays on screen
  // (pinned at the foot of the frame) after the words above it have gone.
  const body = html`
${stage("contact", html`${litHeader(c, {
  still: "header-contact",
  trail: [[c.t("contact")]],
  title: viKeep(c.t("contact")),
  intro: c.tx(P.intro),
  introDraft: P.intro.draft,
  size: "display",
  // The two quickest ways in, on the first screen (the office card repeats them with the address).
  extra: html`<p class="lit-head__actions" data-draft>
    <a class="btn btn--secondary" href="tel:${site.phone.tel}">${icon("phone")}<span>${c.tx(P.callUs)}</span></a>
    <a class="btn btn--secondary" href="mailto:${site.email.general}">${icon("mail")}<span>${c.tx(P.writeUs)}</span></a>
  </p>
  ${skipOpening("#form-title", c.tx(P.skipToForm))}`,
  layers: contactGate(),
})}<div class="container s3d-linebox"><p class="s3d-line"${c.d(P.stageLine)}>${c.tx(P.stageLine)}</p></div>`, litStageVars("header-contact"), { pin: ".lit-head__media" })}
<div class="day day--contact">
  <div class="reach container">
    <section class="reach__write" aria-labelledby="form-title">
      <h2 class="reach__title" id="form-title" data-reveal="rise"><span>${c.tx(P.formTitle)}</span></h2>
      <form class="form form--wide" data-form novalidate aria-labelledby="form-title">
        <div class="form__summary" data-summary tabindex="-1" role="alert" hidden></div>
        <div class="form__pair">
          ${field(c, { id: "name", label: c.t("f_name"), required: true, autocomplete: "name", error: c.t("e_name") })}
          ${field(c, { id: "phone", label: c.t("f_phone"), type: "tel", autocomplete: "tel", error: "" })}
        </div>
        ${field(c, { id: "email", label: c.t("f_email"), type: "email", required: true, autocomplete: "email", help: c.t("f_emailHelp"), error: c.t("e_email") })}
        ${choiceField(c, { id: "topic", label: c.t("f_topic"), items: topics, required: true, error: c.t("e_topic") })}
        ${field(c, { id: "message", label: c.t("f_message"), required: true, rows: 6, error: c.t("e_message") })}
        ${consentField(c)}
        <button class="btn btn--primary form__send" type="submit">${c.t("f_send")}</button>
        ${formSent(c, c.t("f_sent"))}
      </form>
    </section>
    <section class="office" aria-labelledby="office-title">
      <h2 class="office__title" id="office-title">${c.tx(P.officeTitle)}</h2>
      <p class="office__address" data-draft>${quatrefoil("office__sun")}<span>${c.tx(site.address)}</span></p>
      <dl class="office__lines" data-draft>
        <div><dt>${c.t("phone")}</dt><dd><a href="tel:${site.phone.tel}">${site.phone.display}</a></dd></div>
        <div><dt>${c.t("email")}</dt><dd><a href="mailto:${site.email.general}">${site.email.general}</a></dd></div>
      </dl>
      <p class="office__map"><a class="text-link" href="${site.mapUrl}"><span>${c.t("openMap")}</span>${icon("external")}</a></p>
      ${c.prod ? "" : html`<p class="office__chat"${c.d(P.chat)}>${c.tx(P.chat)} <span class="office__app">Zalo <span class="chip-pending">${c.tx(P.chatPending)}</span></span> <a href="${site.facebook}">Facebook Messenger</a></p>`}
    </section>
  </div>
  <section class="units container" aria-labelledby="units-title">
    <h2 class="units__title" id="units-title" data-reveal="rise"><span>${viKeep(c.t("centers"))}</span></h2>
    <ul class="units__list" data-reveal="stagger">${work.centers.map((x) => unitReach(c, x))}</ul>
  </section>
</div>`;
  return { title: c.t("contact"), description: c.tx(P.intro), body, lit: true, bodyClass: "page-contact" };
}

// ---------- 404 (P7) ----------

// "Một viên gạch bị thiếu": the screen with one block missing and the light pouring through the gap.
// Static: nothing on this page moves. The still is the first thing on the page, so it loads eagerly.
// Every page the site links to is built (PLAN-M4), so this page only ever meets a mistyped or old URL:
// the staging "not part of the prototype yet" note is gone (ui.notInPrototype stays in ui.json, unused).
export function notFound(c) {
  const links = [["home", "home"], ["ourWork", "ourWork"], ["contact", "contact"]];
  const art = stillReady("not-found") ? stillPicture("not-found", { cls: "gap__img", sizes: "(min-width: 1024px) 46vw, 100vw", eager: true }) : litWall();
  const body = html`
<div class="day day--gap">
  <section class="gap container" aria-labelledby="nf-title">
    <div class="gap__still" aria-hidden="true">${art}</div>
    <div class="gap__text">
      <h1 class="gap__title" id="nf-title">${viKeep(c.t("nf_title"))}</h1>
      <p class="gap__lead">${c.t("nf_text")}</p>
      <form class="search" action="${c.url("home")}" method="get" role="search">
        <label for="s">${c.t("nf_search")}</label>
        <div class="search__row"><input type="search" id="s" name="s" autocomplete="off"><button class="btn btn--secondary" type="submit">${icon("search")}<span>${c.t("nf_searchBtn")}</span></button></div>
      </form>
      <nav class="gap__nav" aria-labelledby="nf-links">
        <p class="gap__nav-title" id="nf-links">${c.t("nf_links")}</p>
        <ul class="gap__links">${links.map(([route, key]) => html`<li><a href="${c.url(route)}"><span>${c.t(key)}</span>${icon("arrow")}</a></li>`)}</ul>
      </nav>
    </div>
  </section>
</div>`;
  return { title: c.t("nf_title"), description: c.t("nf_text"), body, bodyClass: "page-gap" };
}
