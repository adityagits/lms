/* Full-page templates: ordered lists of component ids. Add your own. */
(window.WB = window.WB || {}).templates = [
  { id: "landing", name: "Landing page", desc: "Hero, features, proof, pricing, CTA",
    blocks: ["announce", "navbar", "hero-split", "logos", "features-grid", "stats", "testimonial-carousel", "pricing-toggle", "faq", "cta", "footer"] },
  { id: "business", name: "Business / About", desc: "Story, team, timeline, contact",
    blocks: ["navbar", "hero-center", "image-text", "timeline", "team", "contact", "map", "footer"] },
  { id: "portfolio", name: "Portfolio", desc: "Gallery, case studies, contact",
    blocks: ["navbar", "hero-center", "gallery", "case-studies", "social", "footer-simple"] },
  { id: "blog", name: "Blog home", desc: "Featured post, article list, newsletter",
    blocks: ["navbar", "post-card", "post-list", "newsletter", "footer"] },
  { id: "shop", name: "Online shop", desc: "Categories, products, reviews",
    blocks: ["announce", "navbar", "hero-image", "categories", "product-grid", "rating", "newsletter", "footer"] },
  { id: "pricing", name: "Pricing page", desc: "Plans, comparison, FAQ",
    blocks: ["navbar", "pricing-toggle", "compare-table", "faq", "cta", "footer"] },
];
