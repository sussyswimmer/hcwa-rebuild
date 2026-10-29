import { html, raw } from "../lib/html.mjs";
import { button, textLink, placeholder, figure, pendingChip, centerCard, programRow, storyCard, reportRow, breadcrumb, langNotice, field, statusPill, newsItem, icon } from "../partials/components.mjs";
import { pageHeader } from "./work.mjs";

// Every component from 05-DESIGN with its states, for HCWA and the agency (08 task 1.3).
export function components(c) {
  const { work, news, site } = c.content;
  const T = (vi, en) => c.tx({ vi, en });
  const block = (id, title, inner) => html`<section class="kit" aria-labelledby="kit-${id}"><h2 id="kit-${id}" class="h3">${title}</h2>${inner}</section>`;
  const testLine = "Hội Bảo trợ Trẻ em Thành phố Hồ Chí Minh: những đứa trẻ ở Quận 7 được học, được bảo vệ.";
  const sizes = [["h1", "--fs-h1"], ["h2", "--fs-h2"], ["h3", "--fs-h3"], ["h4", "--fs-h4"], ["lead", "--fs-lg", T("đoạn dẫn", "lead")], ["body", "--fs-base", T("nội dung", "body")], ["meta", "--fs-meta", T("chú thích", "meta")]];
  const swatches = [
    ["paper", "#FBF8F3"], ["surface", "#FFFFFF"], ["sunken", "#F3EEE6"], ["ink", "#1C2333"], ["ink-2", "#4A5265"], ["ink-3", "#5F6679"],
    ["blue-900", "#23236A"], ["blue-700", "#313188"], ["blue-100", "#ECECF7"], ["sun-400", "#FFC42E"], ["sun-100", "#FFF4D6"],
    ["unit-green-bamboo", "#008E3A"], ["unit-little-rose", "#DC4C98"], ["unit-cau-han", "#417CBD"], ["ok", "#2C7A4C"], ["warn", "#8A5A00"], ["error", "#B42318"],
  ];
  const sample = { verified: true, value: 12345, suffix: "+", label: { vi: "ví dụ định dạng số, không phải số liệu thật", en: "number format example, not real data" }, year: null };
  const body = html`
${pageHeader(c, { trail: [[T("Thành phần giao diện", "Components")]], title: T("Thành phần giao diện", "Components"), intro: T("Mọi thành phần và trạng thái dùng trong bản mẫu.", "Every component and state used in the prototype.") })}
<div class="container kit-wrap">

${block("type", T("Chữ: dòng kiểm tra tiếng Việt", "Type: Vietnamese test line"), html`
  <div class="type-specimen">${sizes.map(([name, v, label]) => html`<div class="type-specimen__row"><span class="type-specimen__tag">${label ?? name}</span><p lang="vi" class="${label ? "" : "display"}" style="font-size:var(${v})">${testLine}</p></div>`)}
  <div class="type-specimen__row"><span class="type-specimen__tag">${T("nội dung 500 / 600", "body 500 / 600")}</span><p lang="vi"><span style="font-weight:500">${testLine}</span><br><span style="font-weight:600">${testLine}</span></p></div>
  <div class="type-specimen__row"><span class="type-specimen__tag">${T("số liệu", "stat")}</span><p class="figure__value">0123456789 · 12.345 · 12,345</p></div></div>`)}

${block("color", T("Màu (đã kiểm tra tương phản)", "Color (contrast checked)"), html`
  <ul class="swatches">${swatches.map(([n, hex]) => html`<li><span class="swatch" style="background:var(--color-${n})"></span><code>--color-${n}</code><span>${hex}</span></li>`)}</ul>`)}

${block("buttons", T("Nút", "Buttons"), html`
  <div class="kit-row">
    ${button({ label: T("Chính", "Primary") })}
    ${button({ label: T("Khi trỏ chuột", "Hover"), cls: "is-hover" })}
    ${button({ label: T("Khi được chọn", "Focus"), cls: "is-focus" })}
    ${button({ label: T("Khi nhấn", "Active"), cls: "is-active" })}
    ${button({ label: T("Không dùng được", "Disabled"), attrs: "disabled" })}
    <button class="btn btn--primary" type="button" aria-busy="true" aria-disabled="true"><span class="spinner" aria-hidden="true"></span>${c.t("f_sending")}</button>
  </div>
  <div class="kit-row">
    ${button({ label: T("Phụ", "Secondary"), kind: "secondary" })}
    ${button({ label: T("Khi trỏ chuột", "Hover"), kind: "secondary", cls: "is-hover" })}
    ${button({ label: T("Khi được chọn", "Focus"), kind: "secondary", cls: "is-focus" })}
    ${button({ label: T("Không dùng được", "Disabled"), kind: "secondary", attrs: "disabled" })}
    ${textLink("#", c.t("readMore"))}
  </div>
  <div class="kit-row kit-row--blue">
    ${button({ label: c.t("contactUs"), kind: "on-blue" })}
    ${button({ label: T("Khi được chọn", "Focus"), kind: "on-blue", cls: "is-focus" })}
  </div>`)}

${block("figures", T("Số liệu tác động", "Impact figures"), html`
  <div class="figures">
    ${figure(c, sample)}
    ${figure(c, c.content.impact[0])}
  </div>
  <p class="kit-note">${T("Trên trang chính thức, số liệu chưa xác minh không hiển thị. Không có hiệu ứng đếm số.", "On production, unverified figures do not render. Numbers never count up.")}</p>`)}

${block("status", T("Trạng thái và nhãn", "Status and labels"), html`
  <div class="kit-row">${statusPill(c, "active")}${statusPill(c, "completed")}${statusPill(c, "planned")}${pendingChip(c)}<span class="badge">PDF · Tiếng Việt</span><span class="label">${c.t("cat_story")}</span></div>`)}

${block("center", T("Thẻ cơ sở", "Center card"), html`<div class="center-grid">${work.centers.map((x) => centerCard(c, x))}</div>`)}

${block("program", T("Dòng dự án", "Program row"), html`<ul class="program-list">${work.programs.map((p) => programRow(c, p))}</ul>`)}

${block("story", T("Câu chuyện và tin tức", "Story and news"), html`<div class="latest">${storyCard(c, news.featuredStory, { featured: true })}<ul class="news-list">${news.posts.map((p) => newsItem(c, p))}</ul></div>`)}

${block("report", T("Dòng báo cáo", "Report row"), html`<ul class="report-list">${reportRow(c, { title: T("[Báo cáo thường niên]", "[Annual report]"), year: T("[năm]", "[year]"), type: c.t("annualReports"), docLang: "Tiếng Việt", size: "[KB]" })}</ul>`)}

${block("placeholder", T("Ô ảnh chờ (mã theo 10-ASSET-REQUEST)", "Image slots (codes from 10-ASSET-REQUEST)"), html`<div class="kit-grid">
  ${placeholder(c, site.hero.image)}${placeholder(c, work.centers[1].image)}${placeholder(c, { code: "B3", label: { vi: "Ảnh hoạt động", en: "Activity photo" }, ratio: "3:2" })}</div>`)}

${block("nav", T("Điều hướng", "Navigation"), html`
  ${breadcrumb(c, [[c.t("ourWork"), "#"], [c.tx(work.centers[0].name)]])}
  <div class="announce" data-announce>
    <div class="announce__inner"><p data-draft>${T("[Thông báo ngắn, ví dụ sự kiện sắp tới hoặc lời kêu gọi khẩn]", "[Short announcement, for example an upcoming event or urgent appeal]")} <a href="#">${c.t("readMore")}</a></p>
    <button class="icon-btn icon-btn--sm" type="button" data-dismiss-announce aria-label="${c.t("hideAnnouncement")}">${icon("close")}</button></div>
  </div>
  ${langNotice(c)}`)}

${block("forms", T("Ô nhập liệu", "Form fields"), html`
  <div class="kit-grid kit-grid--forms">
    ${field(c, { id: "k-name", label: c.t("f_name"), autocomplete: "off", error: c.t("e_name") })}
    ${field(c, { id: "k-email", label: c.t("f_email"), type: "email", help: c.t("f_emailHelp"), autocomplete: "off", error: c.t("e_email") })}
    <div class="field is-invalid" data-field>
      <label for="k-err">${c.t("f_email")}</label>
      <input id="k-err" type="email" value="ten@" aria-invalid="true" aria-describedby="k-err-error">
      <p class="field__error" id="k-err-error">${icon("alert")}<span>${c.t("e_email")}</span></p>
    </div>
    <div class="field" data-field><label for="k-dis">${c.t("f_phone")}</label><input id="k-dis" disabled></div>
    ${field(c, { id: "k-topic", label: c.t("f_topic"), options: { placeholder: c.t("f_topicChoose"), items: [["a", c.t("topic_donate")]] }, error: "" })}
    <div class="field field--check"><input type="checkbox" id="k-consent"><label for="k-consent"><span data-draft>${c.t("f_consent")}</span></label></div>
  </div>
  <div class="kit-row">
    <dl class="pay-details"><div class="pay-details__row"><dt>${T("Ví dụ nút sao chép", "Copy button example")}</dt><dd><code>UNGHO 0900000000</code></dd>
    <button class="btn btn--small btn--secondary" type="button" data-copy="UNGHO 0900000000">${icon("copy")}<span>${c.t("copy")}</span></button></div></dl>
  </div>
  <p class="sr-only" aria-live="polite" data-live></p>`)}

${block("accordion", T("Mở rộng / thu gọn", "Accordion"), html`<div class="accordion">${c.content.involve.faq.slice(0, 2).map((f) => html`<details><summary>${c.tx(f.q)}${icon("chevron")}</summary><p data-draft>${c.tx(c.content.involve.faqAnswer)}</p></details>`)}</div>`)}

</div>`;
  return { title: T("Thành phần giao diện", "Components"), description: T("Thành phần giao diện của bản mẫu HCWA.", "HCWA prototype components."), body };
}
