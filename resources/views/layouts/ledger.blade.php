<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Workforce Attendance Ledger')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #FAF7EF;
            --panel: #FDFBF6;
            --panel-alt: #FFFFFF;
            --border: #E8E0D0;
            --border-soft: #EFE9DC;
            --ink: #2A241C;
            --ink-soft: #6E6455;
            --ink-faint: #A69C89;
            --gold: #AD8A3E;
            --gold-soft: #F4EBD6;

            --present: #4C7A5D;
            --present-bg: #EAF1EA;
            --late: #B98A2E;
            --late-bg: #F7EFDD;
            --halfday: #BE6A34;
            --halfday-bg: #F8EAE0;
            --absent: #9C3B3B;
            --absent-bg: #F5E5E3;
            --unplanned: #7A2E2E;
            --unplanned-bg: #F0E0DE;
            --paidleave: #AD8A3E;
            --paidleave-bg: #F4EBD6;
            --wfh: #4A6FA0;
            --wfh-bg: #E7ECF3;
            --weekoff: #9A9080;
            --weekoff-bg: #F0ECE3;
            --holiday: #8F887A;
            --holiday-bg: #EFEBE2;
            --birthday: #7D6296;
            --birthday-bg: #EEE7F1;

            --shadow-sm: 0 1px 2px rgba(42, 36, 28, 0.04);
            --shadow-md: 0 4px 16px rgba(42, 36, 28, 0.07);
            --shadow-lg: 0 16px 46px rgba(42, 36, 28, 0.13);
            --emboss: inset 0 1px 0 rgba(255, 255, 255, 0.85), inset 0 -1px 0 rgba(42, 36, 28, 0.035);
            --radius: 10px;
            --radius-lg: 18px;
            --radius-xl: 20px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background:
                radial-gradient(1200px 500px at 15% -10%, rgba(173, 138, 62, 0.06), transparent 60%),
                var(--bg);
            color: var(--ink);
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            padding: 40px 48px 80px;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .eyebrow {
            font-size: 11px;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            color: var(--ink-faint);
            font-weight: 600;
        }

        /* ---------- Header ---------- */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 34px;
            padding-bottom: 28px;
            border-bottom: 1px solid var(--border-soft);
        }

        .page-header h1 {
            font-family: 'Fraunces', serif;
            font-weight: 500;
            font-size: 40px;
            letter-spacing: -0.01em;
            line-height: 1.05;
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--ink-soft);
            font-size: 14.5px;
            max-width: 520px;
            line-height: 1.5;
        }

        .header-meta {
            text-align: right;
        }

        .header-meta .eyebrow {
            margin-bottom: 6px;
        }

        .header-meta .val {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            font-weight: 500;
        }

        /* ---------- Section heading ---------- */
        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-heading h2 {
            font-family: 'Fraunces', serif;
            font-size: 23px;
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        .section-heading .sub {
            font-size: 12.5px;
            color: var(--ink-soft);
            margin-top: 3px;
        }

        /* ---------- Date range control ---------- */
        .range-control {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .range-seg {
            display: flex;
            background: var(--panel-alt);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 3px;
            box-shadow: var(--shadow-sm), var(--emboss);
        }

        .range-seg button {
            border: none;
            background: transparent;
            font-size: 12px;
            font-weight: 500;
            padding: 7px 14px;
            border-radius: 999px;
            cursor: pointer;
            color: var(--ink-soft);
            transition: .15s ease;
            white-space: nowrap;
        }

        .range-seg button.active {
            background: var(--ink);
            color: #fff;
        }

        .custom-dates {
            display: none;
            align-items: center;
            gap: 6px;
        }

        .custom-dates.show {
            display: flex;
        }

        .custom-dates input[type="date"] {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11.5px;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 6px 12px;
            background: var(--panel-alt);
            color: var(--ink-soft);
            box-shadow: var(--emboss);
        }

        .custom-dates span {
            color: var(--ink-faint);
            font-size: 11px;
        }

        .tilt-card {
            transform-style: preserve-3d;
            transition: transform .12s ease-out, box-shadow .25s ease;
            will-change: transform;
        }

        .metrics-mega {
            margin-bottom: 40px;
        }

        .glass {
            position: relative;
            background:
                linear-gradient(160deg, rgba(255, 255, 255, 0.62), rgba(255, 255, 255, 0.28) 55%, rgba(255, 255, 255, 0.4)),
                rgba(253, 251, 246, 0.55);
            backdrop-filter: blur(18px) saturate(160%);
            -webkit-backdrop-filter: blur(18px) saturate(160%);
            border: 1px solid rgba(255, 255, 255, 0.65);
            border-radius: var(--radius-xl);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.8) inset,
                0 -14px 30px -22px rgba(42, 36, 28, 0.25) inset,
                var(--shadow-md);
            overflow: hidden;
        }

        .glass::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(420px 200px at var(--mx, 20%) var(--my, -10%), rgba(255, 255, 255, 0.55), transparent 60%);
            opacity: 0;
            transition: opacity .25s ease;
            pointer-events: none;
        }

        .glass:hover::before {
            opacity: 1;
        }

        .glass:hover {
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.85) inset,
                0 -14px 30px -22px rgba(42, 36, 28, 0.28) inset,
                var(--shadow-lg), 0 0 46px -16px var(--glow, rgba(173, 138, 62, 0.4));
        }

        .glass-edge {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4);
        }

        .metrics-filterbar {
            padding: 16px 20px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .metrics-filterbar .search-box {
            min-width: 190px;
            background: rgba(255, 255, 255, 0.55);
        }

        .metrics-filterbar select.fselect {
            background-color: rgba(255, 255, 255, 0.55);
        }

        .mf-chipset {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .mf-label {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-faint);
            font-weight: 600;
            margin-right: 2px;
        }

        .mf-divider {
            width: 1px;
            align-self: stretch;
            background: var(--border);
            opacity: .8;
            margin: 0 2px;
        }

        .mf-reset {
            margin-left: auto;
        }

        .metrics-mega .section-heading h2 {
            font-family: 'Fraunces', serif;
        }

        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
            margin-bottom: 16px;
        }

        .kpi-tile {
            padding: 16px 18px 14px;
            --glow: rgba(173, 138, 62, 0.35);
        }

        .kpi-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .kpi-delta {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px;
            font-family: 'JetBrains Mono', monospace;
        }

        .kpi-delta.up {
            color: var(--present);
            background: var(--present-bg);
        }

        .kpi-delta.down {
            color: var(--absent);
            background: var(--absent-bg);
        }

        .kpi-delta.flat {
            color: var(--ink-faint);
            background: var(--panel);
        }

        .kpi-val {
            font-family: 'Fraunces', serif;
            font-size: 26px;
            font-weight: 500;
            line-height: 1;
            margin-bottom: 3px;
        }

        .kpi-lbl {
            font-size: 11px;
            color: var(--ink-soft);
        }

        .kpi-spark {
            margin-top: 10px;
            height: 28px;
            width: 100%;
            display: block;
        }

        .metrics-bento {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 16px;
        }

        .mtile {
            padding: 20px 22px 20px;
            display: flex;
            flex-direction: column;
        }

        .mtile.span-3 {
            grid-column: span 3;
        }

        .mtile.span-4 {
            grid-column: span 4;
        }

        .mtile.span-5 {
            grid-column: span 5;
        }

        .mtile.span-6 {
            grid-column: span 6;
        }

        .mtile.span-7 {
            grid-column: span 7;
        }

        .mtile.span-8 {
            grid-column: span 8;
        }

        .mtile.span-12 {
            grid-column: span 12;
        }

        .mtile-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
            gap: 10px;
        }

        .mtile-head h4 {
            font-family: 'Fraunces', serif;
            font-size: 15.5px;
            font-weight: 500;
            letter-spacing: -.005em;
        }

        .mtile-head .sub {
            font-size: 11px;
            color: var(--ink-soft);
            margin-top: 2px;
        }

        .mtile-badge {
            font-size: 10px;
            background: var(--gold-soft);
            color: var(--gold);
            padding: 4px 9px;
            border-radius: 999px;
            font-weight: 600;
            white-space: nowrap;
        }

        .mtile-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .mtile-foot {
            margin-top: 10px;
            font-size: 10.5px;
            color: var(--ink-faint);
            display: flex;
            justify-content: space-between;
        }

        .mlegend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 12px;
        }

        .mlegend .li {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--ink-soft);
        }

        .mlegend .sw {
            width: 8px;
            height: 8px;
            border-radius: 3px;
            flex-shrink: 0;
        }

        .donut-wrap {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .donut-wrap svg {
            flex-shrink: 0;
            filter: drop-shadow(0 3px 6px rgba(42, 36, 28, 0.08));
        }

        .donut-legend {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .donut-legend .li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11.5px;
            color: var(--ink-soft);
            cursor: default;
            padding: 2px 4px;
            border-radius: 6px;
            transition: .15s ease;
        }

        .donut-legend .li:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .donut-legend .sw {
            width: 9px;
            height: 9px;
            border-radius: 3px;
            flex-shrink: 0;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
        }

        .donut-legend b {
            color: var(--ink);
            font-family: 'JetBrains Mono', monospace;
            margin-left: auto;
            padding-left: 14px;
        }

        .heat-grid {
            display: grid;
            gap: 3px;
        }

        .heat-row {
            display: grid;
            align-items: center;
            gap: 3px;
        }

        .heat-rowlabel {
            font-size: 10.5px;
            color: var(--ink-soft);
            padding-right: 6px;
            white-space: nowrap;
        }

        .heat-cell {
            aspect-ratio: 1;
            border-radius: 4px;
            cursor: default;
            transition: transform .1s ease, filter .1s ease;
        }

        .heat-cell:hover {
            transform: scale(1.18);
            filter: brightness(1.06);
            z-index: 2;
            box-shadow: 0 2px 8px rgba(42, 36, 28, 0.25);
        }

        .heat-daylabels {
            display: grid;
            gap: 3px;
            margin-bottom: 5px;
        }

        .heat-daylabels span {
            font-size: 8.5px;
            text-align: center;
            color: var(--ink-faint);
        }

        .rank-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .rank-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rank-row .rank-n {
            width: 15px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 10.5px;
            color: var(--ink-faint);
            flex-shrink: 0;
        }

        .rank-row .rank-name {
            width: 88px;
            font-size: 11.5px;
            color: var(--ink);
            flex-shrink: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .rank-row .rank-track {
            flex: 1;
            height: 9px;
            background: var(--border-soft);
            border-radius: 999px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(42, 36, 28, 0.06);
        }

        .rank-row .rank-fill {
            height: 100%;
            border-radius: 999px;
            transition: filter .15s ease;
        }

        .rank-row:hover .rank-fill {
            filter: brightness(1.1);
        }

        .rank-row .rank-val {
            width: 40px;
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 10.5px;
            color: var(--ink-soft);
            flex-shrink: 0;
        }

        .mtable {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .mtable th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--ink-faint);
            font-weight: 600;
            padding: 0 8px 8px;
            border-bottom: 1px solid var(--border-soft);
        }

        .mtable td {
            padding: 9px 8px;
            border-bottom: 1px solid var(--border-soft);
        }

        .mtable tr:last-child td {
            border-bottom: none;
        }

        .mtable tr:hover td {
            background: rgba(255, 255, 255, 0.45);
        }

        .mtable .mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
        }

        .pct-pill {
            font-size: 10.5px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            display: inline-block;
        }

        .gauge-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .radial-wrap {
            position: relative;
            width: 88px;
            height: 88px;
            margin: 0 auto;
        }

        .radial-wrap svg {
            transform: rotate(-90deg);
        }

        .radial-val {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .radial-val .n {
            font-family: 'Fraunces', serif;
            font-size: 17px;
            font-weight: 500;
        }

        .radial-val .u {
            font-size: 8.5px;
            color: var(--ink-faint);
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .stack-bar {
            display: flex;
            width: 100%;
            height: 24px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(42, 36, 28, 0.08);
        }

        .stack-seg {
            height: 100%;
            transition: filter .15s ease, transform .15s ease;
            cursor: default;
        }

        .stack-seg:hover {
            filter: brightness(1.08);
            transform: scaleY(1.05);
        }

        .scatter-dot {
            cursor: default;
            transition: r .12s ease, filter .12s ease;
        }

        .scatter-dot:hover {
            filter: brightness(1.15);
        }

        .mtile-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--ink-faint);
            font-size: 12px;
            padding: 20px 10px;
            flex: 1;
        }

        @media (max-width: 1150px) {
            .kpi-strip {
                grid-template-columns: repeat(3, 1fr);
            }

            .mtile.span-3,
            .mtile.span-4,
            .mtile.span-5,
            .mtile.span-6,
            .mtile.span-7,
            .mtile.span-8 {
                grid-column: span 12;
            }
        }

        @media (max-width: 640px) {
            .kpi-strip {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        #chartTip {
            position: fixed;
            z-index: 80;
            display: none;
            background: var(--ink);
            color: #fff;
            border-radius: 10px;
            padding: 10px 13px;
            font-size: 11.5px;
            box-shadow: var(--shadow-lg);
            pointer-events: none;
            max-width: 220px;
        }

        #chartTip .tt-title {
            font-family: 'Fraunces', serif;
            font-size: 12.5px;
            margin-bottom: 6px;
            font-weight: 500;
        }

        #chartTip .tt-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 2px 0;
            color: rgba(255, 255, 255, 0.75);
        }

        #chartTip .tt-row b {
            color: #fff;
            font-family: 'JetBrains Mono', monospace;
        }

        #chartTip .tt-sw {
            width: 7px;
            height: 7px;
            border-radius: 2px;
            display: inline-block;
            margin-right: 6px;
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 4px;
            background: var(--panel-alt);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 5px 6px;
            box-shadow: var(--shadow-sm), var(--emboss);
        }

        .month-nav button {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: var(--ink-soft);
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s ease, color .15s ease;
        }

        .month-nav button:hover {
            background: var(--gold-soft);
            color: var(--gold);
        }

        .month-nav .label {
            font-family: 'Fraunces', serif;
            font-size: 15.5px;
            font-weight: 500;
            padding: 0 10px;
            min-width: 132px;
            text-align: center;
        }

        .pill-group {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            padding: 9px 16px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--panel-alt);
            color: var(--ink);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all .15s ease;
            box-shadow: var(--shadow-sm), var(--emboss);
        }

        .btn:hover {
            border-color: var(--gold);
            color: var(--gold);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md), 0 0 18px -8px rgba(173, 138, 62, 0.5);
        }

        .btn.today {
            background: var(--ink);
            color: #fff;
            border-color: var(--ink);
        }

        .btn.today:hover {
            opacity: .88;
            color: #fff;
            transform: translateY(-1px);
        }

        .btn.ghost {
            background: transparent;
            box-shadow: none;
        }

        .btn.primary {
            background: var(--gold);
            border-color: var(--gold);
            color: #fff;
        }

        .btn.primary:hover {
            color: #fff;
            opacity: .9;
        }

        .btn.locked {
            background: var(--unplanned-bg);
            border-color: var(--unplanned);
            color: var(--unplanned);
        }

        .btn .dot-ic {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .seg {
            display: flex;
            background: var(--panel-alt);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 3px;
            box-shadow: var(--shadow-sm), var(--emboss);
        }

        .seg button {
            border: none;
            background: transparent;
            font-size: 12.5px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 999px;
            cursor: pointer;
            color: var(--ink-soft);
            transition: .15s ease;
        }

        .seg button.active {
            background: var(--ink);
            color: #fff;
        }

        /* ---------- Filters ---------- */
        .filters {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            margin-bottom: 22px;
            box-shadow: var(--shadow-sm), var(--emboss);
        }

        .filters-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--panel-alt);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 8px 14px;
            min-width: 220px;
            flex: 1;
            box-shadow: var(--emboss);
        }

        .search-box svg {
            flex-shrink: 0;
            color: var(--ink-faint);
        }

        .search-box input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 13px;
            width: 100%;
            font-family: 'Inter', sans-serif;
            color: var(--ink);
        }

        .search-box input::placeholder {
            color: var(--ink-faint);
        }

        select.fselect {
            appearance: none;
            background: var(--panel-alt) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="6"><path d="M1 1l4 4 4-4" stroke="%238B8172" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>') no-repeat right 12px center;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 8px 30px 8px 14px;
            font-size: 12.5px;
            font-family: 'Inter', sans-serif;
            color: var(--ink-soft);
            cursor: pointer;
            min-width: 118px;
            box-shadow: var(--emboss);
        }

        select.fselect:hover {
            border-color: var(--gold);
            color: var(--ink);
        }

        .chips-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed var(--border-soft);
            align-items: center;
        }

        .chip {
            font-size: 12px;
            font-weight: 500;
            padding: 6px 13px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--panel-alt);
            color: var(--ink-soft);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: .15s ease;
            box-shadow: var(--emboss);
        }

        .chip::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--ink-faint);
        }

        .chip:hover {
            border-color: var(--gold);
        }

        .chip.active {
            background: var(--ink);
            border-color: var(--ink);
            color: #fff;
            box-shadow: 0 0 14px -4px rgba(42, 36, 28, 0.6);
        }

        .chip.active::before {
            background: #fff;
        }

        .chip.reset {
            color: var(--unplanned);
            border-style: dashed;
            margin-left: auto;
        }

        .chip.reset::before {
            display: none;
        }

        .matrix-panel {
            background: var(--panel-alt);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md), var(--emboss);
            overflow: hidden;
            margin-bottom: 26px;
        }

        .matrix-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-soft);
        }

        .matrix-topbar h3 {
            font-family: 'Fraunces', serif;
            font-size: 17px;
            font-weight: 500;
        }

        .matrix-topbar .legend {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            font-size: 11px;
            color: var(--ink-soft);
        }

        .legend .li {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .legend .sw {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .matrix-scroll {
            overflow: auto;
            max-height: 640px;
            position: relative;
        }

        table.matrix {
            border-collapse: separate;
            border-spacing: 0;
            width: max-content;
        }

        table.matrix thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: var(--panel);
            border-bottom: 1px solid var(--border);
            padding: 10px 6px;
            font-weight: 500;
            text-align: center;
            min-width: 80px;
        }

        table.matrix thead th .daynum {
            font-family: 'Fraunces', serif;
            font-size: 15px;
        }

        table.matrix thead th .wk {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--ink-faint);
            margin-top: 2px;
        }

        table.matrix thead th.weekend {
            background: #F5F1E6;
        }

        table.matrix thead th.today-col {
            background: var(--gold-soft);
        }

        table.matrix thead th.today-col .daynum {
            color: var(--gold);
        }

        table.matrix thead th.holiday-col {
            background: var(--holiday-bg);
        }

        .stick {
            position: sticky;
            z-index: 4;
            background: var(--panel-alt);
        }

        .stick-head {
            position: sticky;
            z-index: 5;
            background: var(--panel);
        }

        th.col-check,
        td.col-check {
            left: 0;
            min-width: 38px;
            width: 38px;
            text-align: center;
        }

        th.col-emp,
        td.col-emp {
            left: 38px;
            min-width: 198px;
            width: 198px;
            text-align: left;
        }

        th.col-dept,
        td.col-dept {
            left: 236px;
            min-width: 150px;
            width: 150px;
            text-align: left;
            border-right: 1px solid var(--border);
            box-shadow: 5px 0 10px -7px rgba(42, 36, 28, 0.18);
        }

        table.matrix tbody td {
            border-bottom: 1px solid var(--border-soft);
            padding: 8px 10px;
            font-size: 12.5px;
            vertical-align: middle;
        }

        table.matrix tbody tr:hover td {
            background: #FBF7EC;
        }

        table.matrix tbody tr:hover td.stick {
            background: #FBF7EC;
        }

        .emp-cell {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #E8DCC4, #D9C79E);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Fraunces', serif;
            font-weight: 500;
            font-size: 12px;
            color: #6B5A2E;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(42, 36, 28, 0.15), inset 0 1px 1px rgba(255, 255, 255, 0.6);
        }

        .emp-name {
            font-weight: 600;
            font-size: 12.6px;
            line-height: 1.25;
        }

        .emp-sub {
            font-size: 10.5px;
            color: var(--ink-faint);
            font-family: 'JetBrains Mono', monospace;
        }

        .dept-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: var(--ink-soft);
            background: var(--panel);
            border: 1px solid var(--border-soft);
            padding: 4px 10px;
            border-radius: 999px;
        }

        .dept-pill .dsw {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gold);
            flex-shrink: 0;
        }

        input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: var(--ink);
            cursor: pointer;
        }

        .dcell {
            min-width: 80px;
            text-align: center;
            cursor: pointer;
            position: relative;
            border-radius: 7px;
            padding: 6px 4px !important;
            transition: background .12s ease, box-shadow .12s ease;
        }

        .dcell:hover {
            outline: 1.5px solid var(--gold);
            outline-offset: -1px;
            box-shadow: 0 0 12px -2px rgba(173, 138, 62, 0.45);
        }

        .dcell .status-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .01em;
        }

        .dcell .hrs {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            margin-top: 2px;
            opacity: .85;
        }

        .dcell.present {
            background: var(--present-bg);
            color: var(--present);
        }

        .dcell.late {
            background: var(--late-bg);
            color: var(--late);
        }

        .dcell.halfday {
            background: var(--halfday-bg);
            color: var(--halfday);
        }

        .dcell.absent {
            background: var(--absent-bg);
            color: var(--absent);
        }

        .dcell.unplanned {
            background: var(--unplanned-bg);
            color: var(--unplanned);
        }

        .dcell.paidleave {
            background: var(--paidleave-bg);
            color: var(--paidleave);
        }

        .dcell.wfh {
            background: var(--wfh-bg);
            color: var(--wfh);
        }

        .dcell.weekoff {
            background: var(--weekoff-bg);
            color: var(--weekoff);
        }

        .dcell.holiday {
            background: var(--holiday-bg);
            color: var(--holiday);
        }

        .dcell.birthday {
            background: var(--birthday-bg);
            color: var(--birthday);
        }

        .dcell.override::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            border-width: 0 9px 9px 0;
            border-style: solid;
            border-color: transparent #A67C3D transparent transparent;
            opacity: .9;
            border-radius: 0 7px 0 0;
        }

        .dcell.today-marker {
            box-shadow: inset 0 0 0 1.5px var(--gold);
        }

        #popover {
            position: fixed;
            z-index: 60;
            display: none;
            width: 260px;
            background: var(--panel-alt);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 14px 16px;
            font-size: 12px;
            pointer-events: none;
        }

        #popover .ph-title {
            font-family: 'Fraunces', serif;
            font-size: 14.5px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        #popover .prow {
            display: flex;
            justify-content: space-between;
            padding: 3.5px 0;
            color: var(--ink-soft);
            border-bottom: 1px dashed var(--border-soft);
        }

        #popover .prow:last-child {
            border-bottom: none;
        }

        #popover .prow b {
            color: var(--ink);
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11.5px;
        }

        .bulk-bar {
            position: sticky;
            bottom: 20px;
            z-index: 20;
            display: none;
            align-items: center;
            gap: 14px;
            background: var(--ink);
            color: #fff;
            border-radius: 999px;
            padding: 10px 12px 10px 20px;
            margin: 0 auto;
            width: fit-content;
            box-shadow: var(--shadow-lg);
        }

        .bulk-bar.show {
            display: flex;
        }

        .bulk-bar .count {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .bulk-bar .bactions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .bulk-bar button {
            font-size: 12px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            padding: 7px 13px;
            border-radius: 999px;
            cursor: pointer;
            transition: .15s ease;
        }

        .bulk-bar button:hover {
            background: var(--gold);
            border-color: var(--gold);
        }

        .bulk-bar .close {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.06);
            border: none;
            color: #fff;
            cursor: pointer;
        }

        #overlay {
            position: fixed;
            inset: 0;
            background: rgba(42, 36, 28, 0.28);
            z-index: 70;
            display: none;
            backdrop-filter: blur(1px);
        }

        #drawer {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: 400px;
            background: var(--panel);
            border-left: 1px solid var(--border);
            z-index: 71;
            transform: translateX(100%);
            transition: transform .28s cubic-bezier(.4, 0, .2, 1);
            box-shadow: -12px 0 40px rgba(42, 36, 28, 0.14);
            display: flex;
            flex-direction: column;
        }

        #drawer.open {
            transform: translateX(0);
        }

        .drawer-head {
            padding: 22px 24px 16px;
            border-bottom: 1px solid var(--border-soft);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .drawer-head h2 {
            font-family: 'Fraunces', serif;
            font-size: 20px;
            font-weight: 500;
        }

        .drawer-head .eyebrow {
            margin-top: 4px;
        }

        .drawer-close {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: var(--panel-alt);
            cursor: pointer;
            color: var(--ink-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all .15s ease;
        }

        .drawer-close:hover {
            border-color: var(--gold);
            color: var(--gold);
        }

        .drawer-body {
            padding: 18px 24px;
            overflow-y: auto;
            flex: 1;
        }

        .drawer-section {
            margin-bottom: 22px;
        }

        .drawer-section h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink-faint);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .dgrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .dfield {
            background: var(--panel-alt);
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            padding: 9px 12px;
        }

        .dfield .l {
            font-size: 10px;
            color: var(--ink-faint);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 3px;
        }

        .dfield .v {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .audit-item {
            display: flex;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px dashed var(--border-soft);
            font-size: 12px;
        }

        .audit-item .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gold);
            margin-top: 5px;
            flex-shrink: 0;
        }

        .audit-item .t {
            color: var(--ink-faint);
            font-size: 10.5px;
            font-family: 'JetBrains Mono', monospace;
        }

        .drawer-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 16px 24px 24px;
            border-top: 1px solid var(--border-soft);
        }

        .drawer-actions button {
            flex: 1 1 45%;
        }

        .notes-box {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font-family: 'Inter', sans-serif;
            font-size: 12.5px;
            color: var(--ink);
            resize: vertical;
            min-height: 60px;
            background: var(--panel-alt);
        }

        @media (max-width: 1150px) {
            .gauge-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
