import { html } from "../../lib/html.mjs";
import { textLink, storyCard, newsItem, sectionHead } from "../../partials/components.mjs";

// Home H6 news. Phase 1 placeholder until rebuilt. Owner: page stream (prototype/PLAN.md section 3).
export function news(c) {
  const { news } = c.content;
  return html`
<section class="section section--shade" aria-labelledby="latest-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "latest-title", title: c.t("latest"), link: textLink(c.url("news"), c.t("allNews")) })}
    <div class="latest">
      ${storyCard(c, news.featuredStory, { featured: true })}
      <ul class="news-list">${news.posts.map((p) => newsItem(c, p))}</ul>
    </div>
  </div>
</section>`;
}
