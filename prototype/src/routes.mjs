// URL pairs from 04-SITEMAP. Every route the menus, footer and pages link to is built (`page` names its
// module in pages/index.mjs). A key without `page` is an anchor inside a built page (reports, bakery,
// safeguarding, privacy): the target page renders that id in both languages and in PROD. The 404 page
// is only for mistyped URLs; test/links.test.mjs and npm run check:links fail on any internal link that
// lands anywhere else.
// unit / program: which work.json center or program a shared template renders (PLAN-M4 7.3).
export const routes = {
  home: { vi: "/", en: "/en/", page: "home" },
  ourWork: { vi: "/hoat-dong/", en: "/en/our-work/", page: "our-work" },
  greenBamboo: { vi: "/hoat-dong/co-so/tre-xanh/", en: "/en/our-work/centers/green-bamboo/", page: "center", unit: "green-bamboo" },
  // PLAN-M4 decision 4: the EN slug matches 04-SITEMAP and wp/CONTRACT.md (Polylang free cannot reuse "cau-han").
  cauHan: { vi: "/hoat-dong/co-so/cau-han/", en: "/en/our-work/centers/cau-han-project/", page: "center", unit: "cau-han" },
  // Little Rose has its own template: words and an abstract illustration, no still, no door, no place.
  littleRose: { vi: "/hoat-dong/co-so/hoa-hong-nho/", en: "/en/our-work/centers/little-rose/", page: "little-rose" },
  thriveTogether: { vi: "/hoat-dong/du-an/cung-vuon-len/", en: "/en/our-work/programs/thrive-together/", page: "program", program: "thrive-together" },
  charityClass: { vi: "/hoat-dong/du-an/lop-hoc-tinh-thuong/", en: "/en/our-work/programs/charity-class/", page: "program", program: "charity-class" },
  disabilityPilot: { vi: "/hoat-dong/du-an/quyen-tre-em-khuyet-tat/", en: "/en/our-work/programs/disability-rights-pilot/", page: "program", program: "disability-pilot" },
  getInvolved: { vi: "/chung-tay/", en: "/en/get-involved/", page: "get-involved" },
  donate: { vi: "/chung-tay/ung-ho/", en: "/en/get-involved/donate/", page: "donate" },
  volunteer: { vi: "/chung-tay/tinh-nguyen/", en: "/en/get-involved/volunteer/", page: "volunteer" },
  partner: { vi: "/chung-tay/doi-tac-doanh-nghiep/", en: "/en/get-involved/partner/", page: "partner" },
  inKind: { vi: "/chung-tay/hien-vat/", en: "/en/get-involved/in-kind/", page: "in-kind" },
  contact: { vi: "/lien-he/", en: "/en/contact/", page: "contact" },
  about: { vi: "/ve-chung-toi/", en: "/en/about/", page: "about" },
  ourStory: { vi: "/ve-chung-toi/cau-chuyen/", en: "/en/about/our-story/", page: "our-story" },
  team: { vi: "/ve-chung-toi/doi-ngu/", en: "/en/about/team/", page: "team" },
  partners: { vi: "/ve-chung-toi/doi-tac/", en: "/en/about/partners/", page: "partners" },
  policies: { vi: "/ve-chung-toi/chinh-sach/", en: "/en/about/policies/", page: "policies" },
  impact: { vi: "/tac-dong/", en: "/en/impact/", page: "impact" },
  // The news hub belongs to the openings stream (src/pages/news.mjs).
  news: { vi: "/tin-tuc/", en: "/en/news/", page: "news" },
  components: { vi: "/components/", en: "/en/components/", page: "components", prototypeOnly: true },
  notFound: { vi: "/404.html", en: "/en/404.html", page: "not-found" },

  // Anchors inside built pages (the ids live in about.json, impact.mjs and involve-more.mjs).
  reports: { vi: "/tac-dong/#bao-cao", en: "/en/impact/#reports" },
  bakery: { vi: "/chung-tay/hien-vat/#tiem-banh", en: "/en/get-involved/in-kind/#bakery" },
  safeguarding: { vi: "/ve-chung-toi/chinh-sach/#bao-ve-tre-em", en: "/en/about/policies/#safeguarding" },
  privacy: { vi: "/ve-chung-toi/chinh-sach/#quyen-rieng-tu", en: "/en/about/policies/#privacy" },
};

export const pageRoutes = Object.entries(routes).filter(([, r]) => r.page);

// Which hub a child page sits under (the header marks the hub aria-current="true").
export const PARENT = {
  greenBamboo: "ourWork", cauHan: "ourWork", littleRose: "ourWork", thriveTogether: "ourWork", charityClass: "ourWork", disabilityPilot: "ourWork",
  donate: "getInvolved", volunteer: "getInvolved", partner: "getInvolved", inKind: "getInvolved",
  ourStory: "about", team: "about", partners: "about", policies: "about",
};
