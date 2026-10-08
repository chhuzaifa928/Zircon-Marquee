<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; margin: 0; }
        .wrap { padding: 22px 26px; }
        .header { border-bottom: 2px solid #b8860b; padding-bottom: 8px; margin-bottom: 8px; }
        .company { font-size: 18px; font-weight: bold; color: #8a6400; }
        .company-meta { font-size: 9px; color: #555; margin-top: 2px; }
        .doc-title { text-align: center; font-size: 13px; font-weight: bold; letter-spacing: 1px;
            text-transform: uppercase; margin: 8px 0 2px; }
        .range { text-align: center; font-size: 10px; color: #444; margin-bottom: 10px; }
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.grid th, table.grid td { border: 1px solid #ccc; padding: 3px 5px; }
        table.grid th { background: #f3ecd9; font-weight: bold; text-align: left; }
        .num { text-align: right; }
        .section-head { background: #efe6cc; font-weight: bold; }
        .subtotal td { background: #f7f2e3; font-weight: bold; }
        .grand td { background: #8a6400; color: #fff; font-weight: bold; }
        .muted { color: #777; }
        .foot { margin-top: 12px; font-size: 8px; color: #888; text-align: center;
            border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="company">{{ $settings->company_name ?? 'Zircon Marquee' }}</div>
        <div class="company-meta">
            {{ $settings->company_address }}@if($settings->company_phone) · {{ $settings->company_phone }}@endif
        </div>
    </div>
    <div class="doc-title">{{ $title }}</div>
    <div class="range">
        {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} — {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}
    </div>

    @yield('body')

    <div class="foot">
        Generated {{ now()->format('d M Y H:i') }}@if($generatedBy) by {{ $generatedBy }}@endif · Built by Khan Group of Technologies
    </div>
</div>
</body>
</html>
