import { html } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { visibleFigures } from "../lib/content.mjs";
import { textLink, button, icon, pendingChip, duskBand } from "../partials/components.mjs";
import { litHeader } from "../partials/lit-header.mjs";
import { still, litWall } from "../partials/still.mjs";

// Impact & Reports (PLAN-M4 4.6, 06-PAGES): midday. The figures follow the verified rule: they render
// from impact.json through visibleFigures(), so staging shows each as its token with the pending chip and
// production shows none; a section with no figure left hides itself. Numbers never count up and never
// move. The by-year table is tokens and staging only. The reports library and its id render in production
// too (the footer links to it on every page), with the empty state until HCWA publishes a report.

export const REPORTS_ID = { vi: "bao-cao", en: "reports" };

const figures = (c) => {
  const figs = visibleFigures(c.content.impact.filter((f) => f.scope === "organization"), c.prod);
  if (!figs.length) return "";
  return html`<section class="counts counts--page container" aria-labelledby="tally-title">
  <h2 class="counts__title" id="tally-title">${c.t("headlineFigures")}</h2>
  <ul class="counts__list">${figs.map((f) => html`<li class="count"${c.d(f.label)}>
    <p class="count__value num">${f.verified ? c.number(f.value) + (f.suffix ?? "") : f.token}</p>
    <p class="count__label">${c.tx(f.label)}</p>
    ${f.verified ? "" : pendingChip(c)}
  </li>`)}</ul>
</section>`;
};

// A plain table, not a chart (06-PAGES: a chart only once HCWA supplies five years or more).
const byYear = (c) => {
  if (c.prod) return "";
  const B = c.content.impactPage.page.byYear;
  return html`<section class="years container" aria-labelledby="years-title" data-draft>
  <h2 class="years__title" id="years-title">${c.t("byYear")}</h2>
  <div class="years__scroll" role="region" aria-labelledby="years-title" tabindex="0">
    <table class="years__table">
      <thead><tr><th scope="col">${c.t("yearCol")}</th>${B.columns.map((col) => html`<th scope="col">${c.tx(col)}</th>`)}</tr></thead>
      <tbody>${Array.from({ length: B.rows }, () => html`<tr><th scope="row">${c.t("yearToken")}</th>${B.columns.map(() => html`<td data-pending>${B.cell}</td>`)}</tr>`)}</tbody>
    </table>
  </div>
  <p class="years__note">${c.t("stagingOnly")}</p>
</section>`;
};

// Stories of change: the home page's featured-story placeholder (data-story-placeholder, a production
// blocker) until HCWA approves a story. A lit wall stands where its photo would go, never a child.
const stories = (c) => {
  const I = c.content.impactPage.page;
  const s = c.content.news.featuredStory;
  if (c.prod) return "";
  return html`<section class="change container" aria-labelledby="change-title">
  <h2 class="change__title" id="change-title" data-reveal="rise"><span>${c.t("storiesOfChange")}</span></h2>
  <p class="change__lead"${c.d(I.storiesLead)}>${c.tx(I.storiesLead)}</p>
  <article class="feature change__story" data-story-placeholder data-draft>
    <div class="feature__wall">${still("story", "feature__still", "(min-width: 1024px) 50vw, 100vw") || litWall()}</div>
    <div class="feature__text">
      <p class="feature__cat">${c.t("cat_" + s.category)}</p>
      <h3 class="feature__title"><a href="${c.url("news")}">${c.tx(s.title)}</a></h3>
      <p class="feature__excerpt">${c.tx(s.excerpt)}</p>
      <p class="feature__note">${c.t("namesChanged")}</p>
    </div>
  </article>
</section>`;
};

// The library: report rows once HCWA publishes them (impact.json reports), the empty state until then.
const reports = (c) => {
  const id = REPORTS_ID[c.lang];
  const list = c.content.impactPage.reports ?? [];
  return html`<section class="docs library container" id="${id}" aria-labelledby="${id}-title">
  <h2 class="docs__title" id="${id}-title" data-reveal="rise"><span>${c.t("reportsLibrary")}</span></h2>
  ${list.length
    ? html`<ul class="docs__list">${list.map((r) => html`<li class="doc">
        ${icon("file", "icon doc__icon")}
        <p class="doc__title"><a href="${r.href}">${c.tx(r.title)}</a></p>
        <p class="doc__meta">${r.year} · PDF · <span lang="${r.docLang}">${r.docLang === "vi" ? "Tiếng Việt" : "English"}</span> · ${r.size}</p>
      </li>`)}</ul>`
    : html`<p class="docs__empty"${c.d(c.content.ui.noReports)}>${c.t("noReports")}</p>`}
</section>`;
};

export function impact(c) {
  const I = c.content.impactPage.page;
  const body = html`
${litHeader(c, { still: "header-impact", trail: [[c.t("impact")]], title: viKeep(c.t("impact")), intro: c.tx(I.intro), introDraft: I.intro.draft, size: "display" })}
<div class="day day--impact">
  ${figures(c)}
  ${byYear(c)}
  ${stories(c)}
  ${reports(c)}
  <section class="clear container" aria-labelledby="clear-title">
    <h2 class="clear__title" id="clear-title">${c.t("transparency")}</h2>
    <p class="clear__text"${c.d(I.transparencyText)}>${c.tx(I.transparencyText)}</p>
    <ul class="clear__links">
      <li>${textLink(c.url("safeguarding"), c.t("safeguarding"))}</li>
      <li>${textLink(c.url("policies"), c.t("policies"))}</li>
      <li>${textLink(c.url("partners"), c.t("partners"))}</li>
    </ul>
  </section>
</div>
${duskBand(c, {
  id: "impact-cta",
  title: c.tx(I.ctaTitle),
  draft: I.ctaTitle.draft,
  body: html`<div class="dusk__actions">${button({ href: c.url("donate"), label: c.t("donate") })}${button({ href: c.url("news"), label: c.t("allStories"), kind: "on-dusk" })}</div>`,
})}`;
  return { title: c.t("impact"), description: c.t("impact"), body, lit: true, bodyClass: "page-impact" };
}
