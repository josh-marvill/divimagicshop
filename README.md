# Divimagicshop

The pilot store's WordPress code: a block theme and a small must-use plugin. WordPress core,
WooCommerce, the JL Magic storefront plugin, uploads and the database are **not** in this repo.

| Path | What it is |
|---|---|
| `wp-content/themes/divimagicshop/` | Block theme: `theme.json` (colors, fonts, spacing), `templates/` (one per page type), `parts/` (header, footer), `patterns/` (home sections) |
| `wp-content/mu-plugins/divimagicshop-core.php` + `divimagicshop-core/` | Tutorials and FAQ post types, the shop blocks (category shelves, FAQ list, tutorial video/products, product tutorials), placeholder content |
| `scripts/` | Build a **local** copy in Laragon (refuse to run anywhere but `*.test`) |

Plugins on the site: WooCommerce and JL Magic Storefront. Nothing else.

## Deploying to Cloudways

The Git deploy only adds and overwrites files this repo contains, and never deletes anything
else, so the existing WordPress, uploads and plugins are left alone.

**First time**
1. Take a Cloudways backup of the app.
2. In WordPress: install **WooCommerce**, then upload and activate the **JL Magic Storefront**
   zip (`jl-magic-wp/plugin/jlmagic-storefront.zip`).
3. Cloudways → Application → Deployment via Git: add Cloudways' SSH key to this repo
   (GitHub → Settings → Deploy keys), use `git@github.com:josh-marvill/divimagicshop.git`,
   branch `main`, **deployment path empty** (= `public_html`). Pull.
4. WordPress admin:
   - Appearance → Themes → activate **Divimagicshop**.
   - Settings → Permalinks → **Post name** → Save.
   - Tools → Divimagicshop → **Add placeholder content**.
   - WooCommerce → JL Magic → paste the staging key → Save (it syncs). Keep the dealer in
     **demo mode** in the app.
   - Tools → Divimagicshop → **Add placeholder content** again: links the tutorials to the
     products that just arrived.

**After that:** pull in Cloudways. New tutorials/FAQ/page text are edited in the admin, not here.

> The deploy also puts `README.md`, `docs/` and `scripts/` in `public_html`. They contain no
> secrets (this repo is public) and the scripts refuse to run outside a local `*.test` site.

## Editing the design

Appearance → Editor. Every page type is a template, the header and footer are template parts,
and the home page is five patterns: **Curtain welcome** (with a product spotlight), **Why shop here**,
**Shop by category**, **Just in and on sale**, **Latest tutorials**. The shop,
category and search pages have a filter sidebar (**Shop filters** block) and the header a
**Product search** and **Category bar**.

- Category tiles: the home page shows the first 8 top-level categories in WooCommerce's category
  order (Products → Categories, drag to reorder); change the number in the block's settings.
- Shelves: the **Category shelves** pattern / **Category shelf** block (a row of products from a
  category) are no longer on the home page but can be added to any page.
- Spotlight: star a product (Products → ☆) to put it in the welcome; otherwise the newest one shows.
- Category tiles use the category's image (Products → Categories), else its newest product's.
- Colors and fonts: Styles in the Site Editor, or `theme.json`.
- Changes made in the Site Editor are saved in the database and override the files here. To
  bring such a change into the repo, copy it from the editor (⋮ → Code editor) into the
  matching file, then "Clear customizations" on that template.

## Content

- **Tutorials** (admin menu): write-up in blocks, featured image, and in the "Video and products"
  box a YouTube/Vimeo link and the products used. Each product page lists its tutorials.
- **FAQ**: question = title, answer = content, topic = Topics box, order = Page Attributes → Order.
  All of them show on `/faq/`.
- Pages: About, Contact, Shipping & Returns, Terms, Privacy Policy (placeholder text).

## Local copy (Laragon)

Needs Laragon (MySQL running) and a checkout of `jl-magic-wp` next to this folder (for the
plugin zip).

```bash
cd /c/laragon/www
git clone https://github.com/josh-marvill/divimagicshop.git
cd divimagicshop
JLMAGIC_KEY=jlm_... bash scripts/setup.sh   # key optional; without it, paste one in WooCommerce → JL Magic
```

Then <http://divimagicshop.test> (admin / admin). Laragon → Reload if the URL doesn't open.
`bash scripts/reset.sh` deletes the local database and WordPress files to start over.
