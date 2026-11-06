import { html } from "../lib/html.mjs";
import { button, textLink, placeholder, figureStrip, centerCard, programRow, storyCard, newsItem, sectionHead, icon, pauseToggle } from "../partials/components.mjs";
import { heroMedia, heroStage } from "../partials/world.mjs";
import { keepHyphens, viKeep } from "../lib/vikeep.mjs";

// PLAN section 3. This run rebuilds H0-H2 (shell, header, hero S1 Nắng vào); the sections below the
// hero are the Phase 1 ones on the new tokens until the home rebuild (passage, corridor, sheets).
export function home(c) {
  const { site, work, news, involve } = c.content;
  const hero = site.hero;
  const programs = [...work.programs].sort((a, b) => (a.status === "active" ? -1 : 1) - (b.status === "active" ? -1 : 1)).slice(0, 4);

  const body = html`
<section class="hero" id="hero" data-world="room" aria-labelledby="hero-title">
  ${heroMedia()}
  ${heroStage()}
  <div class="container hero__inner">
    <h1 class="hero__title" id="hero-title" data-text-safe><span${c.d(hero.title)}>${viKeep(c.tx(hero.title))}</span></h1>
    <p class="hero__intro" data-text-safe><span${c.d(hero.intro)}>${keepHyphens(c.tx(hero.intro))}</span></p>
    <div class="hero__actions" data-text-safe>
      ${button({ href: c.url("ourWork"), label: c.t("exploreWork") })}
      ${button({ href: c.url("getInvolved"), label: c.t("getInvolved"), kind: "secondary" })}
    </div>
    ${pauseToggle(c, "hero__pause", { textSafe: "display" })}
  </div>
</section>

<section class="section section--sun" aria-labelledby="what-title" data-reveal>
  <div class="container split">
    <div class="split__lead">
      <h2 id="what-title">${viKeep(c.tx(site.whatWeDo.title))}</h2>
      <p${c.d(site.whatWeDo.summary)}>${c.tx(site.whatWeDo.summary)}</p>
    </div>
    <ul class="focus-list">
      ${work.focusAreas.map(
        (a) => html`<li class="focus-item">
          <span class="ph ph--icon" role="img" aria-label="${a.icon} · ${c.tx(a.name)}"><span class="ph__code">${a.icon}</span></span>
          <div>
            <h3><a href="${c.url("ourWork")}?area=${a.id}">${c.tx(a.name)}</a></h3>
            <p${c.d(a.text)}>${c.tx(a.text)}</p>
          </div>
        </li>`,
      )}
    </ul>
  </div>
</section>

${(() => {
  // Impact leaves the hero (05-DESIGN D11.2); it joins the Our work sheet in the home rebuild.
  const strip = figureStrip(c, "organization", { min: 2, cls: "figures--strip" });
  return strip ? html`<section class="section section--tight" aria-label="${c.t("impact")}" data-reveal><div class="container impact-strip">${strip}${textLink(c.url("impact"), c.t("seeImpact"))}</div></section>` : "";
})()}

<section class="section section--sunken" aria-labelledby="centers-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "centers-title", title: c.t("centers"), intro: c.tx(site.centersIntro), draftIntro: true, link: textLink(c.url("ourWork") + (c.lang === "vi" ? "#co-so" : "#centers"), c.t("seeAll")) })}
    <div class="center-grid">${work.centers.map((x) => centerCard(c, x))}</div>
  </div>
</section>

<section class="section" aria-labelledby="programs-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "programs-title", title: c.t("programs"), link: textLink(c.url("ourWork") + (c.lang === "vi" ? "#du-an" : "#programs"), c.t("allPrograms")) })}
    <ul class="program-list">${programs.map((p) => programRow(c, p))}</ul>
  </div>
</section>

<section class="section section--shade" aria-labelledby="latest-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "latest-title", title: c.t("latest"), link: textLink(c.url("news"), c.t("allNews")) })}
    <div class="latest">
      ${storyCard(c, news.featuredStory, { featured: true })}
      <ul class="news-list">${news.posts.map((p) => newsItem(c, p))}</ul>
    </div>
  </div>
</section>

<section class="section section--help" aria-labelledby="help-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "help-title", title: c.tx(site.helpTitle) })}
    <ul class="ways">
      ${involve.ways.map(
        (w) => html`<li class="way">
          ${placeholder(c, { code: w.art, label: w.title, ratio: "1:1", illustration: true }, "way__art")}
          <h3><a href="${c.url(w.page === "partner" ? "partner" : w.page === "in-kind" ? "inKind" : w.page)}">${c.tx(w.title)}</a></h3>
          <p${c.d(w.text)}>${c.tx(w.text)}</p>
        </li>`,
      )}
    </ul>
  </div>
</section>

<section class="section" aria-labelledby="partners-title" data-reveal>
  <div class="container">
    ${sectionHead(c, { id: "partners-title", title: c.tx(site.partnersTitle) })}
    <ul class="logo-wall" aria-label="${c.t("partnerLogo")}">
      ${Array.from({ length: 6 }, () => html`<li><span class="ph ph--logo" role="img" aria-label="C5 · ${c.t("partnerLogo")}"><span class="ph__code">C5</span></span></li>`)}
    </ul>
    <p class="logo-wall__links">${textLink(c.url("partners"), c.t("partners"))} ${textLink(c.url("partner"), c.t("becomePartner"))}</p>
  </div>
</section>

<section class="cta-band" aria-labelledby="contact-title">
  <div class="container cta-band__inner">
    <h2 id="contact-title">${c.tx(site.contactBand.title)}</h2>
    <div class="cta-band__actions">${button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-dusk" })}</div>
    <ul class="cta-band__contact" data-draft>
      <li>${icon("phone")}<a href="tel:${site.phone.tel}">${site.phone.display}</a></li>
      <li>${icon("mail")}<a href="mailto:${site.email.general}">${site.email.general}</a></li>
    </ul>
  </div>
</section>`;

  return {
    title: c.tx(site.orgName),
    description: c.tx(site.hero.intro),
    body,
    bodyClass: "page-home",
    lit: true,
    world: true,
  };
}
