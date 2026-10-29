// URL pairs from 04-SITEMAP. `page` marks routes the prototype builds; the rest are
// linked (menus, footer) and land on the 404 page with a "not in the prototype" note.
export const routes = {
  home: { vi: "/", en: "/en/", page: "home" },
  ourWork: { vi: "/hoat-dong/", en: "/en/our-work/", page: "our-work" },
  greenBamboo: { vi: "/hoat-dong/co-so/tre-xanh/", en: "/en/our-work/centers/green-bamboo/", page: "center" },
  thriveTogether: { vi: "/hoat-dong/du-an/cung-vuon-len/", en: "/en/our-work/programs/thrive-together/", page: "program" },
  getInvolved: { vi: "/chung-tay/", en: "/en/get-involved/", page: "get-involved" },
  donate: { vi: "/chung-tay/ung-ho/", en: "/en/get-involved/donate/", page: "donate" },
  contact: { vi: "/lien-he/", en: "/en/contact/", page: "contact" },
  components: { vi: "/components/", en: "/en/components/", page: "components", prototypeOnly: true },
  notFound: { vi: "/404.html", en: "/en/404.html", page: "not-found" },

  about: { vi: "/ve-chung-toi/", en: "/en/about/" },
  ourStory: { vi: "/ve-chung-toi/cau-chuyen/", en: "/en/about/our-story/" },
  team: { vi: "/ve-chung-toi/doi-ngu/", en: "/en/about/team/" },
  partners: { vi: "/ve-chung-toi/doi-tac/", en: "/en/about/partners/" },
  policies: { vi: "/ve-chung-toi/chinh-sach/", en: "/en/about/policies/" },
  impact: { vi: "/tac-dong/", en: "/en/impact/" },
  reports: { vi: "/tac-dong/#bao-cao", en: "/en/impact/#reports" },
  news: { vi: "/tin-tuc/", en: "/en/news/" },
  volunteer: { vi: "/chung-tay/tinh-nguyen/", en: "/en/get-involved/volunteer/" },
  partner: { vi: "/chung-tay/doi-tac-doanh-nghiep/", en: "/en/get-involved/partner/" },
  inKind: { vi: "/chung-tay/hien-vat/", en: "/en/get-involved/in-kind/" },
  bakery: { vi: "/chung-tay/hien-vat/#tiem-banh", en: "/en/get-involved/in-kind/#bakery" },
  safeguarding: { vi: "/ve-chung-toi/chinh-sach/#bao-ve-tre-em", en: "/en/about/policies/#safeguarding" },
  privacy: { vi: "/ve-chung-toi/chinh-sach/#quyen-rieng-tu", en: "/en/about/policies/#privacy" },
};

export const pageRoutes = Object.entries(routes).filter(([, r]) => r.page);
