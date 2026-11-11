import { html } from "../lib/html.mjs";
import { viKeep } from "../lib/vikeep.mjs";

// News hub (PLAN-M4 4.13, 5.2). Stub first so the pages stream can register the route; the opening
// and the archive follow.
export function news(c) {
  const body = html`<div class="day day--news"><section class="container"><h1>${viKeep(c.t("news"))}</h1></section></div>`;
  return { title: c.t("news"), description: c.t("news"), body, bodyClass: "page-news" };
}
