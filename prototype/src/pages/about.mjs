import { html } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { textLink, button, icon, quatrefoil, duskBand, reachLines } from "../partials/components.mjs";
import { litHeader, sunHead, headerReady } from "../partials/lit-header.mjs";
import { gachStrip, gachSpriteOnce, blockIcon } from "../partials/blocks.mjs";

// About pages (PLAN-M4 4.1-4.5): the morning of the site's day. Who we are opens on a lit header (the
// house's front screen in early light, header-about; the morning sun band until the walls stream renders it); the
// four smaller pages open on the image-free sun band. No founding year and no numbers anywhere: every
// year is a token on staging and production leaves it out. No names, photos, initials or silhouettes of
// staff, and no partner name HCWA has not confirmed.

const aboutCrumb = (c) => [c.t("about"), c.url("about")];

// The four focus areas as a strip of the floor's tiles, each one a way into Our Work filtered to it.
const areas = (c) => {
  const { work } = c.content;
  return html`<section class="areas container" aria-labelledby="areas-title">
  ${gachSpriteOnce(c)}
  <h2 class="areas__title" id="areas-title"${c.d(work.focusTitle)} data-reveal="rise"><span>${c.tx(work.focusTitle)}</span></h2>
  <ul class="areas__list" data-reveal="stagger">${work.focusAreas.map((a) => html`<li class="area">
    ${gachStrip([a.id], "gach-strip area__tile")}
    <h3 class="area__name"><a href="${c.url("ourWork")}?area=${a.id}#f-${a.id}">${viKeep(c.tx(a.name))}</a></h3>
    <p class="area__text"${c.d(a.text)}>${c.tx(a.text)}</p>
  </li>`)}</ul>
</section>`;
};

// ---------- Who we are (about) ----------

export function about(c) {
  const { site } = c.content;
  const W = c.content.about.whoWeAre;
  const more = [["ourStory", W.storyLead], ["team"], ["partners"], ["policies"]];
  // The lit header once the walls stream ships header-about; the morning sun band until then.
  const lit = headerReady("header-about");
  const body = html`
${lit
  ? litHeader(c, { still: "header-about", trail: [[c.t("about")]], title: viKeep(c.t("whoWeAre")), intro: c.tx(site.description), introDraft: site.description.draft, size: "display" })
  : sunHead(c, { trail: [[c.t("about")]], title: viKeep(c.t("whoWeAre")), intro: c.tx(site.description), introDraft: site.description.draft, time: "morning" })}
<div class="day day--about">
  <section class="aim container" aria-labelledby="aim-title">
    <h2 class="aim__label" id="aim-title">${blockIcon("hoaThi", "aim__mark")}${c.tx(W.aimTitle)}</h2>
    <p class="aim__text"${c.d(W.aim)} data-reveal>${c.tx(W.aim)}</p>
  </section>

  <div class="creed container">
    <section class="creed__vision" aria-labelledby="vision-title">
      <h2 class="creed__title" id="vision-title" data-reveal="rise"><span>${c.tx(W.visionTitle)}</span></h2>
      <p class="creed__statement"${c.d(W.vision)} data-reveal>${c.tx(W.vision)}</p>
    </section>
    <section class="creed__mission" aria-labelledby="mission-title">
      <h2 class="creed__title" id="mission-title" data-reveal="rise"><span>${c.tx(W.missionTitle)}</span></h2>
      <p class="creed__text"${c.d(W.mission)} data-reveal>${c.tx(W.mission)}</p>
    </section>
  </div>

  <section class="values container" aria-labelledby="values-title">
    <h2 class="values__title" id="values-title">${c.tx(W.valuesTitle)}</h2>
    <ul class="values__list" data-reveal="stagger">${W.values.map((v) => html`<li${c.d(v)}>${quatrefoil("values__mark")}<span>${viKeep(c.tx(v))}</span></li>`)}</ul>
  </section>

  ${areas(c)}

  <section class="legal container" aria-labelledby="legal-title">
    <h2 class="legal__title" id="legal-title">${c.tx(W.legalTitle)}</h2>
    <p class="legal__text"${c.d(W.legal)}>${c.tx(W.legal)}</p>
    <p class="legal__reg"${c.d(site.registration)}>${c.tx(site.registration)}</p>
  </section>

  <nav class="onward container" aria-labelledby="onward-title">
    <h2 class="onward__title" id="onward-title" data-reveal="rise"><span>${c.t("inAbout")}</span></h2>
    <ul class="onward__list" data-reveal="stagger">${more.map(([key, lead]) => html`<li class="onward__item">
      <a class="onward__link" href="${c.url(key)}"><span>${viKeep(c.t(key))}</span>${icon("arrow")}</a>
      ${lead ? html`<p class="onward__lead"${c.d(lead)}>${c.tx(lead)}</p>` : ""}
    </li>`)}</ul>
  </nav>
</div>
${duskBand(c, {
  id: "about-cta",
  title: c.tx(W.ctaTitle),
  draft: W.ctaTitle.draft,
  body: html`<div class="dusk__actions">${button({ href: c.url("donate"), label: c.t("donate") })}${button({ href: c.url("volunteer"), label: c.t("volunteer"), kind: "on-dusk" })}</div>`,
})}`;
  return { title: c.t("whoWeAre"), description: c.tx(site.description), body, lit, bodyClass: "page-about" };
}

// ---------- Our story (ourStory) ----------

// The timeline as a walk along the morning wall: each step a patch of Hoa thị sun that slides a little
// as the step passes (pages-m4.css, CSS view(), follows the scroll, never leads it). The year is a token
// on staging and is left out in production until HCWA verifies it. Little Rose's step says what the
// shelter is, never where.
export function ourStory(c) {
  const S = c.content.about.story;
  const step = (s) => {
    // A Vietnamese name inside an English title is read in Vietnamese by screen readers (lang="vi").
    const text = viKeep(c.tx(s.title));
    const at = c.lang === "en" && s.viName ? text.indexOf(s.viName) : -1;
    const title = at < 0 ? text : html`${text.slice(0, at)}<span lang="vi">${s.viName}</span>${text.slice(at + s.viName.length)}`;
    return html`<li class="step" data-step="${s.id}">
    <p class="step__when">${s.id === "today" || c.prod ? "" : html`<span data-draft>${c.t("yearToken")}</span>`}</p>
    <span class="step__sun" aria-hidden="true"></span>
    <div class="step__body">
      <h2 class="step__title">${s.route ? html`<a href="${c.url(s.route)}">${title}</a>` : title}</h2>
      <p class="step__text"${c.d(s.text)}>${c.tx(s.text)}</p>
    </div>
  </li>`;
  };
  const body = html`
${sunHead(c, { trail: [aboutCrumb(c), [c.t("ourStory")]], title: viKeep(c.t("ourStory")), intro: c.tx(S.intro), introDraft: S.intro.draft, time: "morning" })}
<div class="day day--story">
  ${c.prod ? "" : html`<p class="steps__order container" data-draft>${c.tx(S.orderNote)}</p>`}
  <ol class="steps container">${S.steps.filter((s) => s.id !== "today").map(step)}${S.steps.filter((s) => s.id === "today").map(step)}</ol>
</div>
${duskBand(c, {
  id: "story-more",
  title: c.t("inAbout"),
  body: html`<div class="dusk__actions">${button({ href: c.url("about"), label: c.t("whoWeAre") })}${button({ href: c.url("ourWork"), label: c.t("exploreWork"), kind: "on-dusk" })}</div>`,
})}`;
  return { title: c.t("ourStory"), description: c.tx(S.intro), body, bodyClass: "page-story" };
}

// ---------- Team (team) ----------

// Only people who agree to be listed (06-PAGES). Nobody has yet, so there are no names, photos, initials
// or silhouettes: staging shows three empty places carrying [Name] · [Role] tokens (data-team-pending, a
// production blocker in render.mjs); production shows the intro and the ways in only.
export function team(c) {
  const T = c.content.about.team;
  const slots = c.prod
    ? ""
    : html`<section class="roster container" aria-labelledby="roster-title">
    <h2 class="sr-only" id="roster-title">${c.t("team")}</h2>
    <ul class="roster__list">${Array.from({ length: T.slots }, () => html`<li class="roster__slot" data-team-pending data-draft><span>${c.t("teamSlot")}</span></li>`)}</ul>
    <p class="roster__note"${c.d(T.slotsNote)}>${c.tx(T.slotsNote)}</p>
  </section>`;
  const body = html`
${sunHead(c, { trail: [aboutCrumb(c), [c.t("team")]], title: viKeep(c.t("team")), intro: c.tx(T.intro), introDraft: T.intro.draft, time: "morning" })}
<div class="day day--team">
  ${slots}
</div>
${duskBand(c, {
  id: "team-join",
  title: c.tx(T.joinTitle),
  body: html`<p class="dusk__note"${c.d(T.joinText)}>${c.tx(T.joinText)}</p><div class="dusk__actions">${button({ href: c.url("volunteer"), label: c.t("volunteer") })}${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk" })}</div>`,
})}`;
  return { title: c.t("team"), description: c.tx(T.intro), body, bodyClass: "page-team" };
}

// ---------- Partners & sponsors (partners) ----------

// Grouped by type (04 Partner), a group only once HCWA has approved an entry in it. None has, so staging
// shows the pending strip (data-partners-pending, a production blocker) and production shows no group.
// The one name in content, the funder of an active program, stands as text on staging only, linked to
// its program, until HCWA confirms a partners page may list it.
export function partners(c) {
  const { work } = c.content;
  const P = c.content.about.partners;
  const groups = P.groups.map((g) => ({ ...g, entries: g.entries.filter((e) => e.approved === true) })).filter((g) => g.entries.length);
  const funded = work.programs.filter((p) => p.status === "active" && p.partners.length);
  const body = html`
${sunHead(c, { trail: [aboutCrumb(c), [c.t("partners")]], title: viKeep(c.t("partners")), intro: c.tx(P.intro), introDraft: P.intro.draft, time: "morning", size: "long" })}
<div class="day day--partners">
  ${groups.map((g) => html`<section class="pgroup container" aria-labelledby="pg-${g.id}">
    <h2 class="pgroup__title" id="pg-${g.id}">${c.tx(g.name)}</h2>
    <ul class="partner-wall">${g.entries.map((e) => html`<li><img src="${e.src}" alt="${e.name}" loading="lazy" decoding="async"></li>`)}</ul>
  </section>`)}
  ${groups.length || c.prod ? "" : html`<div class="pwait container" data-partners-pending><p class="partners__pending" data-draft>${c.t("partnersPending")}</p></div>`}
  ${c.prod || !funded.length
    ? ""
    : html`<section class="funders container" aria-labelledby="funders-title">
    <h2 class="funders__title" id="funders-title">${c.t("fundersNow")}</h2>
    <ul class="funders__list">${funded.map((p) => html`<li data-draft><span class="funders__name">${p.partners.join(", ")}</span> ${textLink(c.tx(p.path), c.tx(p.name))}</li>`)}</ul>
    <p class="funders__note"${c.d(P.fundersNote)}>${c.tx(P.fundersNote)}</p>
  </section>`}
</div>
${duskBand(c, {
  id: "partners-cta",
  title: c.tx(P.ctaTitle),
  draft: P.ctaTitle.draft,
  body: html`<div class="dusk__actions">${button({ href: c.url("partner"), label: c.t("becomePartner") })}${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk" })}</div>`,
})}`;
  return { title: c.t("partners"), description: c.tx(P.intro), body, bodyClass: "page-partners" };
}

// ---------- Policies (policies, with the safeguarding and privacy anchors) ----------

// Three sections, each with its id in the page's language (about.json policies.sections[].id), so the
// footer's links land on every page, production included. Each has a short summary and the outline of the
// full text HCWA will supply; no commitment is invented. The PDF row is the empty state until HCWA
// publishes one.
export const POLICY_TITLE = { safeguarding: "safeguarding", privacy: "privacy" };
export function policies(c) {
  const { site } = c.content;
  const P = c.content.about.policies;
  const title = (s) => (POLICY_TITLE[s.key] ? c.t(POLICY_TITLE[s.key]) : c.tx(s.title));
  const body = html`
${sunHead(c, { trail: [aboutCrumb(c), [c.t("policies")]], title: viKeep(c.t("policies")), intro: c.tx(P.intro), introDraft: P.intro.draft, time: "calm" })}
<div class="day day--policies">
  <nav class="toc container" aria-labelledby="toc-title">
    <p class="toc__title" id="toc-title">${c.t("onThisPage")}</p>
    <ol class="toc__list">${P.sections.map((s) => html`<li><a href="#${s.id[c.lang]}">${title(s)}</a></li>`)}</ol>
  </nav>
  ${P.sections.map((s) => html`<section class="policy container" id="${s.id[c.lang]}" aria-labelledby="${s.id[c.lang]}-title">
    <h2 class="policy__title" id="${s.id[c.lang]}-title">${viKeep(title(s))}</h2>
    <div class="policy__body">
      <div class="policy__summary">${s.summary.map((p) => html`<p${c.d(p)}>${c.tx(p)}</p>`)}</div>
      <p class="policy__lead"${c.d(s.outlineLead)}>${c.tx(s.outlineLead)}</p>
      <ol class="policy__outline">${s.outline.map((o) => html`<li${c.d(o)}>${c.tx(o)}</li>`)}</ol>
      <p class="policy__doc"${c.d(c.content.ui.policyDocPending)}>${icon("file", "icon policy__icon")}<span>${c.t("policyDocPending")}</span></p>
    </div>
  </section>`)}
</div>
${duskBand(c, {
  id: "policies-q",
  title: c.tx(site.contactBand.title),
  body: html`${reachLines(c)}${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk", cls: "dusk__btn" })}`,
})}`;
  return { title: c.t("policies"), description: c.tx(P.intro), body, bodyClass: "page-policies" };
}
