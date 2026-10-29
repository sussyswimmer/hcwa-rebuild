import { home } from "./home.mjs";
import { ourWork, center, program } from "./work.mjs";
import { getInvolved, donate, contact, notFound } from "./involve.mjs";
import { components } from "./components.mjs";

export const pages = {
  home,
  "our-work": ourWork,
  center,
  program,
  "get-involved": getInvolved,
  donate,
  contact,
  components,
  "not-found": notFound,
};
