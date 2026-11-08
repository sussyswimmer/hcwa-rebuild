import { initMenuSheet } from "./menu-sheet.js";
import { initFooter } from "./footer.js";
import { initFilters } from "./filters.js";
import { initLightbox } from "./lightbox.js";
import { initCopy } from "./copy.js";
import { initForms } from "./forms.js";
import { initTabs } from "./tabs.js";
import { initKit } from "./kit.js";
import { initDraftToggle } from "./draft-toggle.js";
import { initReveals } from "../motion/reveals.js";
import { initTransitions } from "../motion/transitions.js";

// Everything the first screen does not need; main.js runs it after the first frame. Owned by the page
// stream: new UI modules register here, so main.js stays the 3D/motion stream's file.
export function initUiRest() {
  initMenuSheet();
  initFooter();
  initFilters();
  initLightbox();
  initCopy();
  initTabs();
  initKit();
  initForms();
  initReveals();
  initDraftToggle();
  initTransitions();
}
