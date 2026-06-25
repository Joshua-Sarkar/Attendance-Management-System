# AMS-V1 Canonical Design Specification

This document serves as the single canonical UI reference and engineering design system for the **Workforce Ledger Overhaul (Phase 4.8)** of the Attendance Management System (AMS-V1). All Blade files, HTML layouts, CSS variables, and Tailwind rules must conform to these measurements and behavioral guidelines.

---

## Part I — Design Philosophy & Material Language

### 1. Vision & Core Principles
AMS-V1 is an **Institutional Workforce Operating System**. The visual design must communicate trust, craftsmanship, permanence, and clear visual hierarchy.

- **Institutional Prestige**: Layout elements should feel permanent, authoritative, and tactile.
- **Editorial Composition**: Avoid repeating card grids. Construct pages like a curated magazine index.
- **Cinematic Atmosphere**: Use natural shadow layers, warm canvas backdrops, and strong text weights rather than decorative effects.
- **Usability Focus**: Aesthetics must never hinder scanning speed.

### 2. Primary Materials
- **Walnut Frame (`#1E1611`)**: Representing structure. Used for the vertical sidebar navigation column to frame the interface.
- **Warm Ivory Canvas (`#FAF8F5`)**: Representing the workspace page background. Eliminates cold gray light and eye strain.
- **Soft Cream Panels (`#F4EFE6`)**: Representing document containers. Used for tables, dashboard summaries, and action panels.
- **Aged Brass Accent (`#9C7C38` / `#C9A24B`)**: Representing indicators and active status. Used sparingly; brass highlights must be earned.
- **Vellum Text (`#24211E`)**: Dark charcoal ink for text, optimizing contrast on the warm ivory canvas.

### 3. Anti-Goals
- No blue/purple startup gradients or modern SaaS templates.
- No rounded, floating, drop-shadowed KPI cards.
- No cold grays (`#F3F4F6`), pure white (`#FFFFFF`), or pure black (`#000000`) layout elements.

---

## Part II — Foundations Spec

### 1. Color System
```css
:root {
  --canvas-dark: #1E1611;       /* Walnut: dark framing */
  --canvas-light: #FAF8F5;      /* Warm Ivory: page background */
  --panel-bg: #F4EFE6;          /* Soft Cream: panels/containers */
  --row-bg: #FAF8F5;            /* Near White: row backgrounds */
  --input-bg: #FBF9F6;          /* Parchment: input backgrounds */
  --text-dark: #24211E;         /* Dark Charcoal: primary body */
  --text-muted: #5E5852;        /* Muted Charcoal: subtext */
  --text-faint: #8A8177;        /* Faint Charcoal: labels/dates */
  --text-light: #ECE4D3;        /* Vellum Light: sidebar text */
  --text-light-muted: #9C9180;  /* Vellum Muted: sidebar copy */
  --brass: #9C7C38;             /* Aged Brass: accents */
  --brass-bright: #C9A24B;
  
  /* Status Colors */
  --forest: #234E39;
  --forest-bg: #E3ECE7;
  --burgundy: #6E1A24;
  --burgundy-bg: #F3E6E8;
  --slate: #3B5368;
  --slate-bg: #E7EDF1;
  --cognac: #8C4E2D;
  --cognac-bg: #F6ECE6;
  
  /* Borders */
  --hairline: rgba(156, 124, 56, 0.16);
  --hairline-strong: rgba(156, 124, 56, 0.28);
}
```

### 2. Typography Hierarchy
- **Display Serif (`Fraunces`)**: Used strictly for page headers, panel titles, and prominent metrics.
- **Interface Sans (`IBM Plex Sans`)**: Used for labels, descriptions, links, and body copy.
- **Tabular Mono (`IBM Plex Mono`)**: Used for timestamps, employee IDs, numeric counts, and status labels.

---

## Part III — Primitive Engineering Specifications

### 1. Sidebar Spine
- **Width**: `240px` (fixed width to prevent layout shifting on long translation strings).
- **Outer Padding**: `32px` vertical, `0px` horizontal.
- **Inner Navigation spacing**: `6px` vertical gap.
- **Navigation item**: Width `216px` (centered), height `42px` (Fitts' Law target for speed).
- **Branding Crest**: Height `76px` containing a circular logo (`36px` diameter, `1px` brass border) and `Fraunces` brand text.
- **Active Navigation State**: `color: var(--brass-bright); background: rgba(201,162,75,0.08); border-left: 3px solid var(--brass-bright); font-weight: 500;`.
- **Hover behavior**: `background-color: rgba(236,228,211,0.04); color: var(--text-light); transition: background 150ms ease;`.
- **Focus behavior**: `outline: 2px solid var(--brass); outline-offset: 2px;`.
- **Scroll behavior**: `overflow-y: auto`, hide scrollbars (`scrollbar-width: none`).
- **Responsive behavior**: Hidden on viewports `< 960px`.

---

### 2. Header
- **Outer padding**: `24px` vertical padding, border bottom `1px solid var(--hairline-strong)`.
- **Title font**: `Fraunces`, `32px`, weight `500`, tracking `-0.5px`, line-height `1.2`.
- **Sub-metadata font**: `IBM Plex Sans`, `13px`, weight `400`, color `var(--text-muted)`.
- **Date/Time block**: Column layout, aligned right.
- **Time font**: `IBM Plex Mono`, `22px`, weight `500`, color `var(--brass)`.
- **Date font**: `IBM Plex Sans`, `11.5px`, color `var(--text-faint)`.
- **Justification**: A larger display font ensures the user identifies page context instantly. Monospaced clock prevents character jumping during redraws.

---

### 3. KPI Briefing Strip
- **Container**: `display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid var(--hairline); background: var(--panel-bg); border-radius: 6px;`.
- **Columns**: 4 columns. Width `25%` each. Vertical border right `1px solid var(--hairline)` between columns.
- **Inner padding**: `24px` on all sides.
- **Label font**: `IBM Plex Sans`, `10.5px`, weight `600`, uppercase, tracking `1.5px`, color `var(--text-faint)`.
- **Value font**: `Fraunces`, `38px`, weight `500`, line height `1`, color `var(--text-dark)`.
- **Unit font**: `IBM Plex Sans`, `15px`, weight `400`, color `var(--text-muted)`.
- **Subtext/Meta font**: `IBM Plex Sans`, `12px`, weight `400`, color `var(--text-muted)`.
- **Justification**: Single-row grid replaces individual floating cards. This aligns with Fitts' Law and groups secondary details within a clean, horizontal reading axis.

---

### 4. Tables (Ledger Tables)
- **Container**: Borderless layout blocks nested in Soft Cream panels.
- **Row Padding**: Vertical padding `16px` (`py-4`), horizontal padding `8px` (`px-2`).
- **Row dividers**: Bottom border `1px solid var(--hairline)`. No vertical border rules.
- **Hover behavior**: `background-color: rgba(156,124,56,0.04); transition: background-color 150ms ease;`.
- **Column Sizing**:
  - Status Indicator Seal: `24px` fixed.
  - Time column: `48px` fixed.
  - Name + metadata: `1.8fr` (flexible).
  - Status tag: `120px` right-aligned.
- **Typography**:
  - Employee Name: `IBM Plex Sans`, `14.5px`, weight `600`, color `var(--text-dark)`.
  - Metadata detail: `IBM Plex Sans`, `12px`, color `var(--text-muted)`.
  - Time value: `IBM Plex Mono`, `13px`, color `var(--text-muted)`.
- **Justification**: Wide row padding (`16px`) prevents visual fatigue during multi-hour data auditing. Left-aligned names dominate the columns to optimize scanning.

---

### 5. Filters
- **Heights**: Select elements `38px` height.
- **Borders & Background**: Border `1px solid var(--hairline)`, background `var(--row-bg)`.
- **Font**: `IBM Plex Sans`, `13px`, weight `500`.
- **Padding**: `8px 12px` (right padding `32px` to accommodate custom arrow SVGs).
- **Focus state**: Border color changes to `var(--brass)`. Drop shadow is disabled.
- **Justification**: Tight, consistent element heights keep filters aligned with navigation elements.

---

### 6. Search
- **Height & Width**: Height `38px`, width `240px` (min) to `320px` (max).
- **Padding**: `8px 12px 8px 36px`.
- **Search icon**: Size `14px`, stroke `1.75`, absolute position `left: 12px`, color `var(--text-faint)`.
- **Font**: `IBM Plex Sans`, `13px`, weight `400`.
- **Border**: `1px solid var(--hairline)`.
- **Active focus**: Border color changes to `var(--brass)`, background changes to `#FFFFFF`.

---

### 7. Buttons
- **Primary Button**:
  - Height: `40px`.
  - Font: `IBM Plex Sans`, `13px`, weight `600`, uppercase, tracking `0.8px`.
  - Colors: Background `var(--brass)`, text `#FAF8F5`.
  - Border: None, border-radius `4px`.
- **Secondary Button**:
  - Height: `40px`.
  - Font: `IBM Plex Sans`, `13px`, weight `500`, uppercase, tracking `0.8px`.
  - Colors: Background `var(--panel-bg)`, border `1px solid var(--hairline-strong)`.
- **Danger Button**:
  - Font: `IBM Plex Sans`, `13px`, weight `600`, uppercase, tracking `0.8px`.
  - Colors: Background `var(--burgundy-bg)`, border `1px solid var(--burgundy)`, text `var(--burgundy)`.
- **Justification**: Fixed `40px` hit target ensures touch targets are comfortable and consistent.

---

### 8. Forms
- **Layout Grid**: Spacing gap between fields is `24px` vertical.
- **Form sections**: Grouped by thin boundaries (`border-bottom: 1px solid var(--hairline)`), padding bottom `20px`.
- **Section headers**: `Fraunces`, `18px`, weight `500`.
- **Labels**: `IBM Plex Sans`, `12px`, weight `600`, uppercase, tracking `1px`, margin-bottom `6px`.
- **Input background**: `#FBF9F6` (light parchment).
- **Focus state**: Gold border highlight (`1.5px solid var(--brass)`).
- **Validation layout**: Alert texts `11.5px`, color `var(--burgundy)`.
- **Justification**: Separating inputs by `24px` spacing ensures clear reading flow and prevents fields from blending together.

---

### 9. Profile Header
- **Height**: `120px` auto-height.
- **Container padding**: `24px` on all sides, background `var(--panel-bg)`, border-radius `6px`.
- **Avatar box**: Width `72px`, height `72px`, background `rgba(236,228,211,0.2)`, border `1px solid var(--brass)`.
- **Initials font**: `Fraunces`, `24px`, weight `500`, color `var(--text-dark)`.
- **Primary Name**: `Fraunces`, `26px`, weight `500`, color `var(--text-dark)`.
- **Metadata**: `IBM Plex Mono`, `12.5px`, color `var(--text-muted)`.

---

### 10. Employee Dossier
- **Menu width**: Side folder-tab list is `180px` wide.
- **Field alignment**: Two-column layout grid.
- **Left (Label) Column**: Fixed width `200px`, font `IBM Plex Sans`, `12px`, uppercase, tracking `1.2px`, color `var(--text-faint)`.
- **Right (Value) Column**: Font `IBM Plex Sans`, `14px`, weight `500`, color `var(--text-dark)`.
- **Sensitive data values**: Naming IDs or bank registers in `IBM Plex Mono`, `13.5px`.
- **Justification**: Clean metadata columns align scanning vectors along a predictable vertical axis.

---

### 11. Attendance Ledger
- **Status Seals**: Size `8px` diameter, absolute position centered next to rows.
- **Status tag badges**: Label text in `IBM Plex Mono`, `11px`, weight `600`, tracking `0.8px`, uppercase, padding `4px 10px`, border-radius `4px`.
- **Tag color definitions**:
  - Present: Background `var(--forest-bg)`, text `var(--forest)`.
  - Late: Background `var(--cognac-bg)`, text `var(--cognac)`.
  - Leave: Background `var(--slate-bg)`, text `var(--slate)`.
  - Absent: Background `var(--burgundy-bg)`, text `var(--burgundy)`.

---

### 12. Leave Registry
- **Category counters**: Sourced in a single horizontal strip.
- **Metric values**: `Fraunces`, `24px`, weight `500`.
- **Category labels**: `IBM Plex Sans`, `11px`, weight `500`, uppercase.
- **Request drawer**: Width `480px` slide-out, background `var(--panel-bg)`, border-left `1px solid var(--hairline-strong)`.

---

## Part IV — Design Review Checklist

Before implementing any screen layout, verify that:
1. All elements map directly to approved CSS color tokens.
2. Typography matches the standard rules (Serif headers, Sans-serif body, Monospace numbers/IDs).
3. Primary layout containers rely on vertical whitespace margins rather than nested cards.
4. Active sidebar states use the `3px` solid gold left border and active brass text.
5. All buttons and interactive fields have a height of `40px` or `38px`.
6. Contrast ratios meet the standard target of 4.5:1.
7. Spacing follows modular increments (e.g. `6px`, `12px`, `24px`, `36px`).
8. The page follows one of the canonical page archetypes (Briefing, Ledger, Dossier, Workflow).
