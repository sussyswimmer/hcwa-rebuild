import { html } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";
import { breadcrumb, button, icon, duskBand, reachLines } from "../partials/components.mjs";
import { still, litWall } from "../partials/still.mjs";
import { stage, skipOpening } from "../partials/scroll3d.mjs";

// News hub (PLAN-M4 4.13, 5.2): "notices on the wall". The page is one stretch of the house's lime wall
// with the latest posts pinned to it as paper notices. On the stage (styles/openings.css) the wall is held
// behind the page while the camera pulls back from the newest notice, each notice turns from a slight
// angle to flat as it reaches its reading place, and a quatrefoil of sun passes over each in turn. At rest
// (no JS, reduced motion, pause, ?tier=0) it is the same wall with the notices flat.
//
// The notices are the archive itself: one list, no copy. The posts live on hcwa.org.vn until the
// WordPress migration brings the articles (PLAN-M4 decision 2), so every card links out with the
// external marker, and nothing here invents a body, an image or a category page. Categories filter in
// place (decision 3): chips are links to #f-<category> (CSS :target without JS, ui/filters.js with it).

const CATEGORIES = ["news", "story", "event"];

// One notice: category, the real publication date, the title as the link (it covers the paper).
const notice = (c, p) => {
  const [, dm, sep, year] = c.date(p.published).match(/^(.*?)(\D)(\d{4})$/);
  return html`<li class="notice" data-areas="${p.category}">
  <article class="notice__paper">
    <span class="notice__pin" aria-hidden="true"></span>
    <p class="notice__cat">${c.t("cat_" + p.category)}</p>
    <h3 class="notice__title"><a href="${c.tx(p.url)}">${c.tx(p.title)}<span class="sr-only"> (${c.t("externalPost")})</span></a></h3>
    <p class="notice__foot"><time class="notice__date" datetime="${p.published}"><span class="dated__dm">${dm}</span><span class="sr-only">${sep}</span><span class="dated__y">${year}</span></time>${icon("external", "icon notice__ext")}</p>
  </article>
</li>`;
};

// The category chips: plain links, the Our Work pattern (ui/filters.js reads data-area / data-areas).
const chips = (c, H) => html`<nav class="filters news-filters" aria-labelledby="news-filter-label">
  ${CATEGORIES.map((k) => html`<span class="filters__anchor" id="f-${k}"></span>`)}
  <p class="filters__label" id="news-filter-label">${c.tx(H.filterLabel)}</p>
  <ul class="filters__list">
    <li><a class="chip chip--all" href="./" data-area="">${c.t("all")}</a></li>
    ${CATEGORIES.map((k) => html`<li><a class="chip" href="?area=${k}#f-${k}" data-area="${k}">${c.t("cat_" + k)}</a></li>`)}
  </ul>
  <p class="sr-only" data-filter-status aria-live="polite" data-posts-one="${c.t("countPost")}" data-posts-many="${c.t("countPosts")}" data-template="${c.t("filterShowingPosts")}"></p>
</nav>`;

// The featured story slot waits for an HCWA-approved story (content/verified/): staging only, and
// render.mjs refuses a production page that still carries data-story-placeholder. Its picture is a lit
// wall from the world, never a photo.
const featured = (c, s, H) => html`<section class="news-feature container" aria-labelledby="feature-title">
  <h2 class="news-feature__title" id="feature-title">${c.tx(H.featuredTitle)}</h2>
  <article class="feature" data-story-placeholder${s.draft ? html` data-draft` : ""}>
    <div class="feature__wall">${still("story", "feature__still", "(min-width: 1024px) 50vw, 100vw") || litWall()}</div>
    <div class="feature__text">
      <p class="feature__cat">${c.t("cat_" + s.category)}</p>
      <h3 class="feature__title"><a href="${c.url("news")}">${c.tx(s.title)}</a></h3>
      <p class="feature__excerpt">${c.tx(s.excerpt)}</p>
      <p class="feature__note">${c.t("namesChanged")}</p>
    </div>
  </article>
</section>`;

export function news(c) {
  const { news: N, site } = c.content;
  const H = N.hub;
  // Newest first; posts with the same date keep their content order.
  const posts = [...N.posts].sort((a, b) => b.published.localeCompare(a.published));
  // Which categories have no posts is known when the page is built, so the empty note needs no script.
  const empty = CATEGORIES.filter((k) => !posts.some((p) => p.category === k)).join(" ");
  const story = c.prod ? null : N.featuredStory;
  const wall = html`<section class="newswall" aria-labelledby="news-title" data-hub>
  <div class="newswall__bg" aria-hidden="true"><div class="newswall__plane"><span class="newswall__light"></span><span class="newswall__sun"></span></div></div>
  <header class="newswall__head container">
    ${breadcrumb(c, [[c.t("news")]])}
    <h1 class="newswall__title" id="news-title">${viKeep(c.t("news"))}</h1>
    <p class="newswall__intro"${c.d(H.intro)}>${c.tx(H.intro)}</p>
    ${skipOpening("#news-list", c.tx(H.skip))}
  </header>
  <div class="container newswall__filters">${chips(c, H)}</div>
  <div class="container newswall__board" data-filter-group>
    <h2 class="sr-only" id="news-list" tabindex="-1">${c.tx(H.listTitle)}</h2>
    <ol class="notices" aria-labelledby="news-list">${posts.map((p) => notice(c, p))}</ol>
    <p class="filter-empty" data-filter-empty data-show-for="${empty}" hidden>${c.tx(H.empty)} <a href="./" data-area="">${c.tx(H.showAll)}</a></p>
    <p class="newswall__note">${icon("external")}<span>${c.tx(H.externalNote)}</span></p>
  </div>
</section>`;
  const body = html`
${stage("news", wall, {}, { min: 0.75, pin: ".newswall__plane" })}
<div class="day day--news">
  ${story ? featured(c, story, H) : ""}
</div>
${duskBand(c, {
  id: "news-contact",
  title: c.tx(site.contactBand.title),
  body: html`${reachLines(c, site.email.general)}${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk", cls: "dusk__btn" })}`,
})}`;
  return { title: c.t("news"), description: c.tx(H.intro), body, bodyClass: "page-news" };
}
