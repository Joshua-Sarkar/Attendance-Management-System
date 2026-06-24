# UI Overhaul Specification & Pre-Phase 4.8 Audit

This document serves as the unified specification, design debt register, component inventory directory, and visual readiness checklist for the **Phase 4.8 Executive UI Overhaul** of the Attendance Management System (AMS-V1).

---

## 1. Style Guidelines & Visual Palette

The visual direction of the AMS-V1 interface focuses on **Executive Operations Software** with an **Editorial Information Design** aesthetic.

* **Palette Limits (No Neon/Bright Gradients):**
  * Canvas/Background: Deep dark charcoal-gold (`#0F0D0B`)
  * Surface/Panels: Rich dark stones (`#17130F`, `#1C1712`)
  * Text Body: Vellum (`#ECE4D3`)
  * Subtext / Copy: Vellum Muted (`#9C9180`)
  * Highlights & Titles: Brass (`#C9A24B`)
  * Present/Active: Forest Sage (`#8FB6A3` text, `rgba(143, 182, 163, 0.12)` background, `border border-[#8FB6A3]/20`)
  * Absent/Skipped/Error: Rose Burgundy (`#C37D8F` text, `rgba(195, 125, 143, 0.12)` background, `border border-[#C37D8F]/20`)
  * Late/Pending: Copper Cognac (`#C38965` text, `rgba(195, 137, 101, 0.12)` background, `border border-[#C38965]/20`)
  * Leave/WFH/Approved: Steel Slate (`#94ABC3` text, `rgba(148, 171, 195, 0.12)` background, `border border-[#94ABC3]/20`)
  * Weekend: Muted border, `text-vellum-faint bg-transparent`
* **Typography Hierarchy:**
  * Displays / Screen Headers: `Fraunces`
  * Body Copy / Labels: `IBM Plex Sans`
  * Timestamps / Numeric IDs: `IBM Plex Mono` (using monospaced/tabular figures: `tabular-nums` / `font-mono`)
* **Standard Cell Padding & Row Hover:**
  * Vertical padding: `py-3.5 px-5`
  * Row Hover highlight: `hover:bg-brass/[0.04] transition duration-150`

---

## 2. Screen-by-Screen Information Hierarchy Audit

For each key screen in the system, we have mapped the user accomplishments, eye-draw focal points, and visual de-emphasis goals:

* **Dashboard & Clock Check-In:**
  * *Goal:* Punch check-in/out logs and monitor daily workforce exceptions.
  * *Primary Eye-Draw:* Active Clocking buttons (for employees); Exception statistics cards grid (for admins).
  * *Secondary Eye-Draw:* Current check-in logs table.
  * *De-emphasis:* Static profile parameters and total company size counts.
* **Workforce Directory (`employees/index.blade.php`):**
  * *Goal:* Find employee records, department details, and leave balances.
  * *Primary Eye-Draw:* Left-aligned monospaced employee IDs and names.
  * *Secondary Eye-Draw:* "Add Employee" header button.
  * *De-emphasis:* Inline delete button styling (restructured as desaturated burgundy lines).
* **Workforce Profile details (`employees/show.blade.php`):**
  * *Goal:* Inspect personal, professional, address, banking, and emergency details.
  * *Primary Eye-Draw:* Summary profile header card (Avatar initials, employee ID, role status tag).
  * *Secondary Eye-Draw:* "Request Profile Correction" button.
  * *De-emphasis:* Field labels (PF No, IFSC Code) compared to their actual values.
* **Attendance Logs Audit Center (`admin/attendance-logs.blade.php`):**
  * *Goal:* Search check-ins by date/name and calculate delay averages.
  * *Primary Eye-Draw:* late-arrival metrics counters and delay values.
  * *Secondary Eye-Draw:* Filter toolbar dropdown inputs.
  * *De-emphasis:* Weekend rows (rendered with reduced opacity).
* **Leaves Management Console (`leaves/index.blade.php`):**
  * *Goal:* Check available leave credit ledger rows and submit requests.
  * *Primary Eye-Draw:* "+ Apply for Leave" action button.
  * *Secondary Eye-Draw:* Categories list stats counters.
  * *De-emphasis:* Truncated long leave reasons lines.
* **Leave Approvals Tab Queue:**
  * *Goal:* Assess pending requests and resolve paid/unpaid/rejection outcomes.
  * *Primary Eye-Draw:* Forest Sage / Rose Burgundy action buttons.
  * *Secondary Eye-Draw:* Requested dates range and employee name.
* **Excel Imports console (`admin/import-employees.blade.php`):**
  * *Goal:* Post spreadsheets and inspect errors summary.
  * *Primary Eye-Draw:* Success / Skipped rows counter metrics.
  * *Secondary Eye-Draw:* File uploader panel form.
* **Profile Corrections review (`admin/correction-requests/index.blade.php`):**
  * *Goal:* Inspect and resolve employee-submitted profile edits.
  * *Primary Eye-Draw:* Highlighted request messages.
  * *Secondary Eye-Draw:* HR response text form inputs.

---

## 3. UI Component Inventory & Recommendations

Our component audit indexes current structures and provides Keep/Rebuild recommendations for the Phase 4.8 Visual Overhaul:

1. **Primary Button (`<x-primary-button>`):** Keep. Brass background (`bg-brass text-canvas`), proper uppercase tracking.
2. **Secondary Button (`<x-secondary-button>`):** Keep. Bordered stone styling (`bg-surface-raised border-hairline text-vellum`).
3. **Danger Button (`<x-danger-button>`):** Keep. Soft burgundy design (`bg-burgundy text-vellum border-burgundy/30`).
4. **Text Input (`<x-text-input>`):** Keep. Dark stone backgrounds (`bg-surface-raised`), thin gold hairline outline.
5. **Input Label (`<x-input-label>`):** Keep. Simple vellum-muted labels.
6. **Input Error (`<x-input-error>`):** Keep. Soft red validation text.
7. **Core Sidebar (`<x-sidebar>`):** Keep. Left navigation column with active markers and SVGs.
8. **Dropdown Menu (`<x-dropdown>`):** **REBUILD (Phase 4.8)**. Currently uses default Breeze light/dark styling (`bg-white dark:bg-gray-800`). Must be re-skinned to dark stone selections.
9. **Modal Container (`<x-modal>`):** Keep. Alpine-driven overlay panel using `.glass-panel` wrappers.
10. **Legacy JS Modals (Leaves Index):** **REBUILD (Phase 4.8)**. Handcrafted absolute overlays containing raw script triggers. Must be migrated to the Alpine `<x-modal>` component.
11. **Status Tag Badge (`.tag`):** Keep (Cleaned). Pill-shaped elements converted to thin-bordered tags in Phase 4.7.3.
12. **Ledger Timeline Seal (`.seal`):** Keep. Timeline circle dots. Ensure text alternative is present on matching table row.
13. **Stat Cards (`.stat-card`):** Keep. Dashboard KPI summaries.
14. **Content Panel (`.panel`):** Keep. Rounded border cards with gold hairlines (`border-hairline`).

---

## 4. Visual Layout & Design Debt Register

These items represent identified design debt to be resolved during the Phase 4.8 visual overhaul:

* **Debt 1: Inconsistent spacing wrappers:**
  * *Location:* Across all `.blade.php` templates.
  * *Description:* Mismatches between Breeze standard margins (`max-w-7xl mx-auto sm:px-6`) and the dark-gold custom containers (`px-11 py-9 max-w-[1180px]`).
  * *Fix:* Standardize all workspaces to use the unified container class.
* **Debt 2: Workforce table responsiveness:**
  * *Location:* `resources/views/employees/index.blade.php`.
  * *Description:* Table contains 10 columns which overflow on smaller mobile screens.
  * *Fix:* Implement responsive card grids for mobile viewports, reserving the data table for desktop.
* **Debt 3: Inline modals in approvals:**
  * *Location:* `resources/views/leaves/index.blade.php`.
  * *Description:* Raw HTML overlays with inline script block triggers instead of Alpine `<x-modal>` structures.
  * *Fix:* Unify modal components.
* **Debt 4: Raw HTML Selects:**
  * *Location:* Leaves create, dashboard dropdowns, corrections.
  * *Description:* Select fields use default browser arrows without cohesive styled drop indicators.
  * *Fix:* Establish a custom `<x-select-input>` component.
* **Debt 5: Long Profile lists scrolling:**
  * *Location:* `resources/views/employees/show.blade.php`.
  * *Description:* Displays 10 sections vertically, forcing excessive scrolling.
  * *Fix:* Implement visual tabbed groupings (e.g. Personal, Contact, Professional, Banking).
* **Debt 6: Settings dropdown colors:**
  * *Location:* `resources/views/components/dropdown.blade.php`.
  * *Description:* Standard Breeze colors (`bg-white`) cause minor visual flashes when active.
  * *Fix:* Re-skin using standard dark surfaces.
* **Debt 7: Alerts reflows:**
  * *Location:* Session flash alerts in dashboards and lists.
  * *Description:* Standard session alert banners push lists down on page load.
  * *Fix:* Create floating or absolute toast overlays.
