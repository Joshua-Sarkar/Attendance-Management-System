# 13. Reporting Rules (Reserved)

This document is a placeholder specifying the design parameters and future scope for custom workforce exports and reporting tables.

---

## 1. Subsystem Details

### Purpose
To allow administrators and managers to export filtered attendance history, late arrival averages, profile metrics, and monthly timesheets in structured file formats (CSV/Excel/PDF) for audit and archiving.

### Scope
- Exports of daily check-in/out logs with status indicators.
- CSV sheets listing employee profile dossiers for HR validation.
- Department-level summary spreadsheets tracking monthly attendance rates.

### Business Rules (Future Scope)
- **Role Restrictions**: General employees can only export their own personal history. Managers can export logs for their direct reports. Only HR Admins can generate company-wide reports.
- **Traceability**: All generated exports must append the runner's ID, the execution date, and the filters used in a footer or header.

### Current Implementation Status
- **Reserved for Future Development**. No reporting module or export services exist in the current release.

---

## 2. Expected Architecture & Integration

### Expected Integration Points
- **Attendance Tracking**: Query logs for export.
- **Workforce Directory**: Resolve employee details and names.
- **Dashboard**: Provide action buttons in the admin console to execute downloads.

### Dependencies
- `PhpOffice/PhpSpreadsheet` (already used in the Zimyo Import Engine).
- Standard PDF libraries (e.g. dompdf) for slip generations.

---

## 3. Related Modules & Cross References
- **[02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)**: Defines the data sources.
- **[08_MODULE_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/08_MODULE_MAP.md)**: Lists available models.
