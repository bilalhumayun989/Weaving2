<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $printTitle }} · Weaving ERP</title>
  <style>
    :root {
      --ink: #0e2429;
      --sub: #526870;
      --brand: #1b4444;
      --line: #d8e5e3;
      --bg: #f4f7f7;
      --lime: #d6ff3a;
    }
    * { box-sizing: border-box; }
    body {
      max-width: 860px;
      margin: 28px auto;
      padding: 24px 28px;
      font: 13px/1.5 Arial, Helvetica, sans-serif;
      color: var(--ink);
    }

    /* ── Toolbar (screen only) ── */
    .toolbar {
      display: flex;
      gap: 10px;
      padding-bottom: 20px;
      margin-bottom: 20px;
      border-bottom: 1px solid var(--line);
    }
    .toolbar button, .toolbar a {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: var(--brand);
      color: #fff;
      border: 0;
      border-radius: 7px;
      padding: 9px 14px;
      font: inherit;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      font-size: 13px;
    }
    .toolbar a.back {
      background: #fff;
      color: var(--brand);
      border: 1px solid var(--line);
    }

    /* ── Print header ── */
    .print-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 3px solid var(--brand);
      padding-bottom: 16px;
      margin-bottom: 24px;
    }
    .print-brand-block {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .print-logo {
      width: 38px;
      height: 38px;
      background: var(--lime);
      color: var(--brand);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: 800;
      border-radius: 8px;
      flex-shrink: 0;
    }
    .print-brand {
      font-size: 18px;
      font-weight: 800;
      color: var(--brand);
      display: block;
    }
    .print-caption {
      font-size: 11px;
      color: var(--sub);
      display: block;
      margin-top: 2px;
    }
    .print-head-right {
      text-align: right;
    }
    .print-head-right h1 {
      font-size: 20px;
      font-weight: 800;
      color: var(--brand);
      margin: 0 0 4px;
    }
    .print-head-right .print-date {
      font-size: 11px;
      color: var(--sub);
    }

    /* ── Sections & tables ── */
    h2 {
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--brand);
      border-bottom: 1px solid var(--line);
      padding-bottom: 6px;
      margin: 22px 0 12px;
    }
    h2:first-child { margin-top: 0; }

    .meta {
      background: var(--bg);
      border: 1px solid var(--line);
      border-radius: 7px;
      padding: 14px 16px;
      margin: 0 0 16px;
    }
    .cols {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    .meta p {
      margin: 3px 0;
      font-size: 12.5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin: 0 0 16px;
      font-size: 12.5px;
    }
    th {
      text-align: left;
      background: var(--bg);
      border-bottom: 2px solid var(--line);
      padding: 8px 9px;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: var(--sub);
    }
    td {
      padding: 8px 9px;
      border-bottom: 1px solid #e8eeec;
      vertical-align: top;
    }
    tr:last-child td { border-bottom: 0; }
    .right { text-align: right; }
    .total-row td, .total-row th {
      background: #eaf2f0;
      font-weight: 700;
      border-top: 2px solid var(--line);
    }
    .muted { color: var(--sub); }

    /* ── Signature footer ── */
    .print-foot {
      margin-top: 56px;
      display: flex;
      justify-content: space-between;
      gap: 30px;
    }
    .sig {
      border-top: 1px solid #b6c6c4;
      padding-top: 8px;
      min-width: 180px;
      text-align: center;
      font-size: 12px;
      color: var(--sub);
    }

    /* ── Print media ── */
    @page { size: A4; margin: 16mm; }
    @media print {
      body { padding: 0; margin: 0; max-width: none; }
      .toolbar { display: none; }
      .meta, th { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
  </style>
</head>
<body>

  <div class="toolbar">
    <button onclick="window.print()">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print / Save PDF
    </button>
    <a href="{{ url()->previous() }}" class="back">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
  </div>

  <div class="print-head">
    <div class="print-brand-block">
      <div class="print-logo">W</div>
      <div>
        <span class="print-brand">WEAVING ERP</span>
        <span class="print-caption">One business &middot; Factory identified below</span>
      </div>
    </div>
    <div class="print-head-right">
      <h1>{{ $printTitle }}</h1>
      <div class="print-date">Printed: {{ now()->format('d M Y, h:i A') }}</div>
    </div>
  </div>

  @yield('document')

  <div class="print-foot">
    <div class="sig">Prepared by</div>
    <div class="sig">Received / Verified by</div>
  </div>

</body>
</html>
