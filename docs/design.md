# Divimagicshop — design (2026-11-09)

**What:** a real magic shop, JL Magic's pilot store, showcasing products synced in by the
jlmagic-storefront plugin. No JL Magic branding anywhere customers can see.

**Decided by Josh**
- Built on Gutenberg (block theme), not Elementor. Minimal plugins: WooCommerce + the storefront plugin.
- Content organized with custom post types; pages made of templates so each section is editable.
- Home page: a welcome band, then one "shelf" of products per category ("shelves" layout).
- Look: black, white and red — "Curtain up": black header and welcome with a red edge, white shelves below.
- Placeholder (lorem ipsum) for About/Contact/policies; generic FAQ and tutorials.
- Hosted on Cloudways, deployed by Josh from Git.
- Plugin auto-updates (GitHub Releases on jl-magic-wp) are the next project, not this one.

**My calls**
- Repo mirrors the site root and tracks only the theme and a must-use plugin; Cloudways deploys to
  `public_html`. Cloudways' Git deploy never deletes untracked files, so the existing install is safe.
- Post types live in a mu-plugin (`dms_tutorial`, `dms_faq` + `dms_faq_topic`), not the theme.
- Tutorials link to products (one meta row per product); product pages list their tutorials.
- Shop blocks are dynamic PHP blocks with a no-build editor script.
- Wordmark DIVI**MAGIC**SHOP until there is a logo. System font stacks (no font downloads).
- Contact page is text only (a form would need another plugin).
- The dealer stays in demo mode: no test order reaches Cafe24.
