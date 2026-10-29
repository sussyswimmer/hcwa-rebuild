import { html, raw } from "../lib/html.mjs";
import { button, textLink, placeholder, field, icon, ctaBand } from "../partials/components.mjs";
import { pageHeader } from "./work.mjs";

const wayRoute = { donate: "donate", volunteer: "volunteer", partner: "partner", "in-kind": "inKind" };

export function getInvolved(c) {
  const { involve } = c.content;
  const T = (vi, en) => c.tx({ vi, en });
  const intro = { vi: "Bạn có thể ủng hộ, tình nguyện, hợp tác hoặc tặng hiện vật.", en: "You can donate, volunteer, partner with us or give in kind." };
  const body = html`
${pageHeader(c, { trail: [[c.t("getInvolved")]], title: c.t("getInvolved"), intro: c.tx(intro), draftIntro: true })}
<section class="section section--tight" aria-label="${c.tx(c.content.site.helpTitle)}">
  <div class="container">
    <ul class="ways">
      ${involve.ways.map(
        (w) => html`<li class="way">
          ${placeholder(c, { code: w.art, label: w.title, ratio: "1:1", illustration: true }, "way__art")}
          <h2 class="h3"><a href="${c.url(wayRoute[w.id])}">${c.tx(w.title)}</a></h2>
          <p${c.d(w.text)}>${c.tx(w.text)}</p>
        </li>`,
      )}
    </ul>
  </div>
</section>
<section class="section section--sunken" aria-labelledby="faq-title">
  <div class="container split">
    <div class="split__lead">
      <h2 id="faq-title">${T("Câu hỏi thường gặp", "Common questions")}</h2>
      <p data-draft>${T("Không thấy câu trả lời bạn cần? Hãy liên hệ, Hội sẽ trả lời qua email.", "Can't find your answer? Get in touch and HCWA will reply by email.")}</p>
      ${textLink(c.url("contact"), c.t("contactUs"))}
    </div>
    <div class="accordion">
      ${involve.faq.map((f) => html`<details><summary>${c.tx(f.q)}${icon("chevron")}</summary><p data-draft>${c.tx(involve.faqAnswer)}</p></details>`)}
    </div>
  </div>
</section>`;
  return { title: c.t("getInvolved"), description: c.tx(intro), body };
}

// Donate: every payment detail is a placeholder until content/verified/donation.json exists.
export function donate(c) {
  const { involve } = c.content;
  const T = (vi, en) => c.tx({ vi, en });
  const pendingValue = html`<span class="pending-value" data-donation-placeholder>${c.t("pendingFinance")}</span>`;
  const detailRows = (fields) => html`<dl class="pay-details">${fields.map(
    (f) => html`<div class="pay-details__row">
      <dt>${c.tx(f.label)}</dt>
      <dd>${pendingValue}${f.hint ? html`<span class="pay-details__hint">${c.tx(f.hint)}</span>` : ""}</dd>
      ${f.copy ? html`<button class="btn btn--small btn--secondary" type="button" data-copy="" disabled aria-label="${c.t("copy")}: ${c.tx(f.label)}">${icon("copy")}<span>${c.t("copy")}</span></button>` : html`<span></span>`}
    </div>`,
  )}</dl>`;
  const method = (id, title, inner, open = false) => html`<details class="method" id="${id}"${open ? raw(" open") : ""}><summary><span>${title}</span>${icon("chevron")}</summary><div class="method__body">${inner}</div></details>`;
  const intro = { vi: "Dù lớn hay nhỏ, mỗi khoản ủng hộ đều có ý nghĩa.", en: "Small or large, every gift matters." };
  const body = html`
${pageHeader(c, { trail: [[c.t("getInvolved"), c.url("getInvolved")], [c.t("donate")]], title: c.t("donate"), intro: c.tx(intro), draftIntro: true })}
<section class="section section--tight" aria-labelledby="gift-title">
  <div class="container">
    <h2 id="gift-title" class="h3">${T("Khoản ủng hộ của bạn giúp được gì", "What your gift does")}</h2>
    <ul class="gift-list" data-draft>
      ${involve.giftExamples.map((g) => html`<li><span class="gift-list__amount" data-pending>${c.tx(g.amount)}</span><span>${c.tx(g.text)}</span></li>`)}
    </ul>
  </div>
</section>
<section class="section section--tight" aria-labelledby="methods-title">
  <div class="container donate-grid">
    <div>
      <h2 id="methods-title">${T("Cách ủng hộ", "How to give")}</h2>
      <div class="methods">
        ${method(T("trong-nuoc", "in-vietnam"), T("Chuyển khoản trong nước", "Bank transfer in Vietnam"), html`
          <div class="pay">
            ${detailRows(involve.bankFields)}
            ${placeholder(c, { code: "C6", label: { vi: "Mã VietQR", en: "VietQR code" }, ratio: "1:1" }, "pay__qr")}
          </div>`, true)}
        ${method(T("quoc-te", "from-abroad"), T("Ủng hộ từ nước ngoài", "Giving from abroad"), html`
          ${detailRows(involve.abroadFields)}
          <p class="method__alt">${T("Hoặc ủng hộ qua PayPal:", "Or give through PayPal:")} <button class="btn btn--secondary btn--small" type="button" disabled data-donation-placeholder>PayPal</button></p>`)}
        ${method(T("theo-co-so", "to-a-center"), T("Ủng hộ cho một cơ sở", "Give to a specific center"), html`
          <p data-draft>${T("[HCWA quyết định có hiển thị tài khoản riêng của từng cơ sở, ví dụ Mái ấm Hoa Hồng Nhỏ, hay không.]", "[HCWA decides whether each center, for example Little Rose, shows its own account.]")}</p>`)}
      </div>
    </div>
    <aside class="tell-us" aria-labelledby="tell-title">
      <h2 id="tell-title" class="h3">${T("Báo cho Hội biết bạn đã ủng hộ", "Tell us you donated")}</h2>
      <p>${T("Để Hội cảm ơn và gửi biên nhận cho bạn.", "So HCWA can thank you and send a receipt.")}</p>
      <form class="form" data-form novalidate>
        <div class="form__summary" data-summary tabindex="-1" role="alert" hidden></div>
        ${field(c, { id: "d-name", label: c.t("f_name"), required: true, autocomplete: "name", error: c.t("e_name") })}
        ${field(c, { id: "d-email", label: c.t("f_email"), type: "email", required: true, autocomplete: "email", error: c.t("e_email") })}
        ${field(c, { id: "d-amount", label: T("Số tiền và ngày chuyển", "Amount and date sent"), required: true, error: T("Nhập số tiền và ngày chuyển.", "Enter the amount and the date you sent it.") })}
        ${field(c, { id: "d-center", label: T("Ủng hộ cho (không bắt buộc)", "Gift for (optional)"), options: { placeholder: T("Nơi cần nhất", "Where it's needed most"), items: c.content.work.centers.map((x) => [x.id, c.tx(x.name)]) }, error: "" })}
        <button class="btn btn--primary" type="submit">${c.t("f_send")}</button>
        <div class="form__success" data-success tabindex="-1" role="status" hidden>${icon("check")}<div><p>${T("Đã gửi. Cảm ơn bạn đã ủng hộ!", "Sent. Thank you for your gift!")}</p>${c.prod ? "" : html`<p class="form__note">${c.t("f_prototypeSent")}</p>`}</div></div>
      </form>
    </aside>
  </div>
</section>`;
  return { title: c.t("donate"), description: c.tx(intro), body };
}

export function contact(c) {
  const { site, work } = c.content;
  const T = (vi, en) => c.tx({ vi, en });
  const topics = ["donate", "partner", "volunteer", "media", "other"].map((k) => [k, c.t("topic_" + k)]);
  const intro = { vi: "Gửi lời nhắn cho Hội, hoặc gọi điện và email trực tiếp.", en: "Send HCWA a message, or call or email us directly." };
  const body = html`
${pageHeader(c, { trail: [[c.t("contact")]], title: c.t("contact"), intro: c.tx(intro), draftIntro: true })}
<section class="section section--tight">
  <div class="container contact-grid">
    <form class="form" data-form novalidate aria-labelledby="form-title">
      <h2 id="form-title" class="h3">${T("Gửi lời nhắn", "Send a message")}</h2>
      <div class="form__summary" data-summary tabindex="-1" role="alert" hidden></div>
      ${field(c, { id: "name", label: c.t("f_name"), required: true, autocomplete: "name", error: c.t("e_name") })}
      ${field(c, { id: "email", label: c.t("f_email"), type: "email", required: true, autocomplete: "email", help: c.t("f_emailHelp"), error: c.t("e_email") })}
      ${field(c, { id: "phone", label: c.t("f_phone"), type: "tel", autocomplete: "tel", error: "" })}
      ${field(c, { id: "topic", label: c.t("f_topic"), required: true, options: { placeholder: c.t("f_topicChoose"), items: topics }, error: c.t("e_topic") })}
      ${field(c, { id: "message", label: c.t("f_message"), required: true, rows: 6, error: c.t("e_message") })}
      <div class="field field--check" data-field>
        <input type="checkbox" id="consent" name="consent" required aria-describedby="consent-error">
        <label for="consent"><span data-draft>${c.t("f_consent")}</span> <a href="${c.url("privacy")}">${c.t("privacy")}</a></label>
        <p class="field__error" id="consent-error" data-error="${c.t("e_consent")}" hidden>${icon("alert")}<span></span></p>
      </div>
      <button class="btn btn--primary" type="submit">${c.t("f_send")}</button>
      <div class="form__success" data-success tabindex="-1" role="status" hidden>${icon("check")}<div><p>${c.t("f_sent")}</p>${c.prod ? "" : html`<p class="form__note">${c.t("f_prototypeSent")}</p>`}</div></div>
    </form>
    <aside class="offices" aria-labelledby="offices-title">
      <h2 id="offices-title" class="h3">${T("Văn phòng và cơ sở", "Office and centers")}</h2>
      <article class="office" data-draft>
        <h3 class="h4">${T("Văn phòng Hội", "HCWA office")}</h3>
        <ul class="contact-lines">
          <li>${icon("pin")}<span>${c.tx(site.address)}</span></li>
          <li>${icon("phone")}<a href="tel:${site.phone.tel}">${site.phone.display}</a></li>
          <li>${icon("mail")}<a href="mailto:${site.email.general}">${site.email.general}</a></li>
        </ul>
        <a class="map-link" href="${site.mapUrl}">${placeholder(c, { code: "C7", label: { vi: "Bản đồ tĩnh", en: "Static map" }, ratio: "4:3" })}<span class="text-link"><span>${c.t("openMap")}</span>${icon("external")}</span></a>
      </article>
      ${work.centers.map((x) => html`<article class="office office--unit" style="--unit:var(--color-unit-${x.accent})" data-draft>
        <h3 class="h4">${c.tx(x.name)}</h3>
        <p>${T("[Địa chỉ và liên hệ đã được duyệt]", "[Approved address and contact]")}</p>
      </article>`)}
      <p class="offices__chat" data-draft>${T("Nhắn tin qua", "Message us on")} <a href="#">Zalo</a> · <a href="${site.facebook}">Facebook Messenger</a></p>
    </aside>
  </div>
</section>`;
  return { title: c.t("contact"), description: c.tx(intro), body };
}

export function notFound(c) {
  const T = (vi, en) => c.tx({ vi, en });
  const body = html`
<section class="section not-found">
  <div class="container not-found__grid">
    <div>
      <h1>${c.t("nf_title")}</h1>
      <p class="lead">${c.t("nf_text")}</p>
      ${c.prod ? "" : html`<p class="note" data-draft>${icon("info")}<span>${c.t("notInPrototype")}</span></p>`}
      <form class="search" action="${c.url("home")}" method="get" role="search">
        <label for="s">${c.t("nf_search")}</label>
        <div class="search__row"><input type="search" id="s" name="s"><button class="btn btn--secondary" type="submit">${icon("search")}<span>${c.t("nf_searchBtn")}</span></button></div>
      </form>
      <ul class="link-list">
        <li>${textLink(c.url("home"), c.t("home"))}</li>
        <li>${textLink(c.url("ourWork"), c.t("ourWork"))}</li>
        <li>${textLink(c.url("contact"), c.t("contact"))}</li>
      </ul>
    </div>
    ${placeholder(c, { code: "C8", label: { vi: "Minh họa trang 404", en: "404 illustration" }, ratio: "1:1", illustration: true }, "not-found__art")}
  </div>
</section>`;
  return { title: c.t("nf_title"), description: c.t("nf_text"), body };
}

