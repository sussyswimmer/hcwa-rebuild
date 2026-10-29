import { html, raw } from "../lib/html.mjs";
import { breadcrumb, centerCard, programRow, placeholder, figureStrip, textLink, button, ctaBand, unitDot, reportRow, newsItem, icon, statusPill } from "../partials/components.mjs";

const A = { vi: { centers: "co-so", programs: "du-an" }, en: { centers: "centers", programs: "programs" } };

const pageHeader = (c, { trail, title, intro, accent, draftIntro, viName, extra = "" }) => html`<header class="page-header"${accent ? html` style="--unit:var(--color-unit-${accent})" data-unit` : ""}>
  <div class="container">
    ${breadcrumb(c, trail)}
    <h1>${title}</h1>
    ${viName && c.lang === "en" ? html`<p class="page-header__vi" lang="vi">(${viName})</p>` : ""}
    ${intro ? html`<p class="page-header__intro"${draftIntro ? raw(" data-draft") : ""}>${intro}</p>` : ""}
    ${extra}
  </div>
</header>`;

// Our Work hub: centers (places) and programs (time-bound), filterable by focus area.
export function ourWork(c) {
  const { work } = c.content;
  const intro = { vi: "Hội vận hành các cơ sở lâu dài và triển khai các dự án có thời hạn cùng đối tác.", en: "HCWA runs long-term centers and carries out time-bound projects with partners." };
  const chip = (id, label) => html`<li><a class="chip" href="?area=${id}" data-area="${id}">${label}</a></li>`;
  const body = html`
${pageHeader(c, { trail: [[c.t("ourWork")]], title: c.t("ourWork"), intro: c.tx(intro), draftIntro: true })}
<div class="container">
  <nav class="filters" aria-label="${c.t("filterArea")}">
    <ul>
      <li><a class="chip" href="./" data-area="" aria-current="true">${c.t("all")}</a></li>
      ${work.focusAreas.map((a) => chip(a.id, c.tx(a.name)))}
    </ul>
  </nav>
  <p class="filters__empty" data-filter-empty hidden>${c.t("noResults")} <a href="./">${c.t("showAll")}</a></p>
</div>
<section class="section section--tight" aria-labelledby="${A[c.lang].centers}" data-filter-group>
  <div class="container">
    <h2 id="${A[c.lang].centers}">${c.t("centers")}</h2>
    <div class="center-grid">${work.centers.map((x) => html`<div data-areas="${x.areas.join(" ")}">${centerCard(c, x)}</div>`)}</div>
  </div>
</section>
<section class="section section--tight" aria-labelledby="${A[c.lang].programs}" data-filter-group>
  <div class="container">
    <h2 id="${A[c.lang].programs}">${c.t("programs")}</h2>
    <ul class="program-list">${work.programs.map((p) => programRow(c, p))}</ul>
  </div>
</section>
<section class="section section--tight">
  <div class="container">
    <aside class="note" data-draft>
      ${icon("info")}
      <p>${c.tx({ vi: "Cơ sở là nơi Hội vận hành lâu dài. Dự án và chương trình được tài trợ, có thời gian bắt đầu và kết thúc.", en: "Centers are long-term places HCWA runs. Projects and programs are funded work with a start and an end." })}</p>
    </aside>
  </div>
</section>`;
  return { title: c.t("ourWork"), description: c.tx(intro), body };
}

// Center template (06-PAGES), filled for Green Bamboo.
export function center(c) {
  const { work, news } = c.content;
  const gb = work.centers.find((x) => x.id === "green-bamboo");
  const programs = work.programs.filter((p) => p.centers.includes("green-bamboo"));
  const T = (vi, en) => c.tx({ vi, en });
  const glance = [
    [T("Đối tượng", "Who we serve"), c.tx(gb.serves)],
    [T("Hoạt động", "What we do"), html`<ul>${[
      T("Lớp bồi dưỡng văn hóa và tiếng Anh", "Catch-up classes and English"),
      T("Câu lạc bộ giáo dục và kỹ năng sống cho thanh thiếu niên", "Education and life-skills clubs for young people"),
      T("Hội thảo cho phụ huynh và tư vấn gia đình", "Workshops for parents and family counseling"),
      T("Hỗ trợ chăm sóc sức khỏe cho trẻ", "Help accessing healthcare"),
    ].map((x) => html`<li>${x}</li>`)}</ul>`],
    [c.t("since"), c.tx(gb.sinceToken)],
    [c.t("contact"), T("[Người liên hệ và số điện thoại đã được duyệt]", "[Approved contact person and phone]")],
  ];
  const story = [
    T(
      "Tre Xanh bắt đầu là một câu lạc bộ, sau đó trở thành mái ấm cho trẻ trai không nơi nương tựa, giúp các em có chỗ ở, được học và học nghề.",
      "Green Bamboo began as a club and later became a shelter for homeless boys, giving them a home, schooling and vocational training.",
    ),
    T(
      "Từ [tháng/năm], Tre Xanh chuyển sang mô hình cơ sở bảo trợ xã hội. Thay vì nuôi dạy tập trung, cơ sở hỗ trợ trẻ và gia đình ngay tại cộng đồng: lớp học, câu lạc bộ, tư vấn, chăm sóc sức khỏe và kết nối dịch vụ xã hội.",
      "In [month/year] Green Bamboo became a social protection center. Instead of residential care, it now supports children and families in their community: classes, clubs, counseling, healthcare and links to social services.",
    ),
  ];
  const gallery = Array.from({ length: 6 }, (_, i) => ({ code: "B3", label: { vi: `Ảnh hoạt động ${i + 1}`, en: `Activity photo ${i + 1}` }, ratio: "3:2" }));
  const body = html`
${pageHeader(c, {
  trail: [[c.t("ourWork"), c.url("ourWork")], [c.t("centers"), c.url("ourWork") + "#" + A[c.lang].centers], [c.tx(gb.name)]],
  title: c.tx(gb.name),
  viName: gb.name.vi,
  accent: gb.accent,
  extra: html`<div class="page-header__meta">
    <img class="unit-logo" src="${gb.logo}" width="72" height="72" alt="${T("Logo Cơ sở Bảo trợ Xã hội Tre Xanh", "Green Bamboo Social Protection Center logo")}">
    <p><span data-draft>${c.t("since")} ${c.tx(gb.sinceToken)}</span><span data-draft>${c.tx(gb.location)}</span></p>
  </div>`,
})}
<div class="container cover">${placeholder(c, { code: "B1", label: gb.image.label, ratio: "16:9" }, "cover__media")}</div>

<div class="container article-grid">
  <aside class="glance" aria-labelledby="glance-title" data-draft>
    <h2 id="glance-title" class="h4">${T("Thông tin nhanh", "At a glance")}</h2>
    <dl>${glance.map(([k, v]) => html`<div><dt>${k}</dt><dd>${v}</dd></div>`)}</dl>
  </aside>
  <section class="prose" aria-labelledby="story-title">
    <h2 id="story-title">${T("Câu chuyện của chúng tôi", "Our story")}</h2>
    ${story.map((p) => html`<p data-draft>${p}</p>`)}
    <details class="more">
      <summary>${T("Đọc toàn bộ lịch sử", "Read the full history")}${icon("chevron")}</summary>
      <p data-draft>${T("[Lịch sử đầy đủ do HCWA cung cấp, kèm các năm đã được xác minh.]", "[Full history supplied by HCWA, with verified years.]")}</p>
    </details>
  </section>
</div>

<section class="section section--tight" aria-labelledby="gb-figures">
  <div class="container">
    <h2 id="gb-figures">${T("Kết quả tại Tre Xanh", "Green Bamboo in numbers")}</h2>
    ${figureStrip(c, "green-bamboo")}
  </div>
</section>

<section class="section section--tight" aria-labelledby="gallery-title">
  <div class="container">
    <h2 id="gallery-title">${T("Hình ảnh hoạt động", "Photos")}</h2>
    <ul class="gallery" data-gallery>
      ${gallery.map((g, i) => html`<li><button class="gallery__item" type="button" data-index="${i}" aria-label="${c.tx(g.label)}: ${T("phóng to", "open larger")}">${placeholder(c, g)}</button></li>`)}
    </ul>
  </div>
</section>
<dialog class="lightbox" data-lightbox aria-label="${T("Hình ảnh hoạt động", "Photos")}">
  <div class="lightbox__stage" data-lightbox-stage></div>
  <p class="lightbox__count" data-lightbox-count aria-live="polite"></p>
  <button class="icon-btn lightbox__prev" type="button" data-lightbox-prev aria-label="${T("Ảnh trước", "Previous photo")}">${icon("prev")}</button>
  <button class="icon-btn lightbox__next" type="button" data-lightbox-next aria-label="${T("Ảnh sau", "Next photo")}">${icon("next")}</button>
  <button class="icon-btn lightbox__close" type="button" data-lightbox-close aria-label="${c.t("close")}">${icon("close")}</button>
</dialog>

<section class="section section--tight" aria-labelledby="gb-programs">
  <div class="container">
    <h2 id="gb-programs">${T("Dự án tại cơ sở", "Programs run here")}</h2>
    <ul class="program-list">${programs.map((p) => programRow(c, p))}</ul>
  </div>
</section>

<section class="section section--sunken section--tight" aria-labelledby="gb-help">
  <div class="container split">
    <div class="split__lead">
      <h2 id="gb-help">${T("Cách giúp Tre Xanh", "How to help Green Bamboo")}</h2>
      <p data-draft>${T("[Nhu cầu cụ thể của cơ sở do HCWA cung cấp, ví dụ học bổng, đồ dùng học tập, giáo viên tình nguyện.]", "[Specific needs supplied by HCWA, for example scholarships, school supplies, volunteer teachers.]")}</p>
    </div>
    <div class="stack">
      ${button({ href: c.url("donate") + "?center=green-bamboo", label: T("Ủng hộ Tre Xanh", "Donate to Green Bamboo") })}
      ${textLink(c.url("volunteer"), c.t("volunteer"))}
    </div>
  </div>
</section>

<section class="section section--tight" aria-labelledby="gb-news">
  <div class="container">
    <h2 id="gb-news">${T("Tin liên quan", "Related news")}</h2>
    <ul class="news-list news-list--row">${news.posts.map((p) => newsItem(c, p))}</ul>
  </div>
</section>
${ctaBand(c, {
  title: T("Muốn đến thăm Tre Xanh?", "Want to visit Green Bamboo?"),
  actions: button({ href: c.url("contact"), label: c.t("contactUs"), kind: "on-blue" }),
  extra: html`<p class="cta-band__note" data-draft>${T("[Chỉ hiển thị thông tin liên hệ HCWA đã duyệt.]", "[Only contact details HCWA has approved.]")}</p>`,
})}`;
  return { title: c.tx(gb.name), description: c.tx(gb.serves), body };
}

// Program template (06-PAGES), filled for Thrive Together.
export function program(c) {
  const { work, news } = c.content;
  const p = work.programs.find((x) => x.id === "thrive-together");
  const T = (vi, en) => c.tx({ vi, en });
  const list = (items) => html`<ul class="check-list">${items.map((x) => html`<li>${icon("check")}<span>${x}</span></li>`)}</ul>`;
  const body = html`
${pageHeader(c, {
  trail: [[c.t("ourWork"), c.url("ourWork")], [c.t("programs"), c.url("ourWork") + "#" + A[c.lang].programs], [c.tx(p.name)]],
  title: c.tx(p.fullName),
  viName: p.name.vi,
  extra: html`<div class="page-header__meta">${statusPill(c, p.status)}<p><span data-draft>${c.tx(p.years)}</span></p>
    <span class="ph ph--logo ph--inline" role="img" aria-label="C5 · ${p.partners[0]}"><span class="ph__code">C5</span><span class="ph__label">${p.partners[0]}</span></span></div>`,
})}
<div class="container article-grid article-grid--wide">
  <section class="prose" aria-labelledby="tt-summary">
    <h2 id="tt-summary" class="sr-only">${T("Tóm tắt", "Summary")}</h2>
    <p class="lead"${c.d(p.summary)}>${c.tx(p.summary)}</p>
    <p data-draft>${T(
      "Chương trình đặc biệt hướng đến trẻ em di cư, trẻ thiếu giấy tờ pháp lý, trẻ khuyết tật, trẻ chịu ảnh hưởng bởi HIV/AIDS và trẻ sống trong môi trường thiếu an toàn.",
      "It focuses on migrant children, children without legal documents, children with disabilities, children affected by HIV/AIDS and children living in unsafe places.",
    )}</p>
    <div class="two-col">
      <div data-draft>
        <h2 class="h3">${T("Mục tiêu", "Goals")}</h2>
        ${list([
          T("Trẻ được tiếp cận liên tục với giáo dục", "Children keep steady access to education"),
          T("Trẻ được chăm sóc sức khỏe thể chất và tinh thần", "Children get physical and mental healthcare"),
          T("Trẻ phát triển kỹ năng phù hợp với độ tuổi", "Children build age-appropriate skills"),
        ])}
      </div>
      <div data-draft>
        <h2 class="h3">${T("Hoạt động", "Activities")}</h2>
        ${list([
          T("Củng cố và mở rộng hoạt động tại các cơ sở của Hội", "Strengthen and expand activities at HCWA's centers"),
          T("Tập huấn cho phụ huynh, nhân viên và cộng tác viên", "Train parents, staff and community collaborators"),
        ])}
      </div>
    </div>
  </section>
  <aside class="glance" aria-labelledby="tt-where">
    <h2 id="tt-where" class="h4">${T("Nơi thực hiện và nhà tài trợ", "Where it runs and who funds it")}</h2>
    <dl>
      <div data-draft><dt>${T("Cơ sở", "Centers")}</dt><dd>${T("[Các cơ sở thực hiện chương trình, HCWA xác nhận]", "[Which centers run it, HCWA to confirm]")}</dd></div>
      <div><dt>${T("Nhà tài trợ", "Funded by")}</dt><dd>${p.partners.join(", ")}</dd></div>
    </dl>
    ${textLink(c.url("greenBamboo"), c.tx(work.centers[0].name))}
  </aside>
</div>

<section class="section section--tight" aria-labelledby="tt-results">
  <div class="container">
    <h2 id="tt-results">${T("Kết quả", "Results")}</h2>
    ${figureStrip(c, "thrive-together")}
  </div>
</section>

<section class="section section--tight" aria-labelledby="tt-docs">
  <div class="container">
    <h2 id="tt-docs">${T("Tài liệu", "Documents")}</h2>
    <ul class="report-list">
      ${reportRow(c, { title: T("[Tên báo cáo dự án]", "[Program report title]"), year: T("[năm]", "[year]"), type: T("Báo cáo dự án", "Program report"), docLang: "Tiếng Việt", size: "[KB]" })}
      ${reportRow(c, { title: T("[Tên báo cáo dự án]", "[Program report title]"), year: T("[năm]", "[year]"), type: T("Báo cáo dự án", "Program report"), docLang: "English", size: "[KB]" })}
    </ul>
  </div>
</section>

<section class="section section--tight" aria-labelledby="tt-news">
  <div class="container">
    <h2 id="tt-news">${T("Tin liên quan", "Related news")}</h2>
    <ul class="news-list news-list--row">${news.posts.map((x) => newsItem(c, x))}</ul>
  </div>
</section>
${ctaBand(c, {
  title: T("Đồng hành cùng những dự án như thế này", "Support work like this"),
  actions: html`${button({ href: c.url("donate"), label: c.t("donate"), kind: "primary" })}${button({ href: c.url("partner"), label: T("Hợp tác trong một dự án", "Partner on a program"), kind: "on-blue" })}`,
})}`;
  return { title: c.tx(p.name), description: c.tx(p.summary), body };
}

export { pageHeader };
