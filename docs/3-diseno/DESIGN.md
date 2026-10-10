# Design System: SIGPAP — Salvia & Teja

**Skill:** stitch-design-taste
**Project:** SIGPAP (Confort y Muebles — Posadas, Misiones)
**Purpose:** Production ERP — Producción, Inventario, Compras, Análisis Predictivo, Reportes, Auditoría, Usuarios/Seguridad
**Stack:** Laravel (Inertia.js + Vue 3) + PostgreSQL · UI generated with Google Stitch

---

## Configuration — Set Your Style

| Dial | Level | Description |
|------|-------|-------------|
| **Creativity** | `6` | `1` = Ultra-minimal, Swiss, silent, monochrome. `5` = Balanced, clean but with personality. `10` = Expressive, editorial, bold typography experiments. This is an OPERATIONAL system: personality through material and detail, never through chaos. |
| **Density** | `8` operational · `4–5` executive | **TWO MODES.** `1` = Gallery-airy, massive whitespace. `5` = Balanced. `10` = Cockpit-dense, data-heavy. Operational screens (part diario, stock, purchases, Gantt) run at **8 = cockpit**. Executive screens (management dashboard, reports) run at **4–5 = airy with progressive disclosure**. Never apply 8 to an executive view. |
| **Variance** | `5` | `1` = Predictable, symmetric grids. `5` = Subtle offsets. `10` = Artsy chaotic. Asymmetry is allowed in dashboards only; operational forms stay aligned and scannable. |
| **Motion Intent** | `6` | `1` = Static. `5` = Subtle hover/entrance cues. `10` = Cinematic. Applied in code phase only — Stitch generates static screens. |

> **How to use:** This system runs at **Creativity 6 / Variance 5 / Motion 6**, with **two density modes** selected per screen, not per project:
>
> - **Operational mode (Density 8)** — part diario, stock, purchases, Gantt, audit log. Cockpit-dense, every number in mono, hairline dividers instead of cards.
> - **Executive mode (Density 4–5)** — management dashboard, executive reports. Fewer items on screen, generous whitespace, one primary visualization, everything else collapsed behind disclosure panels. The gerente must read state in 5 seconds and open only what they need.
>
> The rest of the rules below adapt to the selected mode. When a rule says "at Density 8", it applies to operational mode; executive mode relaxes that rule.

---

## 1. Visual Theme & Atmosphere

A precise, modern operations room resting on a **sage canvas** — calm, botanical, never clinical blue-white. The single **terracotta accent** carries the warmth of fired clay and the wood of the product without decorative flourishes. The atmosphere is that of a well-lit millwork studio in the morning: quiet, measured, material — every panel earns its place through function. Dashboards feel executive and alive (asymmetric bento, mono numerals), operational screens (part diario, stock entry, purchase orders) stay structurally disciplined with generous internal padding and clear visual zones. Nothing shouts, nothing is generic: the interface is a machined tool with a living, natural warmth.

## 2. Color Palette & Roles

- **Canvas Sage** (#EBF0E9) — Primary background surface. Fresh green-gray, never clinical blue-white, never cold gray
- **Surface White** (#FFFFFF) — Cards, panels, containers
- **Workshop Surface** (#DEE6DC) — Secondary fills: table headers, sidebar footer, disabled wells, code/readout background. One step deeper than canvas
- **Sage Ink** (#22281F) — Primary text. Near-black with a green undertone — never pure black
- **Sage Steel** (#5B6258) — Body text, descriptions, metadata. Muted sage-gray
- **Muted Clay** (#9BA39A) — Tertiary text, timestamps, placeholder, disabled states
- **Hairline Border** (rgba(34,40,31,0.10)) — 1px structural lines, card borders, dividers. Sage-tinted, always subtle
- **Diffused Shadow** (rgba(34,40,31,0.06)) — Card elevation. Wide-spreading, 24px blur, -8px offset. Never harsh, never dark

### Accent Selection (ONE primary)

- **Teja** (#B4491F) — THE accent: CTAs, active nav state, focus rings, primary data highlights. Fired clay, saturated below 80%. Text on Teja: always white
- **Teja Deep** (#8F3A16) — Hover/pressed state of Teja. Darker, never brighter
- **Teja Wash** (#F6E3DA) — Subtle tinted background: active nav row, selected list item, chart fill baseline. Large-radius, low-opacity usage

### Secondary Selection (ONE supporting)

- **Sage Deep** (#4A6B52) — Supporting color: secondary actions, operational success states, secondary navigation elements, status fills. Muted forest green
- **Sage Wash** (#E2EBE3) — Subtle tinted background for sage states: selected filters, secondary chips, success fills baseline

### Semantic Status Colors (functional, not decorative — allowed alongside the accent)

- **Pine Signal** (#1E7A4F) — Success: stock OK, order ready, machine operational. Used with Pine Wash (#E7F1EC) backgrounds
- **Hazard Oxide** (#B42318) — Danger/error: stock critical, machine blocked, failed validation. Used with Oxide Wash (#FBE9E7) backgrounds
- **Amber Signal** (#B45309) — Warning: low stock threshold, preventive maintenance due, pending approval. Used with Amber Wash (#FBF3E4) backgrounds
- **Slate Inform** (#4A5568) — Neutral/active: in-progress status, informational badges. Used with Slate Wash (#EDF0F3) backgrounds

### Banned Colors

- Purple/Violet neon gradients — the "AI Purple" aesthetic
- Pure Black (#000000) — always Sage Ink or near-black
- Neon/bright green terminal aesthetics (Sage Deep is muted, never neon)
- Oversaturated accents above 80% saturation
- Mixed warm/cool gray systems within one project — every neutral carries the sage undertone

## 3. Typography Rules

- **Display / UI:** `Geist` (primary) — Track-tight (`-0.02em`), weight-driven hierarchy (600–700 for headers, 500 for section titles). Compressed leading (`1.15`) on headings. NOT screaming — hierarchy through weight and color, not size
- **Body:** `Geist` at weight 400 — Relaxed leading (`1.6`), max 65ch for prose, Sage Steel (#5B6258)
- **Mono:** `Geist Mono` — **MANDATORY for ALL numerals in both density modes**: KPIs, stock quantities, prices, part-of-day counts, dates, IDs, machine codes, table cells with numbers. This is the cockpit rule — numbers read as telemetry, not prose. Executive mode keeps mono numerals but shows fewer of them, larger
- **Scale:** Page titles at `clamp(1.5rem, 3vw, 2rem)`. Section titles `1.125rem`. Body `1rem`. Table data `0.875rem` with Mono numerals. Metadata `0.8125rem`

### Banned Fonts

- `Inter` — banned everywhere in premium contexts (Stitch falls back to Inter visually; the real Laravel build — Inertia.js + Vue 3 — uses Geist via webfont)
- Generic serif fonts (`Times New Roman`, `Georgia`, `Garamond`, `Palatino`) — BANNED in this software UI. Serif is always BANNED in dashboards and operational software regardless

## 4. Component Stylings

* **Buttons:** Flat surface, no outer glow, no gradient. Primary: Teja fill, white text. Secondary: ghost/outline with Hairline Border. Tertiary: text-only link. Active state: `scale(0.98)` tactile push. Hover: Teja Deep shift (primary) or Wash fill (secondary)
* **Cards/Containers:** Generously rounded corners (`1rem` — functional, not pill). Surface White fill. Hairline Border (`1px`). Diffused Shadow only when elevation communicates hierarchy. Internal padding `1.25rem–1.5rem`. **High-density rule:** replace card rows with `border-top` dividers or hairline table rows — cards only for KPI panels, modals, and detail views
* **KPI Cards (double-bezel detail):** Outer shell with Workshop Surface fill + Hairline Border + outer radius `1.25rem`; inner core offset with Surface White... keep it simple: single surface card, Hairline Border, large mono numeral (`clamp(1.75rem, 4vw, 2.5rem)`, Geist Mono 600), label above in Muted Clay, delta indicator with Pine/Hazard color. One KPI = one numeral + label + trend. No icons stuffed in
* **Inputs/Forms:** Label positioned above input. Helper text optional, Muted Clay. Error text below in Hazard Oxide. Focus ring: Teja, `2px` offset. No floating labels. Standard `0.5rem` gap between label-input-error stack. Input background Surface White, Hairline Border, radius `0.5rem`
* **Tables (the workhorse):** Hairline Border rows, sticky header with Workshop Surface, Mono numerals in data cells, zebra-free (dividers only), hover row = Teja Wash at 30%. Operational Density 8: `0.625rem` vertical cell padding, `0.875rem` data text. Numerals right-aligned, text left-aligned, headers Muted Clay uppercase `0.75rem` tracking-wide
* **Disclosure Panels (executive mode signature):** The primary tool for progressive disclosure. Collapsed by default: one header row — domain label (left), one-line summary + status badge (right), chevron indicator. Header height `3rem`, Hairline Border top, Workshop Surface fill. Expanded: reveals the detail (table, Gantt, list) inside the panel body with `1.25rem` padding. Pick one behavior per screen and keep it: accordion (only one open) or independent toggles. A collapsed panel NEVER shows more than one line of summary
* **Decision Tray (executive mode signature):** Ordered list of pending decisions, max 3 visible. Each row: action label (e.g. "Aprobar OC-2026-0047"), context in Muted Clay, single primary action button. No decoration, no icons beyond a status dot
* **Navigation (app shell):** Left sidebar (fixed, Surface White, Hairline Border right). Active item: Teja Wash fill + Teja left indicator + Sage Ink label. Section groups with uppercase Muted Clay labels. Topbar: page title + breadcrumb + user chip + global search. No hamburger on desktop
* **Status Badges:** Small pills (`0.75rem`, mono when numeric), uppercase `0.6875rem` in the status wash color with the signal text color. Used for order states, machine states, stock states
* **Loaders:** Skeletal shimmer matching exact layout dimensions and rounded corners. Shifting light reflection across placeholder shapes. Never circular spinners
* **Empty States:** Composed illustration or icon composition with guidance text and a primary action. Never just "No data found"
* **Error States:** Inline, contextual. Hazard Oxide underline or border. Clear recovery action

## 5. Layout Principles

- **Grid-First:** CSS Grid for all structural layouts. Never flexbox percentage math (`calc(33% - 1rem)` is BANNED)
- **App Shell:** Sidebar (`16rem`, collapsible to icon rail at `4.5rem` below 1024px) + topbar + content area. Content contained at `max-width: 1400px`, centered
- **Executive Dashboard (Density 4–5, three layers):** Layer 1 — status band: 4 KPI cards in a row, each = label + large mono numeral + status color + delta. Below it, one plain-language status sentence. Layer 2 — ONE primary visualization only: production trend vs target (line/area, 8 weeks). Layer 3 — disclosure panels, collapsed by default, one per domain (Producción, Inventario, Compras, Predictivo), each showing a one-line summary + alert badge; expanding reveals the operational detail (Gantt, alert list, critical stock). Plus a Decision Tray (max 3 pending decisions with direct action). **The Gantt does NOT belong on the executive dashboard** — it lives in the Producción operational view
- **Operational Dashboard (Density 8):** KPI row (4 uneven cards — `2fr 1fr 1fr`), then Gantt Predictivo full-width, then a 70/30 split (production alerts / inventory snapshot). Subtle asymmetry, never chaotic
- **Operational forms:** Single-column or two-column aligned grids, generous spacing, surface white panels. Discipline over drama — variance 5 means forms stay scannable
- **No Overlapping:** Elements must never overlap each other. No absolute-positioned layers stacking content on content. Every element occupies its own grid cell or flow position
- **No "3 equal cards in a row"** for feature sections — asymmetric bento or horizontal scroll instead
- **Full-Height:** Use `min-height: 100dvh` — never `height: 100vh` (iOS Safari address bar jump)

## 6. Component Behaviors for Stitch Prompts

When generating a screen, describe behaviors as:

- **Executive dashboard (Density 4–5, three layers):** Top to bottom. (1) Status band — 4 large KPI cards with status colors, one plain-language sentence below ("La planta opera con normalidad · 2 desvíos requieren atención"). (2) One trend chart: production vs target over 8 weeks. (3) Four collapsed disclosure panels (Producción, Inventario, Compras, Predictivo), each a single summary line + badge, expandable. Plus a "Requiere tu decisión" tray with max 3 items and direct action buttons. Air and hierarchy over density — the gerente reads state in 5 seconds and opens only what they need. No Gantt here
- **Parte Diario (part-of-day) entry:** The primary screen of the day. Big mono numeral inputs, one row per production order, additive workflow. Buttons: "Registrar avance" (Teja) — single primary CTA. No decorative chrome on load
- **Machine status list:** Status badges + mono counters. Machine needing preventive maintenance shows Amber Signal badge + reason in body text
- **Purchase order flow:** Kanban-style lane with order states (Solicitud → Cotización → Adjudicada → Orden emitida). Each card: order ID in mono, provider name, amount in mono, status badge
- **Predictive dashboard:** Gantt Predictivo full-width, projected vs actual in Pine/Hazard colors, alert list beside it, simulator entry as secondary action
- **Audit log:** Dense table — timestamp mono, user, action, before/after values, IP. Search + filters on top

## 7. Responsive Rules

Every screen must work flawlessly across all viewports. **Responsive is not optional — it is a hard requirement.**
- **Mobile-First Collapse (< 768px):** All multi-column layouts collapse to a strict single column. `width: 100%`, `padding: 1rem`, `gap: 1.5rem`. No exceptions
- **Sidebar behavior:** Below 1024px collapse to icon rail; below 768px become a slide-in drawer. Topbar keeps page title + primary action
- **No Horizontal Scroll:** Horizontal overflow on mobile is a critical failure
- **Typography Scaling:** Mono numerals scale via `clamp()`. Body text stays `1rem` minimum. Never shrink body below `14px`
- **Touch Targets:** All interactive elements minimum `44px` tap target. Tables become stacked cards or horizontal scroll on mobile with sticky first column (name/ID)
- **Navigation:** Desktop sidebar collapses to a clean mobile drawer. No tiny hamburger icons without labels
- **Testing Viewports:** Verify at `375px` (iPhone SE), `390px` (iPhone 14), `768px` (iPad), `1024px` (small laptop), `1440px` (desktop)

## 8. Motion & Interaction (Code-Phase Intent)

> **Note:** Stitch generates static screens — it does not animate. This section documents the **intended motion behavior** so the coding agent knows exactly how to implement animations when building the exported design into a live product.

- **Physics Engine:** Spring-based exclusively. `stiffness: 100, damping: 20`. No linear easing anywhere. Premium, weighty feel
- **Perpetual Micro-Loops:** Active dashboard components have infinite-loop states — Pulse on machine status dots (amber = awaiting maintenance), Typewriter on global search, Shimmer on loading states
- **Staggered Orchestration:** Lists and dashboard tiles mount with cascaded delays (`animation-delay: calc(var(--index) * 80ms)`). Waterfall reveals, never instant mount
- **Layout Transitions:** Smooth re-ordering via shared element IDs. Items swap positions with physics
- **Hardware Rules:** Animate ONLY `transform` and `opacity`. Never `top`, `left`, `width`, `height`. Grain/noise filters on fixed, pointer-events-none pseudo-elements only
- **Performance:** CPU-heavy perpetual animations isolated in microscopic leaf components. Target 60fps minimum

## 9. Anti-Patterns (Banned)

- No emojis — anywhere in UI, code, or alt text
- No `Inter` font — use `Geist` + `Geist Mono`
- No generic serif fonts (`Times New Roman`, `Georgia`, `Garamond`) — serif banned in all dashboard/software UI
- No pure black (`#000000`) — Sage Ink only
- No neon outer glows or default box-shadow glows
- No oversaturated accent colors above 80%
- No purple/blue-violet "AI SaaS" gradients
- No excessive gradient text on headers
- No custom mouse cursors
- No overlapping elements — text never overlaps images or other content
- No 3-column equal card layouts for feature sections
- No filler UI text: "Scroll to explore", "Swipe down", "Discover more below", bouncing chevrons — all BANNED
- No generic names: "John Doe", "Acme", "Nexus", "SmartFlow" — SIGPAP context: "Confort y Muebles", real-sounding Argentine supplier names ("Maderera del Alto Paraná", "Ferretería Industrial Posadas"), machine IDs like "MÁQ-014", order IDs like "OC-2026-0042"
- No fake round numbers: `99.99%`, `50%`, `1234567` — use organic data: `47.2%`, `1,234 m³/mes`, prices like `$12.480.500`
- No AI copywriting clichés: "Elevate", "Seamless", "Unleash", "Next-Gen", "Revolutionize"
- No broken Unsplash links — use `picsum.photos/seed/{id}/800/600` or SVG UI Avatars (rainbow-gradient avatars are also banned — neutral warm-tone avatars only)
- No generic `shadcn/ui` defaults — customize radii, colors, shadows to match this system
- No `z-index` spam — use only for Navbar, Modal, Overlay layer contexts
- No `h-screen` — always `min-h-[100dvh]`
- No circular loading spinners — skeletal shimmer only
- No rounded-full pills on large containers or primary buttons (radius `1rem` max) — pills only for tiny status badges