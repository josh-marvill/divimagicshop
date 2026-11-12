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
- Wordmark DIVI**MAGIC**SHOP until there is a logo. Headings in Bodoni Moda (OFL, self-hosted in the theme), body in the system sans.
- Contact page is text only (a form would need another plugin).
- The dealer stays in demo mode: no test order reaches Cafe24.

**Polish + distill pass (2026-11-09)**
- One action per band: the welcome has a single "Browse the shop" button; product cards link as a whole (no per-card buttons).
- Product page: title, price, options, add to cart; description below as "The trick". No SKU (supplier codes stay internal), no attribute or reviews tabs.
- Footer is one line of help links; the header already carries the main navigation.
- Red is reserved for actions, the hero's "delivered." and the curtain edge; prices and links are ink.
- Arrows and the FAQ open/close marker are drawn (CSS mask / bars), not text glyphs.
- Cart and checkout get the same title band; checkout uses a wordmark-only header.

**Shop pass (2026-11-12)** — "it looks like a styled list of products"
- Header: wordmark · product search · links + cart, then a bar with every top-level category
  (the current one underlined in red, sitting on the header's rule on purpose).
- Home (Josh, 2026-11-12): no product grids — every product lives in the shop (~600 of them).
  The home page is content with many ways into the shop: welcome (Browse the shop + a tutorial
  button) → reasons to buy → shop story → "Where are you on stage?"
  (three levels, each linking to the shop) → tutorials → closing black band (shop button + search).
  All home copy is placeholder. The spotlight, product-grid, category-tile and shelf blocks/patterns
  stay available in the editor.
- Shop, category and product-search pages share one layout: filters on the left (categories with
  counts, price ranges, in stock only), results count + sorting, removable filter chips, 3 across,
  12 per page, squared pagination. On phones the filters fold into one "Categories & filters" button.
- Filters are plain links, no extra plugin: WooCommerce's own `min_price`/`max_price`, plus
  `in_stock=1` (mu-plugin). Price ranges are worked out from the catalogue's prices (round steps,
  top range open-ended), so they suit whatever currency the store uses.
- Badges on product cards: Sale (red), Sold out (ink). No eyebrow labels above headings; the
  promise strip uses hairline dividers, not colored side rules.
- The promise-strip wording is placeholder: check it against the real shipping/checkout before launch.

**Distill (critique 2026-11-12, 25/36)**
- The welcome's category buttons are gone: they repeated the header's category bar on the same
  screen and would have shown only 8 of 20+ real categories.
- The red italic accent is the hero's "delivered." only; the story and closing headings are roman.

**Critique follow-through (2026-11-12)**
- Stage rows link "Browse the shop" (they all reach the whole shop until the catalogue has a
  category or tag per level). Promise links go where they say (checkout → Terms, Questions → FAQ).
- Phone menu leads with a red "Shop all"; on wider screens the category bar carries it.
- Welcome art: a drawn fan of five cards (SVG in a Custom HTML block, ink/paper/red, Bodoni
  indices) that opens once on arrival; static with reduced motion; hidden on phones. Replace with
  a photo by swapping the block for an Image.
- Tutorial cards always read as video: 16:9 frame with a red play mark (on ink when a tutorial has
  no featured image). Compact rows on phones.
- Inside black bands the focus ring is paper (red vanished on red buttons) and hovers are white
  underlines rather than red text (red-on-black small text was ~3.7:1).
- The hero's red italic "delivered." stays: the detector flags it as an italic serif display; it is
  the one deliberate accent.
