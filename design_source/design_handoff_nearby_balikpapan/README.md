# Handoff: NearBy Balikpapan — Local UMKM (Small-Business) Directory

## Overview
NearBy Balikpapan is a public web directory for discovering local small businesses (UMKM) in
Balikpapan, Indonesia — kuliner (food), penginapan (lodging), fashion, oleh-oleh (souvenirs),
and jasa (services). It has three audiences:

- **Guests / users** — browse, search, filter, favorite, view business detail pages, and leave
  ratings & reviews.
- **Business owners (owner)** — a dashboard to manage their listings, see performance, respond to
  reviews, and edit their profile.
- **Administrators (admin)** — a dashboard to verify new listing submissions, manage all UMKM and
  users, view reports, and manage a trash/restore flow.

All UI copy is in **Bahasa Indonesia**. Keep it in Indonesian when implementing.

## About the Design Files
The files in this bundle are **design references created in HTML** — a working prototype showing
the intended look, layout, and behavior. They are **not production code to copy directly.**

The task is to **recreate these designs in the target codebase's environment** (e.g. React, Vue,
Laravel + Blade, Next.js, etc.) using its established patterns, component library, and routing.
If no codebase exists yet, choose an appropriate stack (a React SPA or a server-rendered framework
both fit this app well) and implement the designs there.

The prototype is a single-file component using a small custom runtime (`support.js`) with a
template + a `Component` logic class. **Do not port that runtime.** Read it only to understand
state, data, and behavior; rebuild the UI natively.

## Fidelity
**High-fidelity (hifi).** Final colors, typography, spacing, radii, shadows, and interactions are
all specified and intentional. Recreate the UI to match, using the codebase's own primitives.
Exact tokens are listed in **Design Tokens** below.

## Information Architecture (routes / views)
The prototype is a single component that swaps views based on a `page` state value, with deep-link
support via URL hash (`#beranda`, `#daftar`, `#detail/3`, `#dashboard/admin`, `#dashboard/owner`,
`#login`, etc.). In a real app, map each of these to a route.

Public site (shares a sticky top nav + footer chrome):
- `beranda` — Home / landing
- `daftar` — Directory listing (browse all UMKM with filters)
- `detail/:id` — Business detail page
- `tentang` — About
- `panduan` — Guide: how to register a business (video, steps, required documents, FAQ)
- `favorit` — Saved favorites (guests see a sign-in prompt)
- `privacy` — Privacy policy (legal)
- `terms` — Terms & conditions (legal)
- `akun` — Account settings (profile, security, permissions, history, delete account)

Auth (full-screen split layout, no site chrome):
- `login` — Sign in (`Masuk`)
- `register` — Sign up (`Daftar`) with a role picker: user vs. business owner

Dashboard (`dashboard`, own topbar + sidebar, no public chrome). Sidebar tab depends on role:
- **Admin tabs:** `verif` (verification queue), `all` (all UMKM), `users` (users), `profile`
  (edit admin profile), `trash` (trash/restore), `report` (reports & stats)
- **Owner tabs:** `ringkasan` (summary/performance), `myumkm` (my UMKM), `trash`, `reviews`
  (ulasan), `profile`

Modals (rendered over the dashboard): edit-UMKM form, UMKM action menu, manage-user, submission
detail (verification review), reply-to-review.

## Global Chrome

### Top navigation (public site)
- Sticky, `height: 70px`, `max-width: 1200px` centered, `padding: 0 24px`.
- Background `rgba(250,247,241,.86)` with `backdrop-filter: saturate(160%) blur(12px)`;
  bottom border `1px solid #ECE5D8`.
- **Logo lockup** (left, clickable → home): 42×42 logo image + wordmark. Wordmark: "Near" in
  `#16324B` + "By" in `#2C5EAD`, weight 800, 18px, `letter-spacing: -.02em`; sub-label
  "BALIKPAPAN" 10px, weight 700, `letter-spacing: .16em`, uppercase, color `#C98A2E`.
- **Nav links:** Beranda, Daftar UMKM, Tentang, Panduan (+ Dashboard when owner/admin). Inactive
  link: `#4B5563`, weight 600, 14.5px, `padding: 9px 14px`, `border-radius: 10px`. Active link:
  color `#2C5EAD`, background `#EAF1FB`. Hover: `background: #F1ECE0`, `translateY(-1px)`.
- **Right cluster:** favorites heart button (42×42, `border-radius: 12px`, border `1px solid
  #E4DCCC`, `#fff` bg, heart `#C0472F`) with a count badge (`#C0472F` pill, white text) when > 0.
  - Guest: "Masuk" (outline button) + "Daftar" (solid `#2C5EAD`, white, shadow
    `0 6px 16px rgba(44,94,173,.28)`), both `border-radius: 11px`, `padding: 10px 18px`, weight 700.
  - Authed: avatar chip (32px circle, `#16324B` bg, white initial) + name + role label, in a
    pill (`border: 1px solid #ECE5D8`, `border-radius: 999px`), opening a dropdown menu
    (Pengaturan ⚙, Keluar in `#C0472F`).
- **Mobile (≤860px):** nav + auth buttons hide; a hamburger (☰) button shows and toggles a stacked
  mobile menu with the same links and full-width auth buttons.

### Footer
Multi-column footer on public pages (brand, link columns, legal). Collapses to 2 columns ≤860px,
1 column ≤560px. (See the prototype footer block for exact links/copy.)

## Screens / Views (detail)

### Home (`beranda`)
- **Hero:** dark diagonal gradient `linear-gradient(160deg,#16324B 0%,#1E4372 55%,#2C5EAD 100%)`
  with two soft radial glow blobs (gold top-right `rgba(214,162,74,.35)`, teal bottom-left
  `rgba(62,142,130,.4)`). Two-column grid (`data-r="hero"`) that collapses to 1 column ≤860px.
  Contains heading (`hero-h1`, drops to 33px on mobile), subcopy, a search input, and category
  quick-filter chips. Left copy / right visual.
- **Kategori section:** heading row + a grid of category cards (`grid-cat`: reflows 3-up ≤860px,
  2-up ≤560px). Categories and their accent colors are in Design Tokens.
- **UMKM Unggulan (featured):** section heading + a responsive card grid of business cards
  (`grid-cards`: 2-up ≤860px, 1-up ≤560px).
- **Wilayah (by location):** location cards grid (`grid-region`).
- **CTA Owner:** rounded banner (`border-radius: 26px`) with gradient
  `linear-gradient(120deg,#3E8E82,#2C5EAD)`, decorative circle, headline + button — prompts owners
  to list their business.

### Business card (reused across home & directory)
- White card, `border: 1px solid #EFE9DC`, rounded, subtle shadow; hover lift.
- Image area at top (striped placeholder in prototype — see Assets), with an absolutely-positioned
  **favorite heart button** top-right: 36px circle, `box-shadow: 0 4px 12px rgba(9,24,40,.18)`.
  Unsaved = white bg / `#C0472F` heart (♡); saved = `#C0472F` bg / white heart (♥).
- Body: category pill (uses category `soft` bg + `accent` text), name (weight ~800), short tag
  line, and a row with star rating (e.g. ★ 4.8), review count, and price label (e.g. "Rp25–75rb").
- Clicking the card opens the detail page; the heart toggles favorite (guests are sent to login).

### Directory (`daftar`)
- Header + filter controls: search query, category chips, and location filter. Results are
  filtered by category, location, and text query, then **sorted by rating descending**.
- Responsive card grid of the filtered results.

### Detail (`detail/:id`)
- "← Kembali ke daftar" back link.
- Two-column split (`data-r="split"`, collapses ≤860px): main content + sidebar.
- Media gallery (`data-r="gallery"`), business name, category, rating, address, hours, phone,
  Instagram handle, and a menu/product/room list (`items` with name + price).
- **Reviews:** list of existing reviews (avatar initial, name, star string, date, text).
- **Add review:** authed users get a star picker (1–5, default 5), a textarea, optional media, and
  a "Kirim ulasan" submit button (`#2C5EAD`). Guests see "Masuk untuk memberi rating dan komentar."

### About (`tentang`)
Dark gradient hero with centered logo (78px, white rounded bg) + intro, then a 2-column card grid
(`grid2`, 1-up ≤560px) of about/mission content.

### Guide (`panduan`)
Dark gradient hero with a pill badge; then: a video-panduan block, a step-by-step section, a
"Berkas yang perlu disiapkan" (required documents) card, an FAQ accordion, and a closing CTA banner
(same teal→blue gradient as the owner CTA).

### Favorites (`favorit`)
Grid of saved businesses. Guests instead see an empty-state card (large `#C0472F` heart, prompt).

### Auth: Login (`login`) & Register (`register`)
- Full-screen `grid-template-columns: 1fr 1fr` (`data-r="authsplit"`, stacks to 1 column ≤860px).
- **Brand panel** (hidden ≤860px, `data-r="authbrand"`): gradient
  (`linear-gradient(165deg,#3E8E82,#16324B)` on register), logo, tagline.
- **Form panel:** email + password fields; login has a "forgot password" flow; register has a
  **role picker** (user vs. owner) styled via `roleStyle` — active = `#EEF4FC` bg + `2px solid
  #2C5EAD` border + shadow; inactive = white + `2px solid #EAE2D2`.

### Account settings (`akun`)
Dark gradient hero + a settings shell with sub-tabs: **Profil** (edit name/email/phone + avatar),
**Keamanan** (security: 2FA toggle, login alerts), **Izin & Privasi** (permission toggles: email
notif, push notif, location, promo, data-share), **History** (two tabs: komentar / kunjungan —
comment history is editable), and **Hapus Akun** (danger zone, `#FCF1EE` bg / `#EFC8BD` border,
requires typed confirmation).

### Dashboard (`dashboard`)
- Full-height `#F1EDE4` bg, own topbar + left sidebar (role-based tabs listed in IA above).
- **Admin – Verifikasi:** "Antrian Verifikasi UMKM" — queue of submissions to approve/reject;
  clicking one opens the submission-detail modal.
- **Admin – Semua UMKM / Pengguna:** data tables (`data-r="tablewrap"` scrolls horizontally on
  mobile; rows keep `min-width: 660px`).
- **Admin – Laporan:** a 4-up stat grid (`grid4`, 2-up ≤860px) + charts/summary.
- **Admin – Trash & Owner – Trash:** deleted items with restore.
- **Owner – Ringkasan:** performance summary (stats, header row).
- **Owner – UMKM Saya:** owner's listings with edit/delete/duplicate actions.
- **Owner – Ulasan:** reviews on the owner's businesses, each with a "reply" action (opens
  reply-review modal).
- **Owner – Profil / Admin – Edit Profil:** profile edit forms (max-width ~560px cards).

### Modals (dashboard)
Overlay `rgba(15,30,45,.5)`, centered, `animation: floatUp .18s ease`, white sheet with
`border-radius: 16–20px` and `box-shadow: 0 30px 70px rgba(9,24,40,.4)`. Clicking the backdrop
closes; clicking the sheet does not (stop-propagation). Kinds: edit-UMKM (max 680px, scrollable),
UMKM action menu (max 288px), manage-user (max 410px), submission-detail (max 560px, scrollable),
reply-review (max 480px).

## Interactions & Behavior
- **Navigation:** changing `page` swaps the view and scrolls to top; deep links via URL hash are
  honored on load and on `hashchange`. Implement as real routes.
- **Search & filter (directory/home):** live filter by category chip, location, and text query
  (matches name + tag + category, case-insensitive); results sorted by rating desc.
- **Favorites:** toggling a heart adds/removes the id from a favorites list and updates the nav
  badge count. Guests attempting to favorite are redirected to login.
- **Reviews:** star picker (1–5) + text; submitting adds to the business's reviews. Guests are
  gated with a sign-in prompt.
- **Auth (prototype):** login/register are mocked — submitting sets an `auth` object
  (`{ name, role }`) and routes to home/dashboard. Wire to real auth in production.
- **Owner/Admin CRUD:** create/edit/delete/duplicate listings, restore from trash, verify or
  reject submissions, reply to reviews, manage users. In the prototype these mutate local state;
  back them with real APIs.
- **Transitions:** nav/link hovers `translateY(-1px)` (~.12–.18s); chips/cards `transition: all
  .12s`; modal enter `floatUp .18s ease` (fade + 16px rise). Inputs on focus: border `#2C5EAD` +
  `box-shadow: 0 0 0 3px rgba(44,94,173,.14)`.
- **Responsive:** breakpoints at **860px** (tablet: hide desktop nav → hamburger, collapse most
  grids, stack auth split, hide brand panel, tables scroll) and **560px** (mobile: single-column
  grids, smaller hero heading, tighter padding). Full rules are in the prototype's `<style>` block.

## State Management
Key state the app needs (from the prototype's `Component.state`):
- **Session/role:** `auth` (`{ name, role }` where role ∈ `user | owner | admin`), or `null` for
  guest; register `regRole`.
- **Routing:** `page`, `selectedId` (detail), `adminTab`, `ownerTab`, `settingsTab`, `historyTab`.
- **Filters:** `cat`, `loc`, `q`, `heroCat`.
- **Favorites:** `favorites` (array of UMKM ids).
- **Review composer:** `reviewStars` (default 5), `reviewText`, `reviewMedia`.
- **Forms:** login (`loginEmail`, `loginPass`), register (`regName`, `regEmail`, `regPass`),
  profile (`profileName/Email/Phone`), password change (`pwOld/pwNew/pwConfirm`), delete confirm.
- **Settings:** `perms` (emailNotif, pushNotif, location, promo, dataShare), `security` (twofa,
  loginAlert).
- **Owner/Admin data & lifecycle:** `deletedUmkm`, `duplicatedUmkm`, `hiddenUmkm`, `trash`,
  `ownerTrash`, `umkmStatus`, `purgedUsers`, `deletedAccounts`, comment history.
- **UI:** `modalKind` + `modalItem` + `editDraft` + `replyText`, `lightbox`, `profileMenuOpen`,
  `mobileNav`, `settingsOpen`, `helpOpen`/`helpTab`, forgot-password flow.

### Data fetching
The prototype ships hard-coded seed data (see `data()` and `reviewsFor()` in the source, and
**Seed Data** below). In production, replace with API calls: list/detail UMKM, reviews, favorites,
submissions/verification, users, and reports.

## Design Tokens

### Colors
- **Background (page):** `#FAF7F1` (warm cream). Dashboard bg: `#F1EDE4`. Alt surface: `#F1ECE0`.
- **Surface (cards):** `#FFFFFF`.
- **Text primary:** `#16324B` (deep navy). Secondary text: `#4B5563` / `#6B7280`. Muted:
  `#8A8578` / `#9AA1A9` / `#B4AE9F`.
- **Primary / brand blue:** `#2C5EAD` (hover/focus ring `rgba(44,94,173,.14)`; button shadow
  `rgba(44,94,173,.28)`). Deep blue `#1E4372`. Light blue tint `#EAF1FB` / `#EEF4FC`.
- **Gold / amber accent:** `#C98A2E` (labels), `#F0B857` / `#D6A24A` (highlights).
- **Teal accent:** `#3E8E82` (tint `#E3EFED`).
- **Danger / heart red:** `#C0472F` (soft bg `#FBEEEA` / `#FCF1EE`, border `#EFC8BD`, deep text
  `#8A2818`).
- **Borders / hairlines:** `#ECE5D8`, `#EFE9DC`, `#E4DCCC`, `#EAE2D2`, `#EFE9DC`, `#E7DFCF`.
- **Category accents** (each has `accent`, `soft` tint, `initial`):
  - Kuliner — accent `#C98A2E`, soft `#F7EDDC`, K
  - Penginapan — accent `#3E8E82`, soft `#E3EFED`, P
  - Fashion — accent `#2C5EAD`, soft `#E6EDF8`, F
  - Oleh-Oleh — accent `#1591DC`, soft `#E1F1FB`, O
  - Jasa — accent `#5B6672`, soft `#EEF0F2`, J

### Gradients
- Hero / legal / account headers: `linear-gradient(160deg,#16324B 0%,#1E4372 55%,#2C5EAD 100%)`
  (and simpler `linear-gradient(160deg,#16324B,#2C5EAD)`).
- CTA banners: `linear-gradient(120deg,#3E8E82,#2C5EAD)`.
- Register brand panel: `linear-gradient(165deg,#3E8E82,#16324B)`.

### Typography
- **Family:** `'Plus Jakarta Sans', system-ui, sans-serif` (Google Fonts, weights 400/500/600/700/800).
  `-webkit-font-smoothing: antialiased`.
- **Weights used:** 800 (headings, wordmark, card titles), 700 (nav, buttons, labels, badges),
  600 (nav inactive, body emphasis), 400/500 (body).
- **Representative sizes:** dashboard H1 29px / `-.02em`; section H2 24px / `-.02em`; hero H1 large
  → 33px ≤560px; body ~14–15px; nav 14.5px; chips/labels 13–13.5px; small labels 10–12px
  (uppercase labels use `letter-spacing: .08–.16em`). Minimum readable text ~10.5px (badges).

### Radius
- Buttons `11px`; icon buttons / inputs `12px`; nav items `10px`; cards `16–20px`; chips & avatars
  & badges `999px` (pill/circle); CTA banners `24–26px`; role cards `14px`.

### Shadows
- Primary button: `0 6px 16px rgba(44,94,173,.28)`.
- Card / soft: `0 4px 18px rgba(19,50,77,.05)`, `0 8px 26px rgba(19,50,77,.06)`.
- Heart button: `0 4px 12px rgba(9,24,40,.18)`.
- Dropdown: `0 14px 34px rgba(9,24,40,.16)`.
- Modal: `0 30px 70px rgba(9,24,40,.4)`.
- Focus ring: `0 0 0 3px rgba(44,94,173,.14)`.

### Misc
- Scrollbar thumb `#d9d2c4`, `border-radius: 8px`.
- Placeholder text `#9AA1A9`.
- Keyframe `floatUp`: opacity 0→1 + `translateY(16px)`→0.

## Seed / Sample Data
The prototype contains a realistic seed set you can reuse for fixtures — **10 UMKM** across the five
categories, each with: `name`, `cat`, `loc` (Balikpapan sub-district), `rating`, `reviews`,
`priceLabel`, `tag` (short description), `address`, `hours`, `phone`, `ig`, and an `items` list of
name/price (menu / room type / product / service). Example: *Warung Kepiting Kenari* (Kuliner,
Balikpapan Timur, 4.8★, 213 reviews, Rp25–75rb). See `data()`, `reviewsFor()`, and
`commentHistory` in `NearBy Balikpapan.dc.html` for the full copy. All copy is in Bahasa Indonesia
and should be preserved.

## Assets
- **Logo:** `logo-nearby-mr7ni322.png` (included in this bundle) — used at 42px in the nav and 78px
  in hero/brand panels. The wordmark "NearBy" + "BALIKPAPAN" is set in HTML text, not baked into the
  image.
- **Business/photo imagery:** the prototype uses **striped diagonal placeholders** with a label
  (e.g. "foto kepiting") and an emoji glyph — no real photos are included. Source real photography
  per listing for production; the design expects a photo at the top of each business card and a
  gallery on the detail page.
- **Icons:** the prototype uses text/emoji glyphs (♡ ♥ ☰ ⚙ ★ ▾ ←). Replace with the codebase's
  icon set (e.g. a proper heart, hamburger, gear, star, chevron, back arrow).
- **Fonts:** Plus Jakarta Sans via Google Fonts.

## Files
- `NearBy Balikpapan.dc.html` — the full high-fidelity prototype (all views, states, and seed
  data). Primary reference.
- `logo-nearby-mr7ni322.png` — logo asset.
- `support.js` — the prototype's custom runtime. **Reference only — do not port it.** Read the
  `<x-dc>` template and the `Component` class inside the HTML for markup, state, and behavior.

To view the prototype, open `NearBy Balikpapan.dc.html` in a browser (it self-loads `support.js`).
