import { html, raw } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { visibleFigures } from "../lib/content.mjs";
import { programRow, textLink, button, icon, statusPill, datedPost, pendingChip, duskBand, quatrefoil } from "../partials/components.mjs";
import { litHeader } from "../partials/lit-header.mjs";
import { blockIcon, gachStrip, gachSpriteOnce } from "../partials/blocks.mjs";
import { still, hasStill, stillAspect } from "../partials/still.mjs";
import { routes } from "../routes.mjs";

// Our Work (PLAN P1-P3). Two kinds of work, two shapes that cannot be mistaken for each other:
// centers are places (doors in one wall, unit paint, the emblem their transom throws), programs are
// time-bound light (lit slats with a status, years and funders as text). Every page is one stretch of
// the day (05-DESIGN D0.5): a single gradient behind the sections, no section fills, no rules, and no
// two sections in a row built on the same grid.

const A = { vi: { centers: "co-so", programs: "du-an" }, en: { centers: "centers", programs: "programs" } };
const STATUSES = ["active", "completed"];
const EMBLEM = { "green-bamboo": "dotTre", "cau-han": "vomCau" };
// The corridor wall with the three doors in one frame, once the world stream renders it; until then the
// three door stills stand side by side and feather into one wall.
const PANORAMA = "hub-doors-wide";

// Elements the no-JS filter can hide or show for an area (pages.css :target rules): which areas leave
// a group empty is known when the page is built, so the empty note needs no script either.
const emptyFor = (items, areas) => areas.filter((a) => !items.some((x) => x.areas.includes(a))).join(" ");

// ---------- Our Work hub (P1) ----------

// Area chips are plain links: ?area= for the server (WordPress) and #f-<area> for this static build, so
// the filter works without JS through :target. ui/filters.js takes over when it runs (both filters at
// once, the URL kept in step, a 240 ms FLIP, the count said in the live region). Each area chip carries one
// gạch tile of its area (final m3 recheck, critique r1/r2 could): the home floor's pigments, decoration only,
// from the page's one tile sprite, which the filters bring (they come before every program row).
const filters = (c) => {
  const { work } = c.content;
  return html`<nav class="filters container" aria-labelledby="filters-label">
  ${work.focusAreas.map((a) => html`<span class="filters__anchor" id="f-${a.id}"></span>`)}
  ${gachSpriteOnce(c)}
  <p class="filters__label" id="filters-label">${c.t("filterArea")}</p>
  <ul class="filters__list">
    <li><a class="chip chip--all" href="./" data-area="">${c.t("all")}</a></li>
    ${work.focusAreas.map((a) => html`<li><a class="chip" href="?area=${a.id}#f-${a.id}" data-area="${a.id}">${gachStrip([a.id], "gach-strip chip__tile")}${c.tx(a.name)}</a></li>`)}
  </ul>
  <p class="sr-only" data-filter-status aria-live="polite" data-centers-one="${c.t("countCenter")}" data-centers-many="${c.t("countCenters")}" data-programs-one="${c.t("countProgram")}" data-programs-many="${c.t("countPrograms")}" data-template="${c.t("filterShowing")}"></p>
</nav>`;
};

// A center as a door in one long wall. The name is the link and covers the door; an open doorway's light
// reaches the floor under its name as the wall scrolls in (pages.css, motion on only). Little Rose's
// door stays shut and throws no light, says "about the shelter", never "visit", never where.
// still: the door's own crop of the panorama (phones), or its door card when there is no panorama.
export const door = (c, x, { sizes, stillName = "door-" + x.id }) => {
  const lr = x.id === "little-rose";
  const label = lr ? c.t("aboutShelter") : c.t("visitCenter");
  return html`<li class="door" data-areas="${x.areas.join(" ")}" data-unit="${x.id}" data-door="${lr ? "closed" : "open"}" style="--unit:var(--color-unit-${x.accent})">
  <div class="door__still" aria-hidden="true">${still(stillName, "door__img", sizes)}<span class="door__spill"></span></div>
  <div class="door__plate">
    <h3 class="door__name"><a href="${c.tx(x.path)}">${viKeep(c.tx(x.name))}</a></h3>
    ${c.prod ? "" : html`<p class="door__since" data-draft>${c.t("since")} ${c.tx(x.sinceToken)}</p>`}
    <p class="door__serves"${c.d(x.serves)}>${c.tx(x.serves)}</p>
    ${!lr && x.location ? html`<p class="door__where"${c.d(x.location)}>${icon("pin", "icon door__pin")}${c.tx(x.location)}</p>` : ""}
    ${lr ? html`<p class="door__note"${c.d(c.content.work.littleRose.privacyNote)}>${c.tx(c.content.work.littleRose.privacyNote)}</p>` : ""}
    <p class="door__go" aria-hidden="true"><span>${label}</span>${icon("arrow")}</p>
    ${lr ? html`<p class="door__extra">${textLink(c.url("bakery"), c.t("bakery"))}</p>` : ""}
  </div>
</li>`;
};

// The door wall: one full-bleed picture with each door's plate on the floor under it, or the three
// door stills feathered into one wall. Phones: one door per plate, stacked, each its own crop of the
// same wall (hub-door-<unit>, 4:5).
export const doorWall = (c, centers, { cls = "" } = {}) => {
  const pano = hasStill(PANORAMA);
  const own = (x) => (pano && hasStill("hub-door-" + x.id) ? { stillName: "hub-door-" + x.id, sizes: "100vw" } : { sizes: "(min-width: 600px) 42vw, 100vw" });
  return html`<div class="doorwall${pano ? " doorwall--pano" : ""}${cls ? " " + cls : ""}"${pano ? raw(` style="--ratio:${stillAspect(PANORAMA)}"`) : ""}>
  ${pano ? html`<div class="doorwall__pano" aria-hidden="true">${still(PANORAMA, "doorwall__img", "100vw")}</div>` : ""}
  <ul class="doors" data-reveal="stagger">${centers.map((x) => door(c, x, own(x)))}</ul>
</div>`;
};

// Every group has one: CSS shows it for the areas known to leave the group empty, the script for any
// combination (an area and a status together can empty the programs too).
const emptyNote = (c, areas) =>
  html`<p class="filter-empty" data-filter-empty data-show-for="${areas}" hidden>${c.t("noResults")} <a href="./" data-area="">${c.t("showAll")}</a></p>`;

export function ourWork(c) {
  const { work } = c.content;
  const ids = work.focusAreas.map((a) => a.id);
  const programs = [...work.programs].sort((a, b) => STATUSES.indexOf(a.status) - STATUSES.indexOf(b.status));
  const body = html`
${litHeader(c, { still: "header-our-work", trail: [[c.t("ourWork")]], title: viKeep(c.t("ourWork")), intro: c.tx(work.hubIntro), introDraft: work.hubIntro.draft, size: "display" })}
<div class="day day--hub hub" data-hub>
  ${filters(c)}
  <section class="places" aria-labelledby="${A[c.lang].centers}" data-filter-group>
    <div class="container"><h2 class="places__title" id="${A[c.lang].centers}" data-reveal="rise"><span>${viKeep(c.t("centers"))}</span></h2></div>
    ${doorWall(c, work.centers)}
    <div class="container places__foot">
      <p class="places__lead"${c.d(work.hub.centersLead)} data-reveal>${c.tx(work.hub.centersLead)}</p>
      ${emptyNote(c, emptyFor(work.centers, ids))}
    </div>
  </section>
  <section class="times container" aria-labelledby="${A[c.lang].programs}" data-filter-group>
    <div class="times__head">
      ${STATUSES.map((s) => html`<span class="filters__anchor" id="f--${s}"></span>`)}
      <span class="lam lam--head" aria-hidden="true"></span>
      <h2 id="${A[c.lang].programs}" data-reveal="rise"><span>${viKeep(c.t("programs"))}</span></h2>
      <p class="times__lead"${c.d(work.hub.programsLead)} data-reveal>${c.tx(work.hub.programsLead)}</p>
      <nav class="seg" aria-label="${c.t("filterStatus")}" data-reveal>
        <a class="seg__opt" href="./#${A[c.lang].programs}" data-status="">${c.t("all")}</a>
        ${STATUSES.map((s) => html`<a class="seg__opt" href="?status=${s}#f--${s}" data-status="${s}"><span class="seg__light seg__light--${s}" aria-hidden="true"></span>${c.t("status_" + s)}</a>`)}
      </nav>
    </div>
    <div class="times__rows">
      <ul class="program-list program-list--hub" data-reveal="stagger">${programs.map((p) => programRow(c, p))}</ul>
      ${emptyNote(c, emptyFor(work.programs, ids))}
    </div>
  </section>
</div>
${duskBand(c, {
  id: "hub-cta",
  title: c.tx(work.hub.ctaTitle),
  draft: work.hub.ctaTitle.draft,
  body: html`<div class="dusk__actions">${button({ href: c.url("donate"), label: c.t("donate") })}${button({ href: c.url("volunteer"), label: c.t("volunteer"), kind: "on-dusk" })}</div>`,
})}`;
  return { title: c.t("ourWork"), description: c.tx(work.hubIntro), body, lit: true, bodyClass: "page-work" };
}

// ---------- shared inner-page parts ----------

// Figures for one center or program: pending chips on staging, nothing at all on production until HCWA
// verifies them (the guard in render.mjs fails on any data-pending). Never animated. One baseline row,
// no boxes: the numbers stand on the wall with their labels under them.
// nested: set under a story (h3 inside its section) rather than as a section of its own.
const figuresFor = (c, scope, title, id, { nested = false } = {}) => {
  const figs = visibleFigures(c.content.impact.filter((f) => f.scope === scope), c.prod);
  if (!figs.length) return "";
  const h = nested ? "h3" : "h2";
  return html`<section class="counts${nested ? " counts--nested" : " container"}" aria-labelledby="${id}">
  <${raw(h)} class="counts__title" id="${id}">${title}</${raw(h)}>
  <ul class="counts__list">${figs.map((f) => html`<li class="count"${c.d(f.label)}>
    <p class="count__value num">${f.verified ? c.number(f.value) + (f.suffix ?? "") : f.token}</p>
    <p class="count__label">${c.tx(f.label)}</p>
    ${f.verified ? "" : pendingChip(c)}
  </li>`)}</ul>
</section>`;
};

// A program's span from its years. Known years put "today" where it falls; a token (unconfirmed) never
// claims a date: an open run ("since [year]") or a running one only fades, a completed one is past.
const spanOf = (c, p) => {
  const [start, end = ""] = c.tx(p.years).split("–");
  const y = [start, end].map((s) => Number(s.trim()));
  const known = y.every((n) => Number.isInteger(n) && n > 1900) && y[1] > y[0];
  const at = known ? Math.min(1, Math.max(0, (new Date().getFullYear() - y[0]) / (y[1] - y[0]))) : p.status === "completed" ? 1 : 0.5;
  const kind = known ? "known" : p.status === "completed" ? "done" : end.trim() ? "running" : "open";
  return { start: start.trim(), end: end.trim(), at: Math.round(at * 1000) / 10, known, kind };
};

// One wide list: the date in its own 160 px column, the title running beside it. pick: the page's own
// list of post URLs (VI) when it has one (news.json has no tags yet); an empty list hides the section.
const relatedNews = (c, id, pick) => {
  const posts = pick ? c.content.news.posts.filter((p) => pick.includes(p.url.vi)) : c.content.news.posts;
  if (!posts.length) return "";
  return html`<section class="related container" aria-labelledby="${id}">
  <h2 class="related__title" id="${id}" data-reveal="rise"><span>${c.t("relatedNews")}</span></h2>
  <ol class="dated-list related__list" data-reveal="stagger">${posts.map((p) => datedPost(c, p))}</ol>
  <p class="related__all">${textLink(c.url("news"), c.t("allNews"))}</p>
</section>`;
};

// ---------- Center: Green Bamboo, Cầu Hàn (P2, PLAN-M4 4.10) ----------
// One template for every center with a door (Little Rose has its own page: little-rose.mjs). The route
// names the unit (routes.mjs unit); the id prefix keeps Green Bamboo's ids as they were.
const PREFIX = { "green-bamboo": "gb", "cau-han": "ch", "thrive-together": "tt", "charity-class": "cc", "disability-pilot": "dp" };

// A program run at the center: one lit band across the page (name, status and years on one baseline,
// the span as a small louver bar lit up to today, the summary under it). Not the hub's program row.
const band = (c, p) => {
  const { at, kind } = spanOf(c, p);
  return html`<li class="band" data-status="${p.status}">
  <h3 class="band__name"><a href="${c.tx(p.path)}">${viKeep(c.tx(p.name))}</a></h3>
  <p class="band__meta">${statusPill(c, p.status)}<span class="band__years" data-draft>${c.tx(p.years)}</span>${icon("arrow", "icon band__go")}</p>
  <span class="band__bar" data-span="${kind}"${kind === "known" ? raw(` style="--at:${at}%"`) : ""} aria-hidden="true"><span class="band__lit"></span></span>
  <p class="band__text"${c.d(p.summary)}>${c.tx(p.summary)}</p>
</li>`;
};

// Where photos will hang (asset B3): one lit wall from the world (gallery-wall: the corridor's far wall,
// its own render at the box's ratio), the six future frames drawn on it in light, one caption. Rendered, never a photo, never a child, never a crop posing as one. Staging only:
// production hangs nothing until HCWA approves photos.
const FRAMES = 6;
const gallery = (c) => {
  if (c.prod) return "";
  return html`<section class="hang container" aria-labelledby="gallery-title">
  <h2 class="hang__title" id="gallery-title" data-reveal="rise"><span>${c.t("photos")}</span></h2>
  <figure class="hang__wall" data-draft>
    <div class="hang__lit" aria-hidden="true">${still("gallery-wall", "hang__still", "(min-width: 1360px) 1320px, 100vw") || raw('<span class="lit-wall"><span class="lit-wall__light"></span></span>')}
      <ol class="hang__frames">${Array.from({ length: FRAMES }, (_, i) => raw(`<li class="hang__frame hang__frame--${i + 1}"></li>`))}</ol>
    </div>
    <figcaption class="hang__cap"><span class="hang__code">B3</span> ${c.t("galleryWaiting")}</figcaption>
  </figure>
</section>`;
};

export function center(c) {
  const { work, site } = c.content;
  const unit = routes[c.routeKey].unit;
  const gb = work.centers.find((x) => x.id === unit);
  if (!gb || unit === "little-rose") throw new Error(`center(): no door page for "${unit}"`);
  const P = work.centerPages[unit];
  const pre = PREFIX[unit];
  const programs = work.programs.filter((p) => p.centers.includes(gb.id));
  const emblem = EMBLEM[gb.id];
  const meta = html`${gb.logo ? html`<img class="lit-head__logo" src="${gb.logo}" width="56" height="56" alt="${c.tx(P.logoAlt)}">` : ""}
    <p class="lit-head__facts">
      ${c.prod ? "" : html`<span data-draft>${c.t("since")} ${c.tx(gb.sinceToken)}</span>`}
      <span${c.d(gb.location)}>${icon("pin")}${c.tx(gb.location)}</span>
    </p>`;
  const body = html`
${litHeader(c, {
  still: "header-" + gb.id,
  unit: gb.accent,
  trail: [[c.t("ourWork"), c.url("ourWork")], [c.t("centers"), c.url("ourWork") + "#" + A[c.lang].centers], [c.tx(gb.shortName)]],
  title: viKeep(c.tx(gb.name)),
  meta,
  extra: c.lang === "en" ? html`<p class="lit-head__vi" lang="vi">${gb.name.vi}</p>` : "",
})}
<div class="day day--center" style="--unit:var(--color-unit-${gb.accent})">
  <section class="plate container" aria-labelledby="glance-title">
    <h2 class="plate__title" id="glance-title">${c.t("glance")}</h2>
    <dl class="plate__facts">
      ${c.prod ? "" : html`<div data-draft><dt>${c.t("since")}</dt><dd>${c.tx(gb.sinceToken)}</dd></div>`}
      <div${c.d(gb.location)}><dt>${c.t("address")}</dt><dd>${c.tx(gb.location)}</dd></div>
      <div${c.d(P.contactPerson)}><dt>${c.t("contact")}</dt><dd>${c.tx(P.contactPerson)}</dd></div>
    </dl>
    <div class="plate__serves">
      <p class="plate__label">${c.t("whoWeServe")}</p>
      <p class="plate__statement"${c.d(gb.serves)} data-reveal>${c.tx(gb.serves)}</p>
    </div>
    <div class="plate__does">
      <p class="plate__label plate__label--mark">${blockIcon(emblem, "plate__mark")}${c.t("whatWeDo")}</p>
      <ul class="does" data-reveal="stagger">${P.activities.map((a) => html`<li${c.d(a)}>${c.tx(a)}</li>`)}</ul>
    </div>
  </section>

  <section class="story container" aria-labelledby="story-title">
    <span class="sun-layer sun-layer--story" aria-hidden="true"></span>
    <h2 class="story__title" id="story-title" data-reveal="rise"><span>${viKeep(c.tx(P.storyTitle))}</span></h2>
    <div class="story__text">
      ${P.story.map((p, i) => html`<p class="${i ? "" : "story__first"}"${c.d(p)} data-reveal>${c.tx(p)}</p>`)}
      <details class="more story__more">
        <summary>${c.t("readFullHistory")}${icon("chevron")}</summary>
        <div class="more__body"><p${c.d(P.history)}>${c.tx(P.history)}</p></div>
      </details>
    </div>
    ${figuresFor(c, gb.id, c.tx(P.figuresTitle), `${pre}-figures`, { nested: true })}
  </section>

  ${gallery(c)}

  <section class="here container" aria-labelledby="${pre}-programs">
    <h2 class="here__title" id="${pre}-programs" data-reveal="rise"><span>${c.t("programsHere")}</span></h2>
    <ul class="bands" data-reveal="stagger">${programs.map((p) => band(c, p))}</ul>
  </section>

  <section class="give container" aria-labelledby="${pre}-help">
    <h2 class="give__title" id="${pre}-help" data-reveal="rise"><span>${viKeep(c.tx(P.helpTitle))}</span></h2>
    <ul class="give__needs" data-reveal="stagger">${P.needs.map((n) => html`<li${c.d(n)}>${quatrefoil("give__mark")}<span>${c.tx(n)}</span></li>`)}</ul>
    <div class="give__act">
      ${button({ href: c.url("donate") + "?center=" + gb.id, label: c.tx(P.donate), cls: "give__btn" })}
      ${textLink(c.url("volunteer"), c.t("volunteer"))}
    </div>
  </section>

  ${relatedNews(c, `${pre}-news`, P.news)}
</div>
${duskBand(c, {
  id: `${pre}-visit`,
  title: c.tx(P.visitTitle),
  body: html`<dl class="dusk__lines">
      <div${c.d(P.contactPerson)}><dt>${c.t("contact")}</dt><dd>${c.tx(P.contactPerson)}</dd></div>
      <div data-draft><dt>${c.t("phone")}</dt><dd><a href="tel:${site.phone.tel}">${site.phone.display}</a></dd></div>
    </dl>
    <p class="dusk__note"${c.d(P.contactNote)}>${c.tx(P.contactNote)}</p>
    ${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk", cls: "dusk__btn" })}`,
})}`;
  return { title: c.tx(gb.name), description: c.tx(gb.serves), body, lit: true, bodyClass: "page-center" };
}

// ---------- Program: Thrive Together (P3) ----------

// Where a program runs, said as a line of names, each behind a small door in its unit paint (the
// transom lit in its emblem). Little Rose: its door shut under plain daylight, the name only (no
// place, no visit link).
const placeDoor = (c, x) => {
  const lr = x.id === "little-rose";
  const built = x.built && !lr;
  const name = c.tx(x.shortName);
  const emblem = EMBLEM[x.id] ? raw(`;--emblem:url('/svg/hole-${EMBLEM[x.id] === "dotTre" ? "dot-tre" : "vom-cau"}.svg')`) : "";
  return html`<li class="place" data-unit="${x.id}" data-door="${lr ? "closed" : "open"}" style="--unit:var(--color-unit-${x.accent})${emblem}">
  <span class="place__door" aria-hidden="true"><span class="place__transom"></span><span class="place__way"></span><span class="place__leaf"></span></span>
  <span class="place__name">${built ? html`<a href="${c.tx(x.path)}">${name}</a>` : name}</span>
</li>`;
};

// The program's span as one band of louver light: start, today, end on one line. The years are tokens
// until HCWA confirms them; "today" sits where the real years put it, in the middle until then.
const timeBand = (c, p) => {
  const { start, end, at, known } = spanOf(c, p);
  return html`<section class="span container" aria-labelledby="span-title">
  <h2 class="sr-only" id="span-title">${c.t("years")}</h2>
  <div class="timeband timeband--${p.status}" style="--at:${at}%"${known ? "" : raw(" data-draft")}>
    <p class="timeband__end timeband__end--start">${start}</p>
    <p class="timeband__bar"><span class="timeband__lit" aria-hidden="true"></span><span class="timeband__today">${c.t("today")}</span></p>
    ${end ? html`<p class="timeband__end">${end}</p>` : ""}
  </div>
</section>`;
};

// Every program shares the template and the lam-stripe still (PLAN-M4 decision 8: the still is a pattern,
// not a unit). Sections with nothing to show leave themselves out: results without scoped figures, where
// it runs without centers, related news without posts. A completed project takes no new partners, so
// its closing band asks for support and contact instead.
export function program(c) {
  const { work } = c.content;
  const id = routes[c.routeKey].program;
  const p = work.programs.find((x) => x.id === id);
  if (!p) throw new Error(`program(): no program "${id}"`);
  const P = work.programPages[id];
  const pre = PREFIX[id];
  const done = p.status === "completed";
  const centers = p.centers.map((cid) => work.centers.find((x) => x.id === cid)).filter(Boolean);
  const [main, ...rest] = c.tx(p.fullName ?? p.name).split(": ");
  const meta = html`${statusPill(c, p.status)}<span class="lit-head__years" data-draft>${c.tx(p.years)}</span>
    ${p.partners.length ? html`<span class="lit-head__funder"><span class="lit-head__k">${c.t("fundedBy")}</span> ${p.partners.join(", ")}</span>` : ""}`;
  const list = (items, id, lit = false) => html`<ol class="slats${lit ? " slats--lit" : ""}" aria-labelledby="${id}" data-reveal="stagger">${items.map((x, i) => html`<li class="slat"${c.d(x)}><span class="slat__n num" aria-hidden="true">${String(i + 1).padStart(2, "0")}</span><span class="slat__text">${c.tx(x)}</span></li>`)}</ol>`;
  const reports = c.prod || !P.reports.length
    ? html`<p class="docs__empty"${c.d(c.content.ui.noDocuments)}>${c.t("noDocuments")} ${textLink(c.url("reports"), c.t("annualReports"))}</p>`
    : html`<ul class="docs__list">${P.reports.map((r) => html`<li class="doc" data-draft>
        ${icon("file", "icon doc__icon")}
        <p class="doc__title">${c.tx(r.title)}</p>
        <p class="doc__meta">${c.tx(r.year)} · ${c.tx(P.reportType)} · PDF · <span lang="${r.docLang}">${r.docLang === "vi" ? "Tiếng Việt" : "English"}</span> · ${r.size}</p>
      </li>`)}</ul>`;
  const body = html`
${litHeader(c, {
  still: "header-thrive-together",
  trail: [[c.t("ourWork"), c.url("ourWork")], [c.t("programs"), c.url("ourWork") + "#" + A[c.lang].programs], [c.tx(p.name)]],
  title: rest.length ? { main: viKeep(main), sub: rest.join(": ") } : viKeep(main),
  meta,
  size: "program",
  extra: c.lang === "en" ? html`<p class="lit-head__vi" lang="vi">${p.name.vi}</p>` : "",
})}
<div class="day day--program">
  <section class="brief container" aria-labelledby="${pre}-summary">
    <span class="lam lam--brief" aria-hidden="true"></span>
    <h2 class="sr-only" id="${pre}-summary">${c.t("summary")}</h2>
    <p class="brief__lead"${c.d(p.summary)} data-reveal>${c.tx(p.summary)}</p>
    <p class="brief__focus"${c.d(P.focus)} data-reveal>${c.tx(P.focus)}</p>
    ${done ? html`<p class="brief__ended">${c.t("status_ended")}</p>` : ""}
  </section>

  ${timeBand(c, p)}

  <section class="aims container" aria-labelledby="${pre}-goals">
    <div class="aims__col">
      <h2 class="aims__title" id="${pre}-goals" data-reveal="rise"><span>${c.t("goals")}</span></h2>
      ${list(P.goals, `${pre}-goals`, true)}
    </div>
    <div class="aims__col aims__col--next">
      <h2 class="aims__title" id="${pre}-activities" data-reveal="rise"><span>${c.t("activities")}</span></h2>
      ${list(P.activities, `${pre}-activities`)}
    </div>
  </section>

  ${figuresFor(c, p.id, c.t("results"), `${pre}-results`)}

  ${centers.length ? html`<section class="where container" aria-labelledby="${pre}-where">
    <h2 class="where__title" id="${pre}-where" data-reveal="rise"><span>${c.t("whereItRuns")}</span></h2>
    <ul class="places-row" data-reveal="stagger">${centers.map((x) => placeDoor(c, x))}</ul>
    <div class="where__notes">
      <p class="where__note"${c.d(P.runsAtNote)}>${c.tx(P.runsAtNote)}</p>
      ${centers.some((x) => x.id === "little-rose") ? html`<p class="where__note"${c.d(work.littleRose.privacyNote)}>${c.tx(work.littleRose.privacyNote)}</p>` : ""}
    </div>
  </section>` : ""}

  <section class="docs container" aria-labelledby="${pre}-docs">
    <h2 class="docs__title" id="${pre}-docs" data-reveal="rise"><span>${c.t("documents")}</span></h2>
    ${reports}
  </section>

  ${relatedNews(c, `${pre}-news`, P.news)}
</div>
${duskBand(c, {
  id: `${pre}-cta`,
  title: c.tx(P.ctaTitle),
  draft: P.ctaTitle.draft,
  body: html`<div class="dusk__actions">${button({ href: c.url("donate"), label: c.t("donate") })}${done
    ? button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk" })
    : button({ href: c.url("partner"), label: c.tx(P.partner), kind: "on-dusk" })}</div>`,
})}`;
  return { title: c.tx(p.name), description: c.tx(p.summary), body, lit: true, bodyClass: "page-program" };
}
