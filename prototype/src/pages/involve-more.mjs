import { html } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { textLink, button, icon, quatrefoil, field, choiceField, consentField, formSent, duskBand, reachLines } from "../partials/components.mjs";
import { sunHead } from "../partials/lit-header.mjs";
import { screenArt } from "../partials/blocks.mjs";

// The Get Involved subpages (PLAN-M4 4.7-4.9): late afternoon, the same sun band as Donate. Forms reuse
// the shared fields (every label, help line and error in the page language) and ui/forms.js; on the
// prototype nothing is sent (f_prototypeSent). No invented checks, partners or needs: what HCWA has not
// said yet is a [token]. The bakery is ordered through the office only, never shown with a place.

export const BAKERY_ID = { vi: "tiem-banh", en: "bakery" };
const crumb = (c) => [c.t("getInvolved"), c.url("getInvolved")];

// The two quickest ways in, in the first screen (the Contact page's call and write buttons).
const reachButtons = (c, email = c.content.site.email.general) => {
  const { site, involve } = c.content;
  return html`<p class="sun-head__actions" data-draft>
    <a class="btn btn--secondary" href="tel:${site.phone.tel}">${icon("phone")}<span>${c.tx(involve.contactPage.callUs)}</span></a>
    <a class="btn btn--secondary" href="mailto:${email}">${icon("mail")}<span>${c.tx(involve.contactPage.writeUs)}</span></a>
  </p>`;
};

// ---------- Volunteer ----------

export function volunteer(c) {
  const V = c.content.involvePages.volunteer;
  const interests = [...V.areas.map((a) => [a.id, c.tx(a.title)]), ["other", c.tx(V.otherArea)]];
  const body = html`
${sunHead(c, {
  trail: [crumb(c), [c.t("volunteer")]],
  title: viKeep(c.t("volunteer")),
  intro: c.tx(V.intro),
  introDraft: V.intro.draft,
  side: button({ href: "#vol-form", label: c.tx(V.formTitle), cls: "sun-head__go" }),
})}
<div class="day day--late">
  <section class="needed container" aria-labelledby="look-title">
    <h2 class="needed__title" id="look-title" data-reveal="rise"><span>${c.tx(V.lookTitle)}</span></h2>
    <ol class="needed__list" data-reveal="stagger">${V.areas.map((a, i) => html`<li class="needed__item">
      <span class="needed__n num" aria-hidden="true">${String(i + 1).padStart(2, "0")}</span>
      <h3 class="needed__name">${viKeep(c.tx(a.title))}</h3>
      <p class="needed__text"${c.d(a.text)}>${c.tx(a.text)}</p>
    </li>`)}</ol>
  </section>

  <div class="duo container">
    <section class="duo__col" aria-labelledby="expect-title">
      <h2 class="duo__title" id="expect-title">${c.tx(V.expectTitle)}</h2>
      <ul class="give__needs duo__list">${V.expect.map((x) => html`<li${c.d(x)}>${quatrefoil("give__mark")}<span>${c.tx(x)}</span></li>`)}</ul>
    </section>
    <section class="duo__col duo__col--safe" aria-labelledby="safe-title">
      <h2 class="duo__title" id="safe-title">${c.tx(V.safetyTitle)}</h2>
      <p class="duo__text"${c.d(V.safety)}>${c.tx(V.safety)}</p>
      <p>${textLink(c.url("safeguarding"), c.t("safeguarding"))}</p>
    </section>
  </div>

  <section class="signup container" id="vol-form" aria-labelledby="vol-form-title">
    <div class="signup__head">
      <h2 class="signup__title" id="vol-form-title">${viKeep(c.tx(V.formTitle))}</h2>
      <p class="signup__lead"${c.d(V.formLead)}>${c.tx(V.formLead)}</p>
    </div>
    <form class="form form--wide" data-form novalidate aria-labelledby="vol-form-title">
      <div class="form__summary" data-summary tabindex="-1" role="alert" hidden></div>
      <div class="form__pair">
        ${field(c, { id: "v-name", label: c.t("f_name"), required: true, autocomplete: "name", error: c.t("e_name") })}
        ${field(c, { id: "v-phone", label: c.t("f_phone"), type: "tel", autocomplete: "tel", error: "" })}
      </div>
      ${field(c, { id: "v-email", label: c.t("f_email"), type: "email", required: true, autocomplete: "email", help: c.t("f_emailHelp"), error: c.t("e_email") })}
      ${choiceField(c, { id: "v-interest", label: c.t("f_interest"), items: interests, required: true, error: c.t("e_interest") })}
      ${field(c, { id: "v-when", label: c.t("f_availability"), options: { placeholder: c.t("f_availabilityChoose"), items: V.availability.map(([v, l]) => [v, c.tx(l)]) }, error: "" })}
      ${field(c, { id: "v-message", label: c.t("f_message"), rows: 5, error: "" })}
      ${consentField(c, "v-consent")}
      <button class="btn btn--primary form__send" type="submit">${c.t("f_send")}</button>
      ${formSent(c, c.tx(V.thanks), { draft: V.thanks.draft })}
    </form>
  </section>
</div>
${duskBand(c, {
  id: "vol-q",
  title: c.tx(V.ctaTitle),
  body: html`${reachLines(c)}${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk", cls: "dusk__btn" })}`,
})}`;
  return { title: c.t("volunteer"), description: c.tx(V.intro), body, bodyClass: "page-sub page-volunteer" };
}

// ---------- Partner with us ----------

export function partner(c) {
  const { site } = c.content;
  const P = c.content.involvePages.partner;
  const email = site.email.sponsorship;
  const ways = P.ways.map((w) => [w.id, c.tx(w.title)]);
  const body = html`
${sunHead(c, {
  trail: [crumb(c), [c.t("partnerWithUs")]],
  title: viKeep(c.t("partnerWithUs")),
  intro: c.tx(P.intro),
  introDraft: P.intro.draft,
  side: html`${button({ href: "#partner-form", label: c.tx(P.contactTitle), cls: "sun-head__go" })}<p class="sun-head__mail" data-draft><a href="mailto:${email}">${email}</a></p>`,
  size: "long",
})}
<div class="day day--late">
  <section class="needed container" aria-labelledby="ways-title">
    <h2 class="needed__title" id="ways-title" data-reveal="rise"><span>${c.tx(P.waysTitle)}</span></h2>
    <ol class="needed__list" data-reveal="stagger">${P.ways.map((w, i) => html`<li class="needed__item">
      <span class="needed__n num" aria-hidden="true">${String(i + 1).padStart(2, "0")}</span>
      <h3 class="needed__name">${w.route ? html`<a href="${c.url(w.route)}">${viKeep(c.tx(w.title))}</a>` : viKeep(c.tx(w.title))}</h3>
      <p class="needed__text"${c.d(w.text)}>${c.tx(w.text)}</p>
    </li>`)}</ol>
  </section>

  ${c.prod
    ? ""
    : html`<section class="pwait container" aria-labelledby="examples-title" data-partners-pending>
    <h2 class="pwait__title" id="examples-title">${c.tx(P.examplesTitle)}</h2>
    <p class="partners__pending" data-draft>${c.t("partnersPending")}</p>
    <p>${textLink(c.url("partners"), c.t("partners"))}</p>
  </section>`}

  <section class="signup container" id="partner-form" aria-labelledby="partner-form-title">
    <div class="signup__head">
      <h2 class="signup__title" id="partner-form-title">${viKeep(c.tx(P.contactTitle))}</h2>
      <p class="signup__lead"${c.d(P.contactLead)}>${c.tx(P.contactLead)}</p>
      <p class="signup__mail" data-draft>${icon("mail")}<a href="mailto:${email}">${email}</a></p>
    </div>
    <form class="form form--wide" data-form novalidate aria-labelledby="partner-form-title">
      <div class="form__summary" data-summary tabindex="-1" role="alert" hidden></div>
      <div class="form__pair">
        ${field(c, { id: "p-name", label: c.t("f_name"), required: true, autocomplete: "name", error: c.t("e_name") })}
        ${field(c, { id: "p-org", label: c.t("f_organization"), required: true, autocomplete: "organization", error: c.t("e_organization") })}
      </div>
      <div class="form__pair">
        ${field(c, { id: "p-email", label: c.t("f_email"), type: "email", required: true, autocomplete: "email", error: c.t("e_email") })}
        ${field(c, { id: "p-phone", label: c.t("f_phone"), type: "tel", autocomplete: "tel", error: "" })}
      </div>
      ${choiceField(c, { id: "p-way", label: c.t("f_partnerWay"), items: ways, required: true, error: c.t("e_partnerWay") })}
      ${field(c, { id: "p-message", label: c.t("f_message"), required: true, rows: 5, error: c.t("e_message") })}
      ${consentField(c, "p-consent")}
      <button class="btn btn--primary form__send" type="submit">${c.t("f_send")}</button>
      ${formSent(c, c.tx(P.thanks), { draft: P.thanks.draft })}
    </form>
  </section>
</div>
${duskBand(c, {
  id: "partner-more",
  title: c.t("otherWays"),
  body: html`<div class="dusk__actions">${button({ href: c.url("donate"), label: c.t("donate") })}${button({ href: c.url("volunteer"), label: c.t("volunteer"), kind: "on-dusk" })}</div>`,
})}`;
  return { title: c.t("partnerWithUs"), description: c.tx(P.intro), body, bodyClass: "page-sub page-partner" };
}

// ---------- In-kind gifts and the Little Rose Bakery ----------

// The bakery: what it is, and that every order goes through the office. No photos, no residents' names,
// nothing that places the shelter or the bakery. Its art is the house's Hoa thị screen with light behind
// it, never people and never a building.
export function inKind(c) {
  const { site } = c.content;
  const K = c.content.involvePages.inKind;
  const bid = BAKERY_ID[c.lang];
  const body = html`
${sunHead(c, {
  trail: [crumb(c), [c.t("inKind")]],
  title: viKeep(c.t("inKind")),
  intro: c.tx(K.intro),
  introDraft: K.intro.draft,
  side: html`${reachButtons(c)}<p>${textLink("#" + bid, c.t("bakery"))}</p>`,
})}
<div class="day day--late">
  <section class="give container" aria-labelledby="needs-title">
    <h2 class="give__title give__title--sub" id="needs-title" data-reveal="rise"><span>${c.tx(K.needsTitle)}</span></h2>
    <ul class="give__needs" data-reveal="stagger">${K.needs.map((n) => html`<li${c.d(n)}>${quatrefoil("give__mark")}<span>${c.tx(n)}</span></li>`)}</ul>
  </section>

  <div class="duo container">
    <section class="duo__col" aria-labelledby="first-title">
      <h2 class="duo__title" id="first-title">${c.t("contactFirst")}</h2>
      <p class="duo__text"${c.d(K.first)}>${c.tx(K.first)}</p>
      <p>${textLink(c.url("contact"), c.t("useContactForm"))}</p>
    </section>
    <section class="duo__col" aria-labelledby="drop-title">
      <h2 class="duo__title" id="drop-title">${c.t("dropOff")}</h2>
      <p class="duo__address" data-draft>${quatrefoil("office__sun")}<span>${c.tx(site.address)}</span></p>
      <p class="duo__text"${c.d(K.dropNote)}>${c.tx(K.dropNote)}</p>
      <p><a class="text-link" href="${site.mapUrl}"><span>${c.t("openMap")}</span>${icon("external")}</a></p>
    </section>
  </div>

  <section class="bakery container" id="${bid}" aria-labelledby="${bid}-title">
    <div class="bakery__art" aria-hidden="true">${screenArt("hoaThi", { cols: 3, rows: 2, cls: "block-art bakery__screen" })}<span class="bakery__light"></span></div>
    <div class="bakery__text">
      <h2 class="bakery__title" id="${bid}-title">${viKeep(c.t("bakery"))}</h2>
      <p class="bakery__lead"${c.d(K.bakery)}>${c.tx(K.bakery)}</p>
      <p class="bakery__menu"${c.d(K.bakeryMenu)}>${c.tx(K.bakeryMenu)}</p>
      <h3 class="bakery__how">${c.t("orderThroughOffice")}</h3>
      <p class="bakery__order"${c.d(K.bakeryOrder)}>${c.tx(K.bakeryOrder)}</p>
      ${reachLinesLight(c)}
      <p class="bakery__links">${textLink(c.url("contact"), c.t("useContactForm"))}${textLink(c.url("littleRose"), c.t("aboutShelter"))}</p>
    </div>
  </section>
</div>
${duskBand(c, {
  id: "inkind-more",
  title: c.t("otherWays"),
  body: html`<div class="dusk__actions">${button({ href: c.url("donate"), label: c.t("donate") })}${button({ href: c.url("volunteer"), label: c.t("volunteer"), kind: "on-dusk" })}</div>`,
})}`;
  return { title: c.t("inKind"), description: c.tx(K.intro), body, bodyClass: "page-sub page-inkind" };
}

// The office's lines on the day's light (not the night): the bakery's only way in.
const reachLinesLight = (c) => {
  const { site } = c.content;
  return html`<dl class="office__lines bakery__lines" data-draft>
    <div><dt>${c.t("phone")}</dt><dd><a href="tel:${site.phone.tel}">${site.phone.display}</a></dd></div>
    <div><dt>${c.t("email")}</dt><dd><a href="mailto:${site.email.general}">${site.email.general}</a></dd></div>
  </dl>`;
};
