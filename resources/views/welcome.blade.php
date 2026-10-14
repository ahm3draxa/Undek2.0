<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
  $seoTitle = 'Undek – Web Development, SEO & IT Support Agency | Pakistan';
  $seoDescription = 'Undek is a full-stack digital studio: web development, SEO, IT support, graphic design and video editing. One accountable team with milestones you can track.';
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<link rel="canonical" href="{{ url('/') }}">
<meta name="theme-color" content="#08080c">
<link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}?v=2">
<link rel="apple-touch-icon" href="{{ asset('images/logo-512.png') }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="Undek">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ url('/') }}">
<meta property="og:image" content="{{ asset('images/og-image.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Undek — Design. Build. Grow.">
<meta property="og:locale" content="en_US">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ProfessionalService',
    'name' => 'Undek',
    'url' => url('/'),
    'logo' => asset('images/logo-512.png'),
    'image' => asset('images/og-image.png'),
    'description' => $seoDescription,
    'email' => 'hello@undek.com',
    'sameAs' => [
        'https://www.facebook.com/profile.php?id=100064091621113',
        'https://www.instagram.com/undekofficial/',
        'https://www.youtube.com/@undek.official',
        'https://www.linkedin.com/company/144573084/',
    ],
    'hasOfferCatalog' => [
        '@type' => 'OfferCatalog',
        'name' => 'Services',
        'itemListElement' => $services->map(fn ($service) => [
            '@type' => 'Offer',
            'itemOffered' => ['@type' => 'Service', 'name' => $service->name, 'description' => $service->description],
        ])->values(),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
</script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>

*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:#08080c;color:#f5f5fa;font-family:'Manrope',system-ui,sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden}
a{color:#22d3ee;text-decoration:none}
a:hover{color:#7cf0ff}
::selection{background:#7c5cff;color:#fff}
@keyframes riseIn{from{opacity:0;transform:translateY(42px)}to{opacity:1;transform:none}}
@keyframes floaty{0%,100%{transform:translateY(0)}50%{transform:translateY(-16px)}}
@keyframes marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}
@keyframes pulseDot{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.35;transform:scale(.8)}}
@keyframes spinSlow{to{transform:rotate(360deg)}}


/* ═══════════════════════════════════════════
   ROBOTIC LAYER — PAGE-SWITCH ANIMATIONS
   ═══════════════════════════════════════════ */

/* 1. Boot-wipe overlay (8 teal bars) */
#page-wipe {
  position: fixed;
  inset: 0;
  z-index: 9000;
  pointer-events: none;
}
#page-wipe .bar {
  position: absolute;
  left: 0; right: 0;
  height: 12.5%;
  background: #22d3ee;
  transform: scaleX(0);
  transform-origin: left center;
}
#page-wipe .bar:nth-child(even) {
  transform-origin: right center;
}

/* 2. Global scan-line texture */
body::after {
  content: '';
  position: fixed;
  inset: 0;
  background: repeating-linear-gradient(
    0deg,
    transparent,
    transparent 3px,
    rgba(0,0,0,0.03) 3px,
    rgba(0,0,0,0.03) 4px
  );
  pointer-events: none;
  z-index: 8000;
}

/* 3. HUD corner brackets */
.hud-corner {
  position: fixed;
  width: 18px; height: 18px;
  pointer-events: none;
  z-index: 200;
  opacity: 0.25;
}
.hud-corner::before, .hud-corner::after {
  content: ''; position: absolute; background: #22d3ee;
}
.hud-corner::before { width: 2px; height: 100%; top:0; left:0; }
.hud-corner::after  { width: 100%; height: 2px; top:0; left:0; }
.hud-tl { top:14px; left:14px; }
.hud-tr { top:14px; right:14px; transform: scaleX(-1); }
.hud-bl { bottom:14px; left:14px; transform: scaleY(-1); }
.hud-br { bottom:14px; right:14px; transform: scale(-1,-1); }

/* 4. HUD status strip */
#hud-status {
  position: fixed;
  bottom: 14px;
  left: 50%;
  transform: translateX(-50%);
  font-family: 'JetBrains Mono', monospace;
  font-size: 10px;
  color: #22d3ee;
  opacity: 0.3;
  letter-spacing: 0.14em;
  z-index: 200;
  pointer-events: none;
  white-space: nowrap;
}

/* 5. Section glitch-reveal: elements with data-reveal start hidden */
[data-reveal] {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1);
}
[data-reveal].revealed {
  opacity: 1;
  transform: none;
}

/* 6. Robotic section-entry sweep mask */
.robo-section {
  position: relative;
  overflow: hidden;
}
.robo-section::before {
  content: '';
  position: absolute;
  inset: 0;
  background: #08080c;
  z-index: 50;
  transform: translateX(0%);
  transition: transform 0.7s cubic-bezier(0.77,0,0.175,1);
  pointer-events: none;
}
.robo-section.unmasked::before {
  transform: translateX(101%);
}

/* 7. Logo glitch on hover */
.logo-glyph {
  position: relative;
  display: inline-block;
}
.logo-glyph:hover::before {
  content: attr(data-text);
  position: absolute; left: 2px; top: 0;
  color: #ff5da2;
  clip-path: polygon(0 30%, 100% 30%, 100% 55%, 0 55%);
  animation: gclip 0.2s steps(1) infinite;
}
.logo-glyph:hover::after {
  content: attr(data-text);
  position: absolute; left: -2px; top: 0;
  color: #22d3ee;
  clip-path: polygon(0 60%, 100% 60%, 100% 80%, 0 80%);
  animation: gclip 0.25s steps(1) 0.04s infinite;
}
@keyframes gclip {
  0%  { clip-path: polygon(0 0%,100% 0%,100% 20%,0 20%); }
  33% { clip-path: polygon(0 45%,100% 45%,100% 65%,0 65%); }
  66% { clip-path: polygon(0 75%,100% 75%,100% 95%,0 95%); }
}

/* Canvas behind everything */
#hero-canvas {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  opacity: .9;
}

@media (prefers-reduced-motion: reduce) {
  #page-wipe, .robo-section::before, body::after, .hud-corner, #hud-status { display:none; }
  [data-reveal] { opacity:1 !important; transform:none !important; }
}

/* ═══════════════════════════════════════════
   TERMINAL MOCKUP + TECH-STACK STRIP
   ═══════════════════════════════════════════ */
.terminal-window {
  position: absolute;
  right: 40px;
  bottom: 64px;
  width: 380px;
  max-width: 38vw;
  border-radius: 14px;
  background: #0c0c12;
  border: 1px solid rgba(255,255,255,.1);
  box-shadow: 0 30px 80px rgba(0,0,0,.55), 0 0 0 1px rgba(124,92,255,.06);
  overflow: hidden;
  z-index: 3;
  animation: riseIn 1s cubic-bezier(.16,1,.3,1) both;
  animation-delay: .9s;
}
.terminal-titlebar {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 10px 14px;
  background: rgba(255,255,255,.03);
  border-bottom: 1px solid rgba(255,255,255,.07);
}
.terminal-dot { width: 9px; height: 9px; border-radius: 50%; display: block; }
.terminal-titlebar span.label {
  margin-left: 6px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px;
  color: #6e6e82;
  letter-spacing: .04em;
}
.terminal-body {
  padding: 16px 18px 20px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 12.5px;
  line-height: 1.85;
  color: #b7f7c8;
  min-height: 190px;
  white-space: pre-wrap;
  word-break: break-word;
}
.term-cursor {
  display: inline-block;
  width: 7px;
  background: #34e0a1;
  margin-left: 2px;
  animation: pulseDot 1s steps(2) infinite;
}
.stack-pill {
  padding: 8px 16px;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,.1);
  background: rgba(255,255,255,.02);
  color: #9b9bb0;
  font-family: 'JetBrains Mono', monospace;
  font-size: 12.5px;
  letter-spacing: .02em;
  transition: .25s ease;
}
.stack-pill:hover {
  border-color: rgba(34,211,238,.4);
  color: #e6fbff;
  background: rgba(34,211,238,.06);
  transform: translateY(-2px);
}
.code-preview {
  border-radius: 10px;
  background: #0c0c12;
  border: 1px solid rgba(255,255,255,.08);
  padding: 12px 14px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 11.5px;
  line-height: 1.7;
  color: #8f8fa8;
  overflow: hidden;
}
.code-preview .kw { color: #ff9ec7; }
.code-preview .fn { color: #22d3ee; }
.code-preview .cl { color: #7c5cff; }
.code-preview .cm { color: #5c5c72; }

@media (max-width: 1100px) {
  .terminal-window { display: none; }
}

/* ═══════════════════════════════════════════
   MOBILE / SMALL-SCREEN LAYOUT
   ═══════════════════════════════════════════ */
.nav-toggle-btn { display: none; }
.nav-cta-in-menu { display: none; }

@media (max-width: 860px) {
  .site-nav { padding: 14px 20px !important; }
  .nav-links {
    display: none !important;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 2px !important;
    background: #0b0b12;
    border-bottom: 1px solid rgba(255,255,255,.08);
    box-shadow: 0 20px 40px rgba(0,0,0,.4);
    padding: 10px 20px 20px;
    max-height: calc(100vh - 64px);
    overflow-y: auto;
  }
  .nav-links.open { display: flex !important; }
  .nav-links a { padding: 13px 4px; border-bottom: 1px solid rgba(255,255,255,.05); }
  .nav-cta-desktop { display: none !important; }
  .nav-cta-in-menu { display: block !important; margin-top: 12px; text-align: center; }
  .nav-toggle-btn { display: flex !important; }
}

@media (max-width: 640px) {
  .pad-x { padding-left: 20px !important; padding-right: 20px !important; }
  .hero-header { min-height: auto !important; padding-top: 108px !important; padding-bottom: 64px !important; }
  .section-pad { padding-top: 72px !important; padding-bottom: 72px !important; }
  .footer-grid { grid-template-columns: 1fr !important; gap: 34px !important; }
  .hero-cta-row a, .contact-cta-row a { text-align: center; justify-content: center; }
}

@media (max-width: 420px) {
  h1 { font-size: clamp(40px, 13vw, 132px) !important; }
}
</style>
</head>
<body>

<!-- HUD brackets -->
<div class="hud-corner hud-tl"></div>
<div class="hud-corner hud-tr"></div>
<div class="hud-corner hud-bl"></div>
<div class="hud-corner hud-br"></div>
<div id="hud-status">SYS::UNDEK_v2.0 &nbsp;■&nbsp; ONLINE &nbsp;■&nbsp; <span id="hud-clk"></span></div>

<!-- Boot-wipe bars -->
<div id="page-wipe">
  <div class="bar" style="top:0%"></div>
  <div class="bar" style="top:12.5%"></div>
  <div class="bar" style="top:25%"></div>
  <div class="bar" style="top:37.5%"></div>
  <div class="bar" style="top:50%"></div>
  <div class="bar" style="top:62.5%"></div>
  <div class="bar" style="top:75%"></div>
  <div class="bar" style="top:87.5%"></div>
</div>



<div style="position:relative">

<nav class="site-nav" style="position:sticky;top:0;z-index:60;display:flex;align-items:center;justify-content:space-between;padding:16px 40px;backdrop-filter:blur(16px);background:rgba(8,8,12,0.6);border-bottom:1px solid rgba(255,255,255,0.07)">
  <a href="#top" style="display:flex;align-items:center">
    <x-logo-lockup glow style="font-size:21px;color:#f5f5fa;letter-spacing:-.02em" />
  </a>
  <div class="nav-links" id="nav-links" style="display:flex;gap:32px;align-items:center;font-size:15px;font-weight:500">
    <a href="#services" style="color:#c7c7d6">Services</a>
    <a href="#work" style="color:#c7c7d6">Work</a>
    <a href="#process" style="color:#c7c7d6">Process</a>
    @if ($team->isNotEmpty())
      <a href="#team" style="color:#c7c7d6">Team</a>
    @endif
    <a href="#contact" style="color:#c7c7d6">About</a>
    @auth
      <a href="{{ url('/dashboard') }}" style="color:#c7c7d6">Dashboard</a>
    @else
      <a href="{{ route('login') }}" style="color:#c7c7d6">Log in</a>
    @endauth
    <a href="#contact" class="nav-cta-in-menu" style="padding:12px 22px;border-radius:999px;background:linear-gradient(120deg,#7c5cff,#22d3ee);color:#08080c;font-weight:700;font-family:'Space Grotesk';font-size:15px;border-bottom:none !important">Get a quote</a>
  </div>
  <a href="#contact" class="nav-cta-desktop" style="padding:11px 22px;border-radius:999px;background:linear-gradient(120deg,#7c5cff,#22d3ee);color:#08080c;font-weight:700;font-family:'Space Grotesk';font-size:15px;box-shadow:0 6px 24px rgba(124,92,255,.35);transition:.3s" style-hover="transform:translateY(-2px);box-shadow:0 10px 32px rgba(124,92,255,.5)">Get a quote</a>
  <button id="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="nav-links" class="nav-toggle-btn" style="align-items:center;justify-content:center;width:42px;height:42px;flex-shrink:0;border-radius:10px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.04);cursor:pointer">
    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#f5f5fa" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
</nav>

<header id="top" class="hero-header pad-x" style="position:relative;overflow:hidden;padding:130px 40px 120px;min-height:90vh;display:flex;align-items:center">
  <canvas id="hero-canvas" style="position:absolute;inset:0;width:100%;height:100%;z-index:0;opacity:.9"></canvas>
  <div style="position:absolute;inset:0;z-index:1;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:64px 64px;mask-image:radial-gradient(ellipse 70% 60% at 40% 40%,#000 20%,transparent 75%);-webkit-mask-image:radial-gradient(ellipse 70% 60% at 40% 40%,#000 20%,transparent 75%)"></div>
  <div style="position:absolute;inset:0;z-index:1;background:radial-gradient(ellipse 90% 80% at 50% 120%,rgba(8,8,12,0) 40%,#08080c 80%)"></div>

  <div style="position:relative;z-index:3;max-width:1240px;margin:0 auto;width:100%">
    <div style="display:inline-flex;align-items:center;gap:10px;padding:8px 15px;border:1px solid rgba(255,255,255,.12);border-radius:999px;font-family:'JetBrains Mono';font-size:12px;letter-spacing:.14em;color:#a9a9c0;margin-bottom:30px;animation:riseIn .8s ease both">
      <span style="width:7px;height:7px;border-radius:50%;background:#34e0a1;box-shadow:0 0 10px #34e0a1;animation:pulseDot 2s infinite"></span>
      FULL-STACK DIGITAL STUDIO
    </div>
    <h1 style="font-family:'Space Grotesk';font-weight:700;font-size:clamp(54px,9.5vw,132px);line-height:.9;letter-spacing:-.04em;margin:0 0 30px;max-width:16ch">
      <span style="display:inline-block;background:linear-gradient(120deg,#ff5da2,#7c5cff);-webkit-background-clip:text;background-clip:text;color:transparent;animation:riseIn .9s cubic-bezier(.16,1,.3,1) both;animation-delay:.12s">Design.</span>
      <span style="display:inline-block;background:linear-gradient(120deg,#22d3ee,#4d8bff);-webkit-background-clip:text;background-clip:text;color:transparent;animation:riseIn .9s cubic-bezier(.16,1,.3,1) both;animation-delay:.26s">Build.</span>
      <span style="display:inline-block;background:linear-gradient(120deg,#34e0a1,#22d3ee);-webkit-background-clip:text;background-clip:text;color:transparent;animation:riseIn .9s cubic-bezier(.16,1,.3,1) both;animation-delay:.4s">Grow.</span>
    </h1>
    <p style="font-size:clamp(17px,2vw,21px);line-height:1.55;color:#b6b6c8;max-width:56ch;margin:0 0 40px;animation:riseIn .9s ease both;animation-delay:.5s">
      undek is a full-stack digital studio — web development, SEO, IT support, graphic design and video, engineered under one roof. One team, every service, measurable results.
    </p>
    <div class="hero-cta-row" style="display:flex;flex-wrap:wrap;gap:14px;align-items:center;margin-bottom:44px;animation:riseIn .9s ease both;animation-delay:.6s">
      <a href="#contact" style="padding:16px 30px;border-radius:999px;background:linear-gradient(120deg,#7c5cff,#22d3ee);color:#08080c;font-weight:700;font-family:'Space Grotesk';font-size:16px;box-shadow:0 10px 34px rgba(124,92,255,.4);transition:.3s" style-hover="transform:translateY(-3px);box-shadow:0 16px 44px rgba(124,92,255,.55)">Start your project →</a>
      <a href="#services" style="padding:16px 30px;border-radius:999px;border:1px solid rgba(255,255,255,.18);color:#f5f5fa;font-weight:600;font-size:16px;transition:.3s" style-hover="border-color:rgba(255,255,255,.4);background:rgba(255,255,255,.05)">Explore services</a>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:10px;animation:riseIn .9s ease both;animation-delay:.72s">
      <span style="padding:7px 15px;border-radius:999px;background:rgba(34,211,238,.09);border:1px solid rgba(34,211,238,.25);color:#7fe8f5;font-size:13px;font-family:'JetBrains Mono'">Web Development</span>
      <span style="padding:7px 15px;border-radius:999px;background:rgba(52,224,161,.09);border:1px solid rgba(52,224,161,.25);color:#7fecc4;font-size:13px;font-family:'JetBrains Mono'">SEO</span>
      <span style="padding:7px 15px;border-radius:999px;background:rgba(91,140,255,.09);border:1px solid rgba(91,140,255,.25);color:#a6bcff;font-size:13px;font-family:'JetBrains Mono'">IT Support</span>
      <span style="padding:7px 15px;border-radius:999px;background:rgba(255,93,162,.09);border:1px solid rgba(255,93,162,.25);color:#ff9ec7;font-size:13px;font-family:'JetBrains Mono'">Graphic Design</span>
      <span style="padding:7px 15px;border-radius:999px;background:rgba(255,176,32,.09);border:1px solid rgba(255,176,32,.25);color:#ffcf7a;font-size:13px;font-family:'JetBrains Mono'">Video Editing</span>
    </div>
    <div style="display:flex;align-items:center;gap:9px;margin-top:26px;animation:riseIn .9s ease both;animation-delay:.84s">
      <span style="width:8px;height:8px;border-radius:50%;background:#34e0a1;box-shadow:0 0 10px #34e0a1;animation:pulseDot 2s infinite"></span>
      <span style="font-family:'JetBrains Mono';font-size:13px;letter-spacing:.06em;color:#a9a9c0">24/7 Support — a real human or our assistant is always reachable</span>
    </div>
  </div>

  <div class="terminal-window">
    <div class="terminal-titlebar">
      <span class="terminal-dot" style="background:#ff5f56"></span>
      <span class="terminal-dot" style="background:#ffbd2e"></span>
      <span class="terminal-dot" style="background:#27c93f"></span>
      <span class="label">zsh — undek/deploy</span>
    </div>
    <div class="terminal-body" id="term-body"></div>
  </div>
</header>

<div style="position:relative;z-index:3;padding:14px 0;border-top:1px solid rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.06);overflow:hidden;background:rgba(255,255,255,.01)">
  <div style="display:flex;gap:56px;width:max-content;animation:marquee 26s linear infinite;font-family:'Space Grotesk';font-weight:600;font-size:15px;color:#5c5c72;white-space:nowrap">
    <span>PIXEL-PERFECT BUILDS</span><span style="color:#7c5cff">✦</span><span>SEARCH THAT RANKS</span><span style="color:#22d3ee">✦</span><span>24/7 IT SUPPORT</span><span style="color:#34e0a1">✦</span><span>BRAND-GRADE DESIGN</span><span style="color:#ff5da2">✦</span><span>SCROLL-STOPPING VIDEO</span><span style="color:#ffb020">✦</span>
    <span>PIXEL-PERFECT BUILDS</span><span style="color:#7c5cff">✦</span><span>SEARCH THAT RANKS</span><span style="color:#22d3ee">✦</span><span>24/7 IT SUPPORT</span><span style="color:#34e0a1">✦</span><span>BRAND-GRADE DESIGN</span><span style="color:#ff5da2">✦</span><span>SCROLL-STOPPING VIDEO</span><span style="color:#ffb020">✦</span>
  </div>
</div>

<div class="pad-x" style="position:relative;z-index:3;padding:44px 40px 40px;text-align:center">
  <div style="font-family:'JetBrains Mono';font-size:11px;letter-spacing:.18em;color:#5c5c72;margin-bottom:18px">// BUILT ON A REAL STACK, NOT A TEMPLATE</div>
  <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;max-width:820px;margin:0 auto">
    <span class="stack-pill">PHP</span>
    <span class="stack-pill">Laravel</span>
    <span class="stack-pill">MySQL</span>
    <span class="stack-pill">JavaScript</span>
    <span class="stack-pill">Tailwind CSS</span>
    <span class="stack-pill">Alpine.js</span>
    <span class="stack-pill">REST APIs</span>
    <span class="stack-pill">Git / CI</span>
    <span class="stack-pill">Linux</span>
  </div>
</div>

<section id="services" class="robo-section pad-x section-pad" style="padding:120px 40px">
  <div style="max-width:1240px;margin:0 auto">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:24px;margin-bottom:56px">
      <div data-reveal="0" style="transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1)">
        <div style="font-family:'JetBrains Mono';font-size:12px;letter-spacing:.18em;color:#7c5cff;margin-bottom:16px">// WHAT WE DO</div>
        <h2 style="font-family:'Space Grotesk';font-weight:700;font-size:clamp(34px,5vw,60px);line-height:1;letter-spacing:-.03em;margin:0;max-width:16ch">Every service. One accountable team.</h2>
      </div>
      <p data-reveal="120" style="transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1);max-width:34ch;color:#9b9bb0;font-size:16px;line-height:1.6;margin:0">Every service is modular — mix what you need today, add more as you grow. New offerings can be plugged in anytime.</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px">

      @php
        $accents = ['#22d3ee', '#34e0a1', '#5b8cff', '#ff5da2', '#ffb020', '#7c5cff'];
        $delays = [0, 80, 160, 0, 80, 160];
      @endphp

      @foreach ($services as $i => $service)
        @php $accent = $accents[$i % count($accents)]; @endphp
        <a href="{{ route('quote-requests.create') }}?service={{ $service->slug }}" data-reveal="{{ $delays[$i % count($delays)] }}" style="position:relative;display:flex;flex-direction:column;gap:18px;padding:32px;border-radius:22px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .35s ease,border-color .35s ease,background .35s ease;overflow:hidden" style-hover="transform:translateY(-8px);border-color:{{ $accent }}80;background:{{ $accent }}0a">
          <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,{{ $accent }},transparent)"></div>
          <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div style="width:52px;height:52px;border-radius:14px;background:{{ $accent }}24;border:1px solid {{ $accent }}4d;display:flex;align-items:center;justify-content:center;color:{{ $accent }}">
              <x-icon :name="$service->icon" class="w-6 h-6" />
            </div>
            <span style="font-family:'JetBrains Mono';font-size:13px;color:#4a4a5e">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
          </div>
          <div>
            <h3 style="font-family:'Space Grotesk';font-weight:600;font-size:23px;margin:0 0 10px;color:#f5f5fa">{{ $service->name }}</h3>
            <p style="font-size:15px;line-height:1.6;color:#9b9bb0;margin:0">{{ $service->description }}</p>
          </div>
          <span style="margin-top:auto;font-family:'JetBrains Mono';font-size:13px;color:{{ $accent }}">Request a quote →</span>
        </a>
      @endforeach

      <div data-reveal="{{ $delays[count($services) % count($delays)] }}" style="position:relative;display:flex;flex-direction:column;gap:14px;padding:32px;border-radius:22px;background:linear-gradient(135deg,rgba(124,92,255,.12),rgba(34,211,238,.06));border:1px dashed rgba(255,255,255,.18);transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .35s ease;justify-content:center;align-items:flex-start" style-hover="transform:translateY(-8px)">
        <div style="font-family:'Space Grotesk';font-size:40px;font-weight:300;color:#7c5cff;line-height:1">+</div>
        <h3 style="font-family:'Space Grotesk';font-weight:600;font-size:20px;margin:0;color:#f5f5fa">More on the way</h3>
        <p style="font-size:14px;line-height:1.6;color:#9b9bb0;margin:0">Our catalogue is dynamic — new services drop in without a rebuild.</p>
      </div>

    </div>
  </div>
</section>

<section id="process" class="robo-section pad-x section-pad" style="padding:120px 40px;background:linear-gradient(180deg,transparent,rgba(124,92,255,.04),transparent)">
  <div style="max-width:1240px;margin:0 auto">
    <div data-reveal="0" style="transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1);text-align:center;margin-bottom:64px">
      <div style="font-family:'JetBrains Mono';font-size:12px;letter-spacing:.18em;color:#22d3ee;margin-bottom:16px">// HOW WE WORK</div>
      <h2 style="font-family:'Space Grotesk';font-weight:700;font-size:clamp(34px,5vw,60px);line-height:1;letter-spacing:-.03em;margin:0 auto;max-width:18ch">From first idea to final milestone.</h2>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px">
      <div data-reveal="0" style="transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1);padding:30px;border-radius:20px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08)">
        <div style="font-family:'Space Grotesk';font-weight:700;font-size:52px;line-height:1;background:linear-gradient(120deg,#ff5da2,#7c5cff);-webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:20px">01</div>
        <h3 style="font-family:'Space Grotesk';font-weight:600;font-size:20px;margin:0 0 10px">Discover &amp; quote</h3>
        <p style="font-size:15px;line-height:1.6;color:#9b9bb0;margin:0">Pick a service, tell us about your project, upload references, and request a quote.</p>
      </div>
      <div data-reveal="100" style="transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1);padding:30px;border-radius:20px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08)">
        <div style="font-family:'Space Grotesk';font-weight:700;font-size:52px;line-height:1;background:linear-gradient(120deg,#22d3ee,#4d8bff);-webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:20px">02</div>
        <h3 style="font-family:'Space Grotesk';font-weight:600;font-size:20px;margin:0 0 10px">Proposal &amp; handshake</h3>
        <p style="font-size:15px;line-height:1.6;color:#9b9bb0;margin:0">An expert reviews your brief and sends a tailored proposal. We align, then shake on it.</p>
      </div>
      <div data-reveal="200" style="transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1);padding:30px;border-radius:20px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08)">
        <div style="font-family:'Space Grotesk';font-weight:700;font-size:52px;line-height:1;background:linear-gradient(120deg,#5b8cff,#34e0a1);-webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:20px">03</div>
        <h3 style="font-family:'Space Grotesk';font-weight:600;font-size:20px;margin:0 0 10px">Team &amp; kickoff</h3>
        <p style="font-size:15px;line-height:1.6;color:#9b9bb0;margin:0">Your project is assigned to a specialist supervisor leading a dedicated team in the right field.</p>
      </div>
      <div data-reveal="300" style="transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1);padding:30px;border-radius:20px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08)">
        <div style="font-family:'Space Grotesk';font-weight:700;font-size:52px;line-height:1;background:linear-gradient(120deg,#34e0a1,#ffb020);-webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:20px">04</div>
        <h3 style="font-family:'Space Grotesk';font-weight:600;font-size:20px;margin:0 0 10px">Milestones &amp; delivery</h3>
        <p style="font-size:15px;line-height:1.6;color:#9b9bb0;margin:0">Every milestone is tracked and visible, so you always know exactly where things stand.</p>
      </div>
    </div>
  </div>
</section>

{{-- No .robo-section / data-reveal here either — real delivered-project data,
     not decorative copy, so it's always visible regardless of any JS. --}}
<section id="work" class="pad-x section-pad" style="padding:120px 40px">
  <div style="max-width:1240px;margin:0 auto">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:24px;margin-bottom:56px">
      <div>
        <div style="font-family:'JetBrains Mono';font-size:12px;letter-spacing:.18em;color:#ff5da2;margin-bottom:16px">// SELECTED WORK</div>
        <h2 style="font-family:'Space Grotesk';font-weight:700;font-size:clamp(34px,5vw,60px);line-height:1;letter-spacing:-.03em;margin:0;max-width:14ch">Work our clients rate highly.</h2>
      </div>
      @if ($avgRating)
        <div style="text-align:right">
          <div style="font-family:'Space Grotesk';font-weight:700;font-size:44px;line-height:1;color:#f5f5fa">{{ number_format($avgRating, 1) }}<span style="color:#ffb020;font-size:24px">★</span></div>
          <div style="font-size:14px;color:#9b9bb0;margin-top:6px">avg. rating · {{ $ratedProjectsCount }} delivered project{{ $ratedProjectsCount === 1 ? '' : 's' }}</div>
        </div>
      @endif
    </div>

    @if ($deliveredProjects->isNotEmpty())
      @php $workAccents = ['#22d3ee', '#34e0a1', '#5b8cff', '#ff5da2', '#ffb020', '#7c5cff']; @endphp
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:22px">
        @foreach ($deliveredProjects as $i => $project)
          @php
            $feedback = $project->feedbacks->first();
            $service = $project->quoteRequest->service ?? null;
            $accent = $workAccents[$i % count($workAccents)];
          @endphp
          <div style="position:relative;display:flex;flex-direction:column;gap:16px;padding:28px;border-radius:22px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);overflow:hidden">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,{{ $accent }},transparent)"></div>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div style="width:44px;height:44px;border-radius:12px;background:{{ $accent }}24;border:1px solid {{ $accent }}4d;display:flex;align-items:center;justify-content:center;color:{{ $accent }}">
                <x-icon :name="$service->icon ?? 'sparkles'" class="w-5 h-5" />
              </div>
              <span style="color:#ffb020;font-size:13px;white-space:nowrap">{{ str_repeat('★', $feedback->rating) }}<span style="color:#3a3a48">{{ str_repeat('★', 5 - $feedback->rating) }}</span></span>
            </div>
            <p style="font-family:'JetBrains Mono';font-size:13px;color:#9b9bb0;line-height:1.65;margin:0;flex:1">&ldquo;{{ \Illuminate\Support\Str::limit($feedback->comment, 120) }}&rdquo;</p>
            <div style="padding-top:14px;border-top:1px solid rgba(255,255,255,.07)">
              <div style="font-family:'Space Grotesk';font-weight:600;font-size:17px;color:#f5f5fa">{{ $project->title }}</div>
              <div style="font-size:12.5px;color:#7a7a92;margin-top:3px">{{ $service->name ?? 'Project delivery' }}</div>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div style="padding:60px 40px;border-radius:18px;border:1px dashed rgba(255,255,255,.14);text-align:center">
        <p style="font-family:'JetBrains Mono';font-size:13px;color:#6e6e82;letter-spacing:.04em">// No delivered projects rated yet — check back soon.</p>
      </div>
    @endif
  </div>
</section>

@if ($team->isNotEmpty())
{{-- No .robo-section / data-reveal here on purpose — this is real content
     (who's actually on the team), not decorative copy, so it shouldn't
     depend on a JS animation system to become visible at all. --}}
<section id="team" class="pad-x section-pad" style="padding:120px 40px">
  <div style="max-width:1240px;margin:0 auto">
    <div style="text-align:center;margin-bottom:64px">
      <div style="font-family:'JetBrains Mono';font-size:12px;letter-spacing:.18em;color:#34e0a1;margin-bottom:16px">// THE TEAM</div>
      <h2 style="font-family:'Space Grotesk';font-weight:700;font-size:clamp(34px,5vw,60px);line-height:1;letter-spacing:-.03em;margin:0 auto;max-width:18ch">The people behind the work.</h2>
    </div>

    <div style="display:flex;flex-direction:column;align-items:center;gap:28px">
      @foreach ($team as $node)
        @include('partials.team-node', ['node' => $node])
      @endforeach
    </div>
  </div>
</section>
@endif

<section id="contact" class="robo-section pad-x section-pad" style="padding:130px 40px">
  <div data-reveal="0" class="pad-x" style="transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1);max-width:1000px;margin:0 auto;text-align:center;position:relative;padding:70px 40px;border-radius:32px;background:linear-gradient(135deg,rgba(124,92,255,.14),rgba(34,211,238,.08));border:1px solid rgba(255,255,255,.1);overflow:hidden">
    <div style="position:absolute;width:340px;height:340px;border-radius:50%;background:radial-gradient(circle,rgba(124,92,255,.4),transparent 70%);top:-140px;left:-80px;filter:blur(20px)"></div>
    <div style="position:absolute;width:340px;height:340px;border-radius:50%;background:radial-gradient(circle,rgba(34,211,238,.35),transparent 70%);bottom:-160px;right:-80px;filter:blur(20px)"></div>
    <div style="position:relative;z-index:2">
      <h2 style="font-family:'Space Grotesk';font-weight:700;font-size:clamp(32px,5vw,58px);line-height:1.02;letter-spacing:-.03em;margin:0 0 20px;max-width:18ch;margin-left:auto;margin-right:auto">Let's build something worth talking about.</h2>
      <p style="font-size:18px;color:#c2c2d2;max-width:48ch;margin:0 auto 36px;line-height:1.6">Tell us what you need. You'll get a tailored proposal from a specialist — not a sales bot.</p>
      <div class="contact-cta-row" style="display:flex;flex-wrap:wrap;gap:14px;justify-content:center">
        <a href="{{ route('quote-requests.create') }}" style="padding:16px 32px;border-radius:999px;background:linear-gradient(120deg,#7c5cff,#22d3ee);color:#08080c;font-weight:700;font-family:'Space Grotesk';font-size:16px;box-shadow:0 10px 34px rgba(124,92,255,.4);transition:.3s" style-hover="transform:translateY(-3px);box-shadow:0 16px 44px rgba(124,92,255,.55)">Request a quote →</a>
        <a href="mailto:hello@undek.com" style="padding:16px 32px;border-radius:999px;border:1px solid rgba(255,255,255,.2);color:#f5f5fa;font-weight:600;font-size:16px;transition:.3s" style-hover="border-color:rgba(255,255,255,.45);background:rgba(255,255,255,.05)">hello@undek.com</a>
      </div>
    </div>
  </div>
</section>

<footer class="pad-x" style="padding:70px 40px 44px;border-top:1px solid rgba(255,255,255,.07)">
  <div class="footer-grid" style="max-width:1240px;margin:0 auto;display:grid;grid-template-columns:1.6fr 1fr 1fr;gap:40px">
    <div>
      <div style="display:flex;align-items:center;margin-bottom:18px">
        <x-logo-lockup style="font-size:19px;letter-spacing:-.02em" />
      </div>
      <p style="font-size:15px;line-height:1.6;color:#9b9bb0;margin:0 0 20px;max-width:34ch">A full-stack digital studio. We design, build, rank, and grow — under one roof, with milestones you can see.</p>
      <div style="display:flex;gap:10px">
        <a href="https://www.facebook.com/profile.php?id=100064091621113" target="_blank" rel="noopener noreferrer" aria-label="undek on Facebook" style="width:38px;height:38px;border-radius:50%;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.03);display:flex;align-items:center;justify-content:center;color:#9b9bb0;transition:.25s" style-hover="color:#f5f5fa;border-color:rgba(34,211,238,.4);background:rgba(34,211,238,.08)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7.9h2.65l.4-3.08h-3.05V8.06c0-.89.25-1.5 1.52-1.5h1.63V3.85A21.8 21.8 0 0 0 14.1 3.7c-2.42 0-4.08 1.48-4.08 4.19v2.13H7.36v3.08h2.66V21h3.48Z"/></svg>
        </a>
        <a href="https://www.instagram.com/undekofficial/" target="_blank" rel="noopener noreferrer" aria-label="undek on Instagram" style="width:38px;height:38px;border-radius:50%;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.03);display:flex;align-items:center;justify-content:center;color:#9b9bb0;transition:.25s" style-hover="color:#f5f5fa;border-color:rgba(255,93,162,.4);background:rgba(255,93,162,.08)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.4" cy="6.6" r="1"/></svg>
        </a>
        <a href="https://www.youtube.com/@undek.official" target="_blank" rel="noopener noreferrer" aria-label="undek on YouTube" style="width:38px;height:38px;border-radius:50%;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.03);display:flex;align-items:center;justify-content:center;color:#9b9bb0;transition:.25s" style-hover="color:#f5f5fa;border-color:rgba(255,176,32,.4);background:rgba(255,176,32,.08)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12s0-3.3-.42-4.86a2.78 2.78 0 0 0-1.96-2C17.9 4.7 12 4.7 12 4.7s-5.9 0-7.62.44a2.78 2.78 0 0 0-1.96 2C2 8.7 2 12 2 12s0 3.3.42 4.86a2.78 2.78 0 0 0 1.96 1.96C6.1 19.3 12 19.3 12 19.3s5.9 0 7.62-.48a2.78 2.78 0 0 0 1.96-1.96C22 15.3 22 12 22 12Z"/><path d="M10 9.6 15 12l-5 2.4V9.6Z" fill="#08080c"/></svg>
        </a>
        <a href="https://www.linkedin.com/company/144573084/" target="_blank" rel="noopener noreferrer" aria-label="undek on LinkedIn" style="width:38px;height:38px;border-radius:50%;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.03);display:flex;align-items:center;justify-content:center;color:#9b9bb0;transition:.25s" style-hover="color:#f5f5fa;border-color:rgba(91,140,255,.4);background:rgba(91,140,255,.08)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.94 8.5H3.56V20h3.38V8.5ZM5.25 3.7a1.96 1.96 0 1 0 0 3.92 1.96 1.96 0 0 0 0-3.92ZM20.44 20h-3.37v-6.06c0-1.44-.03-3.3-2.02-3.3-2.02 0-2.33 1.58-2.33 3.2V20H9.36V8.5h3.24v1.57h.05c.45-.86 1.56-1.76 3.2-1.76 3.43 0 4.06 2.25 4.06 5.19V20Z"/></svg>
        </a>
      </div>
    </div>
    <div>
      <div style="font-family:'JetBrains Mono';font-size:12px;letter-spacing:.14em;color:#5c5c72;margin-bottom:16px">SERVICES</div>
      <div style="display:flex;flex-direction:column;gap:11px;font-size:15px;color:#c7c7d6">
        @foreach ($services as $service)
          <a href="{{ route('quote-requests.create') }}?service={{ $service->slug }}" style="color:#c7c7d6">{{ $service->name }}</a>
        @endforeach
      </div>
    </div>
    <div>
      <div style="font-family:'JetBrains Mono';font-size:12px;letter-spacing:.14em;color:#5c5c72;margin-bottom:16px">COMPANY</div>
      <div style="display:flex;flex-direction:column;gap:11px;font-size:15px;color:#c7c7d6">
        <a href="#work" style="color:#c7c7d6">Work</a>
        <a href="#process" style="color:#c7c7d6">Process</a>
        <a href="{{ route('privacy-policy') }}" style="color:#c7c7d6">Privacy Policy</a>
        <a href="mailto:hello@undek.com" style="color:#c7c7d6">Contact</a>
      </div>
    </div>
  </div>
  <div style="max-width:1240px;margin:44px auto 0;padding-top:24px;border-top:1px solid rgba(255,255,255,.06);display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:13px;color:#5c5c72;font-family:'JetBrains Mono'">
    <span>© {{ date('Y') }} undek.com — all rights reserved</span>
    <span><a href="{{ route('privacy-policy') }}" style="color:#5c5c72">Privacy Policy</a> &nbsp;·&nbsp; Designed &amp; built by undek</span>
  </div>
</footer>

</div>

<!-- ─── AI CHAT WIDGET ─── -->
<div id="chatbot-launcher" style="position:fixed;right:26px;bottom:26px;z-index:9200;display:flex;flex-direction:column;align-items:flex-end;gap:12px;font-family:'Manrope',system-ui,sans-serif">
  <div id="chatbot-panel" style="display:none;width:min(360px,calc(100vw - 40px));height:min(480px,calc(100vh - 140px));background:#101018;border:1px solid rgba(255,255,255,.12);border-radius:20px;box-shadow:0 24px 60px rgba(0,0,0,.5);overflow:hidden;flex-direction:column">
    <div style="padding:16px 18px;background:linear-gradient(120deg,rgba(124,92,255,.18),rgba(34,211,238,.1));border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between">
      <div>
        <div style="font-family:'Space Grotesk';font-weight:700;font-size:15px;color:#f5f5fa">undek assistant</div>
        <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
          <span style="width:6px;height:6px;border-radius:50%;background:#34e0a1;box-shadow:0 0 8px #34e0a1"></span>
          <span style="font-family:'JetBrains Mono';font-size:11px;color:#9b9bb0;letter-spacing:.04em">Online · 24/7 support</span>
        </div>
      </div>
      <button id="chatbot-close" aria-label="Close chat" style="background:none;border:none;color:#9b9bb0;font-size:20px;line-height:1;cursor:pointer;padding:4px">&times;</button>
    </div>
    <div id="chatbot-messages" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px"></div>
    <form id="chatbot-form" aria-label="Chat with the undek assistant" style="display:flex;gap:8px;padding:12px;border-top:1px solid rgba(255,255,255,.08)">
      <input id="chatbot-input" type="text" autocomplete="off" aria-label="Your message" placeholder="Ask about services, pricing…" style="flex:1;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:999px;padding:10px 16px;color:#f5f5fa;font-size:14px;outline:none">
      <button type="submit" aria-label="Send message" style="width:40px;height:40px;border-radius:50%;border:none;background:linear-gradient(120deg,#7c5cff,#22d3ee);color:#08080c;font-weight:700;cursor:pointer;flex-shrink:0">→</button>
    </form>
  </div>
  <button id="chatbot-toggle" aria-label="Open chat assistant" style="width:60px;height:60px;border-radius:50%;border:none;background:linear-gradient(135deg,#7c5cff,#22d3ee);box-shadow:0 10px 30px rgba(124,92,255,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;position:relative">
    <span style="position:absolute;top:-2px;right:-2px;width:14px;height:14px;border-radius:50%;background:#34e0a1;border:2px solid #08080c;box-shadow:0 0 8px #34e0a1"></span>
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#08080c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3.117-3.117a1.125 1.125 0 00-.795-.33h-2.088c-.628 0-1.176-.427-1.29-1.026m8.29-6.86h.008v.008h-.008v-.008zm-8.29 6.86A24.301 24.301 0 013.75 15.75c-1.136 0-2.1-.847-2.193-1.98a24.301 24.301 0 010-5.54c.093-1.133 1.057-1.98 2.193-1.98h9.75c1.136 0 2.1.847 2.193 1.98a24.3 24.3 0 010 5.54c-.093 1.133-1.057 1.98-2.193 1.98H12"/></svg>
  </button>
</div>

<script>
// ─── REVEAL SAFETY NET (registered first, before anything that could throw) ───
// Content below is invisible-by-default until "revealed" by JS further down.
// This guarantees it becomes visible no matter what — even if a completely
// unrelated widget below throws and stops the rest of the script from
// running, this has already been scheduled and fires independently.
setTimeout(() => {
  document.querySelectorAll('.robo-section:not(.unmasked)').forEach(sec => sec.classList.add('unmasked'));
  document.querySelectorAll('[data-reveal]:not(.revealed)').forEach(el => el.classList.add('revealed'));
}, 1200);

// ─── AI CHAT WIDGET ───
try {
(function() {
  const launcher = document.getElementById('chatbot-launcher');
  const panel = document.getElementById('chatbot-panel');
  const toggle = document.getElementById('chatbot-toggle');
  const closeBtn = document.getElementById('chatbot-close');
  const messages = document.getElementById('chatbot-messages');
  const form = document.getElementById('chatbot-form');
  const input = document.getElementById('chatbot-input');
  const csrfToken = document.querySelector('meta[name=csrf-token]').content;
  let opened = false;

  function addMessage(text, from) {
    const row = document.createElement('div');
    row.style.cssText = 'display:flex;justify-content:' + (from === 'user' ? 'flex-end' : 'flex-start');
    const bubble = document.createElement('div');
    bubble.style.cssText = 'max-width:80%;padding:9px 13px;border-radius:14px;font-size:14px;line-height:1.5;' +
      (from === 'user'
        ? 'background:linear-gradient(120deg,#7c5cff,#22d3ee);color:#08080c;font-weight:500'
        : 'background:rgba(255,255,255,.06);color:#e4e4ef;border:1px solid rgba(255,255,255,.08)');
    bubble.textContent = text;
    row.appendChild(bubble);
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
  }

  toggle.addEventListener('click', () => {
    opened = !opened;
    panel.style.display = opened ? 'flex' : 'none';
    if (opened && messages.children.length === 0) {
      addMessage("Hey! 👋 I'm the undek assistant, available 24/7. Ask me about our services, pricing, or how to get a quote.", 'bot');
      input.focus();
    }
  });

  closeBtn.addEventListener('click', () => {
    opened = false;
    panel.style.display = 'none';
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;
    addMessage(text, 'user');
    input.value = '';

    const typing = document.createElement('div');
    typing.id = 'chatbot-typing';
    typing.style.cssText = 'font-size:13px;color:#6e6e82;font-family:JetBrains Mono';
    typing.textContent = 'undek assistant is typing…';
    messages.appendChild(typing);
    messages.scrollTop = messages.scrollHeight;

    try {
      const res = await fetch('{{ route('chatbot.respond') }}', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ message: text }),
      });
      const data = await res.json();
      typing.remove();
      addMessage(data.reply || "Sorry, I'm having trouble responding right now — try emailing hello@undek.com.", 'bot');
    } catch (err) {
      typing.remove();
      addMessage("Sorry, I'm having trouble responding right now — try emailing hello@undek.com.", 'bot');
    }
  });
})();
} catch (e) { console.error('chat widget failed to init', e); }

// ─── HUD CLOCK ───
try {
function tick() {
  const n = document.getElementById('hud-clk');
  if (!n) return;
  const now = new Date();
  const pad = x => String(x).padStart(2,'0');
  n.textContent = pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds());
}
setInterval(tick, 1000); tick();
} catch (e) { console.error('hud clock failed to init', e); }

// ─── BOOT WIPE ───
try {
(function() {
  const bars = [...document.querySelectorAll('#page-wipe .bar')];
  bars.forEach((b, i) => setTimeout(() => {
    b.style.transition = 'transform 0.2s cubic-bezier(.4,0,1,1)';
    b.style.transform  = 'scaleX(1)';
  }, i * 30));
  const out = bars.length * 30 + 280;
  bars.forEach((b, i) => setTimeout(() => {
    b.style.transformOrigin = 'right center';
    b.style.transition = 'transform 0.2s cubic-bezier(0,0,.6,1)';
    b.style.transform  = 'scaleX(0)';
  }, out + i * 30));
})();
} catch (e) { console.error('boot wipe failed to init', e); }

// ─── TERMINAL TYPE ANIMATION ───
try {
(function() {
  const el = document.getElementById('term-body');
  if (!el) return;

  const lines = [
    { text: '$ undek deploy client-app', cls: '', type: true },
    { text: '> provisioning environment...  done', cls: 'color:#6e6e82', type: false },
    { text: '> installing dependencies...   done', cls: 'color:#6e6e82', type: false },
    { text: '> running test suite...        128/128 passed', cls: 'color:#6e6e82', type: false },
    { text: '> build optimized  (1.2s)', cls: 'color:#6e6e82', type: false },
    { text: '✓ deployed to production', cls: 'color:#34e0a1;font-weight:600', type: false },
  ];

  let cursor = document.createElement('span');
  cursor.className = 'term-cursor';
  cursor.textContent = ' ';

  function typeLine(line, cb) {
    const row = document.createElement('div');
    const textSpan = document.createElement('span');
    row.style.cssText = line.cls;
    row.appendChild(textSpan);
    el.appendChild(row);
    // The cursor is appended once per line, not per character — re-inserting
    // an animated element into the DOM restarts its CSS animation, so doing
    // this on every keystroke made the blink stutter instead of pulsing evenly.
    row.appendChild(cursor);
    if (!line.type) {
      textSpan.textContent = line.text;
      setTimeout(cb, 260);
      return;
    }
    let i = 0;
    function step() {
      textSpan.textContent = line.text.slice(0, i);
      i++;
      if (i <= line.text.length) {
        setTimeout(step, 28 + Math.random() * 30);
      } else {
        setTimeout(cb, 320);
      }
    }
    step();
  }

  function run(i) {
    if (i >= lines.length) {
      setTimeout(() => { el.innerHTML = ''; run(0); }, 3200);
      return;
    }
    typeLine(lines[i], () => run(i + 1));
  }

  run(0);
})();
} catch (e) { console.error('terminal animation failed to init', e); }

// ─── PARTICLE CANVAS ───
try {
(function() {
  const canvas = document.getElementById('hero-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let W, H, pts;
  const COLS = ['#22d3ee','#7c5cff','#ff5da2','#34e0a1','#ffb020'];

  function resize() {
    W = canvas.width  = canvas.offsetWidth;
    H = canvas.height = canvas.offsetHeight;
  }
  function init() {
    pts = Array.from({length:60}, () => ({
      x: Math.random()*W, y: Math.random()*H,
      vx: (Math.random()-.5)*.3, vy: (Math.random()-.5)*.3,
      r: Math.random()*1.8+.6,
      c: COLS[Math.floor(Math.random()*COLS.length)]
    }));
  }
  function frame() {
    ctx.clearRect(0,0,W,H);
    pts.forEach(p => {
      p.x += p.vx; p.y += p.vy;
      if (p.x<0||p.x>W) p.vx*=-1;
      if (p.y<0||p.y>H) p.vy*=-1;
    });
    for (let i=0;i<pts.length;i++) for (let j=i+1;j<pts.length;j++) {
      const d = Math.hypot(pts[i].x-pts[j].x, pts[i].y-pts[j].y);
      if (d<130) {
        ctx.strokeStyle = `rgba(124,92,255,${(1-d/130)*.2})`;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(pts[i].x,pts[i].y);
        ctx.lineTo(pts[j].x,pts[j].y);
        ctx.stroke();
      }
    }
    pts.forEach(p => {
      ctx.fillStyle = p.c;
      ctx.beginPath();
      ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fill();
    });
    requestAnimationFrame(frame);
  }
  window.addEventListener('resize', () => { resize(); init(); });
  resize(); init(); frame();
})();
} catch (e) { console.error('particle canvas failed to init', e); }

// ─── ROBOTIC SECTION REVEALS ───
// Sections: mechanical sweep-unmask + staggered data-reveal children.
// (The safety net covering these same elements is already registered at the
// very top of this script, before any of the above — see there.)
try {
const sections = document.querySelectorAll('.robo-section');
const revealObs = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const sec = entry.target;
    if (sec.dataset.done) return;
    sec.dataset.done = '1';

    // Phase 1: glitch flash
    let f = 0;
    const flash = setInterval(() => {
      sec.style.opacity = f++ % 2 === 0 ? '0.06' : '1';
      if (f >= 6) {
        clearInterval(flash);
        sec.style.opacity = '1';
        // Phase 2: sweep mask away
        sec.classList.add('unmasked');
        // Phase 3: staggered reveal of children
        sec.querySelectorAll('[data-reveal]').forEach(el => {
          const delay = parseInt(el.getAttribute('data-reveal')) || 0;
          setTimeout(() => el.classList.add('revealed'), delay + 100);
        });
      }
    }, 60);
    revealObs.unobserve(sec);
  });
}, { threshold: 0.08 });

sections.forEach(s => revealObs.observe(s));
} catch (e) { console.error('section reveal observer failed to init', e); }

// ─── MOBILE NAV TOGGLE ───
try {
(function() {
  const toggle = document.getElementById('nav-toggle');
  const links = document.getElementById('nav-links');
  if (!toggle || !links) return;
  toggle.addEventListener('click', () => {
    const open = links.classList.toggle('open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    links.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
  }));
})();
} catch (e) { console.error('mobile nav toggle failed to init', e); }

// ─── NAV HOVER ───
try {
document.querySelectorAll('nav a').forEach(a => {
  a.addEventListener('mouseenter', () => a.style.color = '#22d3ee');
  a.addEventListener('mouseleave', () => a.style.color = '');
});
} catch (e) { console.error('nav hover failed to init', e); }

// ─── STYLE-HOVER ATTRIBUTES ───
// The original design uses style-hover="..." — implement via JS
try {
document.querySelectorAll('[style-hover]').forEach(el => {
  const base = el.getAttribute('style') || '';
  const hover = el.getAttribute('style-hover') || '';
  el.addEventListener('mouseenter', () => el.setAttribute('style', base + ';' + hover));
  el.addEventListener('mouseleave', () => el.setAttribute('style', base));
});
} catch (e) { console.error('style-hover failed to init', e); }
</script>
</body>
</html>
