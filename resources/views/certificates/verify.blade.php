<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Certificate verification — {{ $code }}</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0e0c16; color: #ede9f7; font-family: 'Manrope', system-ui, sans-serif; padding: 24px; }
  .card { max-width: 460px; width: 100%; background: #171425; border: 1px solid rgba(255,255,255,.09); border-radius: 16px; padding: 40px; text-align: center; }
  .brand { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 30px; }
  .brand .mark { width: 24px; height: 24px; border-radius: 7px; background: linear-gradient(135deg,#7c5cff,#22d3ee); transform: rotate(10deg); }
  .brand .name { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 18px; }
  .badge { width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
  .badge.ok { background: rgba(63,219,160,.14); color: #3fdba0; border: 1px solid rgba(63,219,160,.3); }
  .badge.no { background: rgba(255,127,163,.14); color: #ff7fa3; border: 1px solid rgba(255,127,163,.3); }
  h1 { font-family: 'Space Grotesk', sans-serif; font-size: 20px; margin: 0 0 8px; }
  p.dek { color: #a9a3c2; font-size: 14.5px; margin: 0 0 26px; line-height: 1.6; }
  .facts { text-align: left; background: #1e1a2e; border-radius: 10px; padding: 18px 20px; display: grid; gap: 10px; }
  .facts div span.label { display: block; font-family: 'JetBrains Mono', monospace; font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: #766f92; margin-bottom: 2px; }
  .facts div span.value { font-size: 14px; font-weight: 600; }
  .code-line { margin-top: 22px; font-family: 'JetBrains Mono', monospace; font-size: 12px; color: #766f92; }
</style>
</head>
<body>
  <div class="card">
    <div class="brand"><span class="mark"></span><span class="name">undek</span></div>

    @if ($certificate)
      <div class="badge ok">&#10003;</div>
      <h1>Certificate verified</h1>
      <p class="dek">This document was issued by Undek and matches our records.</p>
      <div class="facts">
        <div><span class="label">Document type</span><span class="value">{{ \App\Models\Certificate::TYPES[$certificate->type] ?? $certificate->type }}</span></div>
        <div><span class="label">Issued to</span><span class="value">{{ $certificate->snapshot['name'] ?? $certificate->user->name }}</span></div>
        <div><span class="label">Issued on</span><span class="value">{{ $certificate->issued_at->format('F j, Y') }}</span></div>
      </div>
    @else
      <div class="badge no">&times;</div>
      <h1>Not found</h1>
      <p class="dek">We couldn't find a certificate matching this code. Double-check it was copied correctly, or contact the issuer.</p>
    @endif

    <div class="code-line">{{ $code }}</div>
  </div>
</body>
</html>
