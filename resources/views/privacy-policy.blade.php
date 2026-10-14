<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Privacy Policy – undek</title>
<meta name="description" content="How Undek collects, uses and protects the information you share when requesting a quote or working with us.">
<link rel="canonical" href="{{ route('privacy-policy') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}?v=2">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:#08080c;color:#f5f5fa;font-family:'Manrope',system-ui,sans-serif;-webkit-font-smoothing:antialiased}
a{color:#22d3ee;text-decoration:none}
a:hover{color:#7cf0ff}
h1,h2{font-family:'Space Grotesk',sans-serif}
::selection{background:#7c5cff;color:#fff}
</style>
</head>
<body>

<nav style="position:sticky;top:0;z-index:60;display:flex;align-items:center;justify-content:space-between;padding:16px 40px;backdrop-filter:blur(16px);background:rgba(8,8,12,0.6);border-bottom:1px solid rgba(255,255,255,0.07)">
  <a href="{{ route('home') }}" style="display:flex;align-items:center">
    <x-logo-lockup glow style="font-size:21px;color:#f5f5fa;letter-spacing:-.02em" />
  </a>
  <div style="display:flex;gap:24px;align-items:center;font-size:15px;font-weight:500">
    <a href="{{ route('home') }}" style="color:#c7c7d6">&larr; Back to home</a>
    @auth
      <a href="{{ url('/dashboard') }}" style="color:#c7c7d6">Dashboard</a>
    @else
      <a href="{{ route('login') }}" style="color:#c7c7d6">Log in</a>
    @endauth
  </div>
</nav>

<div style="max-width:760px;margin:0 auto;padding:80px 40px 120px">
  <div style="font-family:'JetBrains Mono';font-size:12px;letter-spacing:.18em;color:#7c5cff;margin-bottom:16px">// LEGAL</div>
  <h1 style="font-weight:700;font-size:clamp(32px,5vw,48px);line-height:1.05;letter-spacing:-.03em;margin:0 0 12px">Privacy Policy</h1>
  <p style="color:#9b9bb0;font-size:14px;font-family:'JetBrains Mono';margin:0 0 48px">Last updated: {{ now()->format('F j, Y') }}</p>

  <div style="color:#c2c2d2;font-size:16px;line-height:1.75">
    <p>undek ("we", "us", "our") operates a full-stack digital studio and the client portal at this domain. This policy explains what information we collect when you request a quote, work with us on a project, or use the portal, and how we use, store and protect it.</p>

    <h2 style="font-weight:600;font-size:22px;margin:40px 0 14px;color:#f5f5fa">1. Information we collect</h2>
    <p><strong style="color:#f5f5fa">Information you give us directly</strong> — your name, email address, phone number, company details, project descriptions, requirements, reference files or images you upload, and any messages you send us through a quote request, project chat, or email.</p>
    <p><strong style="color:#f5f5fa">Account information</strong> — if you register for the client portal, we store your name, email address, and a securely hashed password. We never store your password in plain text.</p>
    <p><strong style="color:#f5f5fa">Project &amp; billing information</strong> — quotations, budgets, milestones, deliverables, and payment-related details associated with work we do for you.</p>
    <p><strong style="color:#f5f5fa">Automatically collected information</strong> — standard technical data such as IP address, browser type, and pages visited, collected via server logs and, where used, cookies or similar technologies for authentication and session management.</p>

    <h2 style="font-weight:600;font-size:22px;margin:40px 0 14px;color:#f5f5fa">2. How we use your information</h2>
    <p>We use the information we collect to:</p>
    <ul style="padding-left:20px">
      <li>respond to quote requests and prepare quotations;</li>
      <li>deliver, manage, and communicate about projects, milestones, and deadlines;</li>
      <li>maintain your account and authenticate you in the client portal;</li>
      <li>send you service-related updates, and — only with your consent or an existing business relationship — occasional product or promotional emails;</li>
      <li>improve our services, troubleshoot issues, and keep the portal secure;</li>
      <li>meet our legal, accounting, and contractual obligations.</li>
    </ul>

    <h2 style="font-weight:600;font-size:22px;margin:40px 0 14px;color:#f5f5fa">3. How we share your information</h2>
    <p>We do not sell your personal information. We only share it with:</p>
    <ul style="padding-left:20px">
      <li>the undek team members and specialists directly assigned to your project, so they can do the work you've asked for;</li>
      <li>service providers who help us operate the portal (for example, hosting and email delivery), under obligations to protect your data;</li>
      <li>authorities, where required by law or to protect our legal rights.</li>
    </ul>

    <h2 style="font-weight:600;font-size:22px;margin:40px 0 14px;color:#f5f5fa">4. Data retention</h2>
    <p>We retain project and account information for as long as your account is active or as needed to provide our services, comply with legal obligations, resolve disputes, and enforce our agreements. You can request deletion of your account and associated personal data at any time, subject to records we're required to keep.</p>

    <h2 style="font-weight:600;font-size:22px;margin:40px 0 14px;color:#f5f5fa">5. Your rights</h2>
    <p>Depending on where you're located, you may have the right to access, correct, export, or delete your personal information, and to object to or restrict certain processing. To exercise any of these rights, contact us using the details below.</p>

    <h2 style="font-weight:600;font-size:22px;margin:40px 0 14px;color:#f5f5fa">6. Security</h2>
    <p>We use industry-standard measures — including encrypted password storage, HTTPS in transit, and access controls scoped by role — to protect your information. No method of transmission or storage is 100% secure, but we work to protect your data appropriately for its sensitivity.</p>

    <h2 style="font-weight:600;font-size:22px;margin:40px 0 14px;color:#f5f5fa">7. Cookies</h2>
    <p>We use essential cookies to keep you signed in and to remember preferences such as light/dark mode. We do not use third-party advertising trackers.</p>

    <h2 style="font-weight:600;font-size:22px;margin:40px 0 14px;color:#f5f5fa">8. Changes to this policy</h2>
    <p>We may update this policy from time to time. If we make material changes, we'll update the "last updated" date above and, where appropriate, let you know directly.</p>

    <h2 style="font-weight:600;font-size:22px;margin:40px 0 14px;color:#f5f5fa">9. Contact us</h2>
    <p>Questions about this policy or your data? Email us at <a href="mailto:hello@undek.com">hello@undek.com</a>.</p>
  </div>
</div>

<footer style="padding:40px;border-top:1px solid rgba(255,255,255,.07);text-align:center">
  <span style="font-size:13px;color:#5c5c72;font-family:'JetBrains Mono'">© {{ date('Y') }} undek.com — all rights reserved</span>
</footer>

</body>
</html>
