<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('code') — فنانة فون</title>
    <style>
        :root { color-scheme: light dark; font-family: "Tajawal", "Segoe UI", Tahoma, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; color: #0f172a; background:
            radial-gradient(circle at 80% 10%, rgba(37,99,235,.15), transparent 32%),
            radial-gradient(circle at 10% 90%, rgba(14,165,233,.11), transparent 34%), #f8fafc; }
        .page { min-height: 100vh; display: grid; place-items: center; padding: 28px; }
        .card { width: min(680px, 100%); border: 1px solid rgba(148,163,184,.3); border-radius: 28px; background: rgba(255,255,255,.88); box-shadow: 0 30px 80px rgba(15,23,42,.12); backdrop-filter: blur(18px); padding: clamp(28px, 6vw, 56px); text-align: center; }
        .mark { width: 72px; height: 72px; margin: 0 auto 24px; display: grid; place-items: center; border-radius: 22px; color: white; background: linear-gradient(135deg,#1d4ed8,#0284c7); box-shadow: 0 18px 38px rgba(37,99,235,.25); }
        .code { margin: 0; font-size: clamp(54px, 12vw, 92px); line-height: 1; letter-spacing: -.05em; color: #1d4ed8; }
        h1 { margin: 18px 0 10px; font-size: clamp(24px, 5vw, 34px); }
        p { margin: 0 auto; max-width: 520px; color: #64748b; font-size: 16px; line-height: 1.9; }
        .actions { margin-top: 30px; display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
        a, button { appearance: none; border: 0; border-radius: 14px; padding: 12px 20px; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
        .primary { background: #2563eb; color: white; box-shadow: 0 10px 24px rgba(37,99,235,.23); }
        .secondary { color: #334155; background: #eef2f7; }
        .brand { margin-top: 30px; color: #94a3b8; font-size: 13px; }
        @media (prefers-color-scheme: dark) {
            body { color: #f8fafc; background: radial-gradient(circle at 80% 10%, rgba(37,99,235,.2), transparent 32%), #07111f; }
            .card { background: rgba(15,23,42,.88); border-color: rgba(71,85,105,.65); box-shadow: 0 30px 80px rgba(0,0,0,.34); }
            p { color: #94a3b8; }
            .secondary { color: #e2e8f0; background: #1e293b; }
        }
    </style>
</head>
<body>
<main class="page">
    <section class="card">
        <div class="mark" aria-hidden="true">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6.5" y="2.5" width="11" height="19" rx="2.5"/><path d="M10 18.5h4M9.5 5.5h5"/></svg>
        </div>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a class="primary" href="{{ url('/') }}">العودة إلى الصفحة الرئيسية</a>
            <button class="secondary" type="button" onclick="history.back()">العودة للصفحة السابقة</button>
        </div>
        <div class="brand">فنانة فون — إدارة متكاملة لمعرض الهواتف</div>
    </section>
</main>
</body>
</html>
