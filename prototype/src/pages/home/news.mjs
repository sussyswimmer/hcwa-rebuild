import { html } from "../../lib/html.mjs";
import { viKeep } from "../../lib/vikeep.mjs";
import { icon, datedPost } from "../../partials/components.mjs";
import { still, litWall } from "../../partials/still.mjs";

// Home H6 news (Desire 3), in the cool shade. A typographic list of real posts with big dates; the
// featured story is a placeholder until HCWA approves a real one, so it exists on staging only
// (data-story-placeholder: render.mjs fails a production build that still carries it). Its image is a
// lit wall from the world, never a photo. The H2 comes first in the source (the story's H3 files under it);
// grid areas put the story on the left on desktop. Owner: page stream (prototype/PLAN.md section 3).
const featured = (c, s) => html`<article class="feature" data-story-placeholder${s.draft ? html` data-draft` : ""}>
  <div class="feature__wall">${still("story", "feature__still", "(min-width: 1024px) 55vw, 100vw") || litWall()}</div>
  <div class="feature__text">
    <p class="feature__cat">${c.t("cat_" + s.category)}</p>
    <h3 class="feature__title"><a href="${c.url("news")}">${c.tx(s.title)}</a></h3>
    <p class="feature__excerpt">${c.tx(s.excerpt)}</p>
    <p class="feature__note">${c.t("namesChanged")}</p>
  </div>
</article>`;

export function news(c) {
  const { news } = c.content;
  const story = c.prod ? null : news.featuredStory;
  return html`
<section class="wall wall--shade news${story ? "" : " news--solo"}" id="news" aria-labelledby="latest-title">
  <div class="container news__grid">
    <div class="news__list" data-reveal>
      <h2 class="wall__title" id="latest-title">${viKeep(c.t("latest"))}</h2>
      <ol class="dated-list">${news.posts.map((p) => datedPost(c, p))}</ol>
      <a class="news__all text-link" href="${c.url("news")}"><span>${c.t("allNews")}</span>${icon("arrow")}</a>
    </div>
    ${story ? featured(c, story) : ""}
  </div>
</section>`;
}
