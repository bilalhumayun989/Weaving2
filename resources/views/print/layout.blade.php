<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $printTitle }} · Weaving ERP</title>
<style>
:root{font-family:Arial,Helvetica,sans-serif;color:#122d36}*{box-sizing:border-box}body{max-width:850px;margin:30px auto;padding:20px;font-size:13px;line-height:1.45}
.print-head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #244d4e;padding-bottom:14px;margin-bottom:24px}.brand{font-size:21px;font-weight:800;color:#244d4e}.caption{font-size:12px;color:#667b80}h1{font-size:20px;margin:0 0 10px}.cols{display:grid;grid-template-columns:1fr 1fr;gap:15px}.meta{background:#f4f7f7;padding:14px;margin:12px 0;border-radius:6px}.meta p{margin:3px 0}.total{background:#eaf2f0;padding:14px;font-size:18px;text-align:right;font-weight:800;margin:20px 0}table{width:100%;border-collapse:collapse;margin:14px 0}th,td{padding:9px 7px;text-align:left;border-bottom:1px solid #d8e3e2}th{background:#eaf2f0;font-size:12px}.right{text-align:right}.muted{color:#687d80}.foot{margin-top:48px;display:flex;justify-content:space-between;gap:30px}.sig{border-top:1px solid #b6c6c4;padding-top:8px;min-width:170px;text-align:center}.toolbar{padding:10px 0 20px;display:flex;gap:10px}.toolbar button,.toolbar a{background:#204c4c;color:white;text-decoration:none;border:0;border-radius:6px;padding:9px 13px;cursor:pointer}
@page{size:A4;margin:17mm}@media print{body{padding:0;margin:0;max-width:none}.toolbar{display:none}.meta{print-color-adjust:exact}th{print-color-adjust:exact}}
</style></head><body>
<div class="toolbar"><button onclick="window.print()">Print / Save PDF</button><a href="{{ url()->previous() }}">Back</a></div>
<div class="print-head"><div><div class="brand">TEXTILE WEAVING ERP</div><div class="caption">One business · Factory identified below</div></div><div class="right"><h1>{{ $printTitle }}</h1><div class="caption">Printed: {{ now()->format('d M Y, h:i A') }}</div></div></div>
@yield('document')
<div class="foot"><div class="sig">Prepared by</div><div class="sig">Received / Verified by</div></div>
</body></html>
