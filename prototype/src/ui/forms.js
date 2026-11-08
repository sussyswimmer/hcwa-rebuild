import { $, $$, i18n } from "./dom.js";

// Forms (05-DESIGN D7 "Form field", PLAN P5, P6): inline errors in the page language, an error summary
// that takes focus on submit, then a sent state that replaces the fields. The prototype has no backend:
// nothing leaves the browser. Errors and the sent panel appear at once (D5.1: never animated).
export function initForms() {
  // Donate links from a center page carry ?center=<id>; preselect it in "Tell us you donated".
  const pick = $("#d-center");
  const center = new URLSearchParams(location.search).get("center");
  if (pick && center && [...pick.options].some((o) => o.value === center)) pick.value = center;

  $$("[data-form]").forEach((form) => {
    const summary = $("[data-summary]", form);
    const success = $("[data-success]", form);
    const submit = $('button[type="submit"]', form);
    // One box per question: a text field, a select, the consent box, or a whole radio group.
    const boxes = $$("[data-field]", form).filter((b) => $(".field__error", b));
    const controls = (box) => $$("input, select, textarea", box);
    const check = (box) => {
      const err = $(".field__error", box);
      const bad = controls(box).some((el) => !el.checkValidity());
      box.classList.toggle("is-invalid", bad);
      controls(box).forEach((el) => (bad ? el.setAttribute("aria-invalid", "true") : el.removeAttribute("aria-invalid")));
      err.hidden = !bad;
      $("span", err).textContent = bad ? err.dataset.error : "";
      return !bad;
    };
    // Re-check while typing, not on blur: clearing an error on blur shifts the layout under the
    // pointer mid-click, so the next control (e.g. the consent box) misses the click.
    boxes.forEach((box) => controls(box).forEach((el) => ["input", "change"].forEach((ev) => el.addEventListener(ev, () => { if (box.classList.contains("is-invalid")) check(box); }))));

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const bad = boxes.filter((box) => !check(box));
      if (bad.length) {
        const p = Object.assign(document.createElement("p"), { textContent: i18n.e_summary });
        const ul = document.createElement("ul");
        bad.forEach((box) => {
          const target = controls(box)[0];
          const a = Object.assign(document.createElement("a"), { href: "#" + target.id, textContent: $(".field__error span", box).textContent });
          a.addEventListener("click", (ev) => { ev.preventDefault(); target.focus(); });
          const li = document.createElement("li");
          li.append(a);
          ul.append(li);
        });
        summary.replaceChildren(p, ul);
        summary.hidden = false;
        summary.focus();
        return;
      }
      summary.hidden = true;
      submit.disabled = true;
      submit.setAttribute("aria-busy", "true");
      submit.textContent = i18n.f_sending;
      setTimeout(() => {
        form.reset();
        submit.disabled = false;
        submit.removeAttribute("aria-busy");
        submit.textContent = i18n.f_send;
        form.classList.add("is-sent");
        success.hidden = false;
        success.focus();
      }, 600);
    });

    $("[data-form-again]", form)?.addEventListener("click", () => {
      form.classList.remove("is-sent");
      success.hidden = true;
      $("input:not([type=hidden]), textarea", form)?.focus();
    });
  });
}
