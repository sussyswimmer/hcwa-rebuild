import { $, $$, i18n } from "./dom.js";

// Forms: inline errors in the page language, error summary on submit, then success.
export function initForms() {
  // Donate links from a center page carry ?center=<id>; preselect it in "Tell us you donated".
  const pick = $("#d-center");
  const center = new URLSearchParams(location.search).get("center");
  if (pick && center && [...pick.options].some((o) => o.value === center)) pick.value = center;

  $$("[data-form]").forEach((form) => {
    const summary = $("[data-summary]", form);
    const success = $("[data-success]", form);
    const submit = $('button[type="submit"]', form);
    const check = (input) => {
      const box = input.closest("[data-field]");
      const err = $(".field__error", box);
      const bad = !input.checkValidity();
      box.classList.toggle("is-invalid", bad);
      if (bad) input.setAttribute("aria-invalid", "true");
      else input.removeAttribute("aria-invalid");
      err.hidden = !bad;
      $("span", err).textContent = bad ? err.dataset.error : "";
      return !bad;
    };
    const inputs = $$("input, select, textarea", form).filter((el) => el.closest("[data-field]"));
    // Re-check while typing, not on blur: clearing an error on blur shifts the layout under the
    // pointer mid-click, so the next control (e.g. the consent box) misses the click.
    inputs.forEach((el) => ["input", "change"].forEach((ev) => el.addEventListener(ev, () => { if (el.closest(".is-invalid")) check(el); })));
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      success.hidden = true;
      const bad = inputs.filter((el) => !check(el));
      if (bad.length) {
        summary.innerHTML = "";
        const p = Object.assign(document.createElement("p"), { textContent: i18n.e_summary });
        const ul = document.createElement("ul");
        bad.forEach((el) => {
          const a = Object.assign(document.createElement("a"), { href: "#" + el.id, textContent: $(`#${el.id}-error span`, form)?.textContent ?? "" });
          a.addEventListener("click", (ev) => { ev.preventDefault(); el.focus(); });
          const li = document.createElement("li");
          li.append(a);
          ul.append(li);
        });
        summary.append(p, ul);
        summary.hidden = false;
        summary.focus();
        return;
      }
      summary.hidden = true;
      // Prototype: there is no backend. Show the sending state, then the success message.
      submit.disabled = true;
      submit.setAttribute("aria-busy", "true");
      submit.textContent = i18n.f_sending;
      setTimeout(() => {
        form.reset();
        submit.disabled = false;
        submit.removeAttribute("aria-busy");
        submit.textContent = i18n.f_send;
        success.hidden = false;
        success.focus();
      }, 600);
    });
  });
}
