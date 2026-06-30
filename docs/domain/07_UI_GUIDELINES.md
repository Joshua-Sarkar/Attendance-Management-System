# 07. UI & UX Guidelines

This document serves as the design system manual and UX specification for the Attendance Management System. All views, layouts, and components must adhere to these parameters.

---

## 1. Material Philosophy & Canvas Palette

### Design Vision
AMS-V1 is an **Institutional Workforce Operating System**. The interface avoids the style of typical SaaS dashboards in favor of a tactile, permanent editorial format that minimizes eye strain and highlights records clearly.

### Canvas Colors
| Token Name | Hex Value | Intended Usage |
| :--- | :--- | :--- |
| **Walnut Frame** (`--canvas-dark`) | `#1E1611` | Sidebar background, framing element. |
| **Warm Ivory** (`--canvas-light`) | `#FAF8F5` | Main workspace canvas background. |
| **Soft Cream** (`--panel-bg`) | `#F4EFE6` | Panel containers, tables, lists cards. |
| **Near White** (`--row-bg`) | `#FAF8F5` | Alternating rows and filter boxes. |
| **Parchment** (`--input-bg`) | `#FBF9F6` | Form input fields. |
| **Dark Charcoal** (`--text-dark`) | `#24211E` | Primary body text. |
| **Muted Charcoal** (`--text-muted`) | `#5E5852` | Subtext and secondary details. |
| **Faint Charcoal** (`--text-faint`) | `#8A8177` | Captions, dates, and borders. |
| **Aged Brass** (`--brass`) | `#9C7C38` | Primary highlights, button backgrounds. |
| **Bright Brass** (`--brass-bright`) | `#C9A24B` | Active navigation indicators. |

### Status Colors
| Status Name | Text Color | Background Color | Status Mapped |
| :--- | :--- | :--- | :--- |
| **Forest** | `#234E39` | `#E3ECE7` | Present / Late (within grace) |
| **Cognac** | `#8C4E2D` | `#F6ECE6` | Late (arrival delay status) |
| **Slate** | `#3B5368` | `#E7EDF1` | Approved Leaves / WFH |
| **Burgundy** | `#6E1A24` | `#F3E6E8` | Absent / Rejections |

---

## 2. Typography Hierarchy

The system uses three primary font families to distinguish content:
1. **Display Serif** (`Fraunces`): Used for primary page headers, panel titles, and large KPI numbers to project quality.
2. **Interface Sans** (`IBM Plex Sans` or system sans-serif): Used for all body text, input labels, menus, and layout items.
3. **Tabular Mono** (`IBM Plex Mono` or system monospaced): Used for values that require direct alignment — numeric dates, timestamps, employee IDs, and status codes. This prevents layout shifting during redraws.

---

## 3. Structural Component Metrics

### A. Sidebar Navigation Column
- **Width**: Fixed `240px` (blocks layout shifting).
- **Navigation item**: Height `42px` (optimal hit target).
- **Active Navigation Indicator**: 3px solid gold border on the left side of the item, using active brass text.

### B. Header Block
- **Padding**: `24px` vertical.
- **Clock**: Aligned right, rendered in `IBM Plex Mono` display font to prevent visual width shaking as digits change.

### C. KPI Briefing Strip
- **Container**: Single horizontal grid containing 4 columns. Individual floating cards are forbidden.
- **Inner Padding**: `24px` on all sides.

### D. Table Records (Ledger Tables)
- **Cell Padding**: Vertical padding `16px` (`py-4`), horizontal padding `8px` (`px-2`).
- **Dividers**: Bottom border `1px solid var(--hairline)`. No vertical lines.
- **Hover highlighting**: `background-color: rgba(156,124,56,0.04)` on row hover.

### E. Input & Buttons
- **Height**: Select controls and search inputs are fixed at `38px`.
- **Primary / Secondary / Danger Buttons**: Fixed at `40px` height.
- **Focus Highlighting**: Gold border transition (`1.5px solid var(--brass)`). Shadows and glow borders are disabled.

---

## 4. Current Implementation & Known Inconsistencies

### Current Implementation
- Declared in the main layout file [components/ledger-layout.blade.php](file:///c:/Users/Lenovo/AMS-V1/resources/views/components/ledger-layout.blade.php) and custom CSS.
- Color tokens are defined as CSS custom variables in the root container.

### Known Inconsistencies
- Some layout cards in the dashboard settings retain default Tailwind light-mode whites (`bg-white`) and standard SaaS rounded shadow boxes, which deviate from the flat Soft Cream panel system.
- Some table rows use small vertical padding (`py-2`) instead of the standardized `py-4` table spacing.

### Future Improvements
- Refactor the CSS variables to guarantee that all Blade files use semantic tokens (e.g., `bg-panel` instead of hardcoded tailwind utility classes).

---

## 5. Related Modules & Cross References
- **[08_MODULE_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/08_MODULE_MAP.md)**: Mappings of View files.
- **[10_CHANGE_GUIDE.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/10_CHANGE_GUIDE.md)**: Coordinates layout updates.
