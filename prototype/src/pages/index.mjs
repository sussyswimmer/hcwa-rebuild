import { home } from "./home.mjs";
import { ourWork, center, program } from "./work.mjs";
import { getInvolved, donate, contact, notFound } from "./involve.mjs";
import { volunteer, partner, inKind } from "./involve-more.mjs";
import { about, ourStory, team, partners, policies } from "./about.mjs";
import { impact } from "./impact.mjs";
import { littleRose } from "./little-rose.mjs";
import { news } from "./news.mjs";
import { components } from "./components.mjs";

export const pages = {
  home,
  "our-work": ourWork,
  center,
  program,
  "little-rose": littleRose,
  "get-involved": getInvolved,
  donate,
  volunteer,
  partner,
  "in-kind": inKind,
  contact,
  about,
  "our-story": ourStory,
  team,
  partners,
  policies,
  impact,
  news,
  components,
  "not-found": notFound,
};
