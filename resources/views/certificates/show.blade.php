<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ \App\Models\Certificate::TYPES[$certificate->type] ?? 'Certificate' }} — {{ $certificate->code }}</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body { margin: 0; background: #f1f0f6; color: #201c33; font-family: 'Manrope', system-ui, sans-serif; }
  .bar { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; background: #17142a; color: #f5f5fa; }
  .bar a, .bar button { font-family: 'Manrope', sans-serif; font-size: 13px; font-weight: 600; color: #f5f5fa; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); padding: 8px 16px; border-radius: 999px; cursor: pointer; text-decoration: none; }
  .bar button.primary { background: linear-gradient(120deg,#7c5cff,#22d3ee); color: #0c0c12; border: none; }
  .sheet-wrap { max-width: 800px; margin: 40px auto; padding: 0 20px; }
  .sheet { background: #ffffff; border-radius: 4px; box-shadow: 0 1px 3px rgba(20,14,50,.08), 0 20px 50px rgba(20,14,50,.12); padding: 64px 72px; position: relative; overflow: hidden; }
  .sheet::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 8px; background: linear-gradient(90deg,#7c5cff,#22d3ee,#34e0a1); }
  .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 46px; }
  .brand .mark { width: 26px; height: 26px; border-radius: 7px; background: linear-gradient(135deg,#7c5cff,#22d3ee); transform: rotate(10deg); }
  .brand .name { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 20px; letter-spacing: -.02em; }
  .doc-kicker { font-family: 'JetBrains Mono', monospace; font-size: 11px; letter-spacing: .16em; text-transform: uppercase; color: #7c5cff; margin-bottom: 10px; }
  h1 { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 30px; letter-spacing: -.02em; margin: 0 0 34px; }
  .body-copy { font-size: 16px; line-height: 1.85; color: #2c2740; max-width: 60ch; }
  .body-copy strong { color: #17142a; }
  .facts { margin: 36px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 14px 28px; padding: 22px 26px; background: #f7f6fb; border-radius: 10px; border: 1px solid #ece9f4; }
  .facts div span.label { display: block; font-family: 'JetBrains Mono', monospace; font-size: 10.5px; letter-spacing: .08em; text-transform: uppercase; color: #8a84a3; margin-bottom: 3px; }
  .facts div span.value { font-size: 14.5px; font-weight: 600; color: #201c33; }
  .sign-row { margin-top: 64px; display: flex; justify-content: space-between; align-items: flex-end; }
  .sign-block .sig { font-family: 'Space Grotesk', sans-serif; font-size: 22px; font-weight: 700; color: #17142a; border-bottom: 1px solid #201c33; padding-bottom: 6px; margin-bottom: 6px; display: inline-block; min-width: 200px; }
  .sign-block .title { font-size: 12.5px; color: #8a84a3; }
  .verify { text-align: right; font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #8a84a3; line-height: 1.7; }
  .verify .code { color: #17142a; font-weight: 600; }
  @media print {
    .no-print { display: none !important; }
    body { background: #fff; }
    .sheet-wrap { margin: 0; padding: 0; max-width: none; }
    .sheet { box-shadow: none; border-radius: 0; padding: 48px 56px; }
  }
</style>
</head>
<body>

<div class="bar no-print">
  <a href="{{ route('certificates.index') }}">&larr; Back to my certificates</a>
  <button class="primary" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="sheet-wrap">
  <div class="sheet">
    <div class="brand"><span class="mark"></span><span class="name">undek</span></div>

    @php $s = $certificate->snapshot ?? []; @endphp

    @if ($certificate->type === \App\Models\Certificate::INTERNSHIP_CERTIFICATE)
      <div class="doc-kicker">Certificate of Internship</div>
      <h1>{{ $s['end_date'] ? 'Certificate of Completion' : 'Certificate of Internship' }}</h1>
      <div class="body-copy">
        <p>This is to certify that <strong>{{ $s['name'] ?? '—' }}</strong>
          @if ($s['end_date'])
            successfully completed an internship at <strong>Undek</strong>
          @else
            is currently undergoing an internship at <strong>Undek</strong>
          @endif
          @if (!empty($s['position'])) as <strong>{{ $s['position'] }}</strong> @endif
          @if (!empty($s['department'])) in the <strong>{{ $s['department'] }}</strong> department @endif,
          @if ($s['end_date'])
            from {{ \Illuminate\Support\Carbon::parse($s['hire_date'])->format('F j, Y') }} to {{ \Illuminate\Support\Carbon::parse($s['end_date'])->format('F j, Y') }}.
          @else
            beginning {{ !empty($s['hire_date']) ? \Illuminate\Support\Carbon::parse($s['hire_date'])->format('F j, Y') : '—' }}.
          @endif
        </p>
        <p>Throughout this period, {{ explode(' ', $s['name'] ?? 'the intern')[0] }} contributed to real client work and demonstrated a working command of the responsibilities entrusted to them.</p>
      </div>
    @elseif ($certificate->type === \App\Models\Certificate::EXPERIENCE_LETTER)
      <div class="doc-kicker">Experience Letter</div>
      <h1>To Whom It May Concern</h1>
      <div class="body-copy">
        <p>This is to certify that <strong>{{ $s['name'] ?? '—' }}</strong> {{ $s['end_date'] ? 'was' : 'is' }} engaged with <strong>Undek</strong>
          @if (!empty($s['position'])) as <strong>{{ $s['position'] }}</strong> @endif
          @if (!empty($s['department'])) in the <strong>{{ $s['department'] }}</strong> department @endif,
          starting {{ !empty($s['hire_date']) ? \Illuminate\Support\Carbon::parse($s['hire_date'])->format('F j, Y') : '—' }}
          @if ($s['end_date']) through {{ \Illuminate\Support\Carbon::parse($s['end_date'])->format('F j, Y') }} @else and continuing to date @endif.
        </p>
        <p>During this engagement, they conducted themselves professionally and performed their duties to our satisfaction. We wish them well in their future endeavors.</p>
      </div>
    @else
      <div class="doc-kicker">Earnings Letter</div>
      <h1>Confirmation of Compensation</h1>
      <div class="body-copy">
        <p>This letter confirms that <strong>{{ $s['name'] ?? '—' }}</strong> is engaged with <strong>Undek</strong>
          @if (!empty($s['position'])) as <strong>{{ $s['position'] }}</strong> @endif,
          effective {{ !empty($s['hire_date']) ? \Illuminate\Support\Carbon::parse($s['hire_date'])->format('F j, Y') : '—' }},
          compensated on a <strong>{{ \App\Models\EmployeeProfile::COMPENSATION_TYPES[$s['compensation_type'] ?? ''] ?? 'to-be-determined' }}</strong> basis
          @if (!is_null($s['compensation_value'] ?? null))
            at a rate of <strong>
              @if ($s['compensation_type'] === 'project_percentage') {{ number_format($s['compensation_value'], 2) }}% of project value
              @elseif ($s['compensation_type'] === 'hourly') {{ number_format($s['compensation_value'], 2) }}/hour
              @elseif ($s['compensation_type'] === 'fixed_project_price') {{ number_format($s['compensation_value'], 2) }} per project
              @else {{ number_format($s['compensation_value'], 2) }}/year
              @endif
            </strong>
          @endif
          .
        </p>
        <p>This letter is issued for verification purposes upon request and reflects standing as of the issue date below.</p>
      </div>
    @endif

    <div class="facts">
      <div><span class="label">Full name</span><span class="value">{{ $s['name'] ?? '—' }}</span></div>
      <div><span class="label">Position</span><span class="value">{{ $s['position'] ?? '—' }}</span></div>
      <div><span class="label">Department</span><span class="value">{{ $s['department'] ?? '—' }}</span></div>
      <div><span class="label">Employment type</span><span class="value">{{ \Illuminate\Support\Str::of($s['employment_type'] ?? '—')->replace('_',' ')->title() }}</span></div>
      <div><span class="label">Start date</span><span class="value">{{ !empty($s['hire_date']) ? \Illuminate\Support\Carbon::parse($s['hire_date'])->format('M j, Y') : '—' }}</span></div>
      <div><span class="label">{{ $s['end_date'] ? 'End date' : 'Status' }}</span><span class="value">{{ $s['end_date'] ? \Illuminate\Support\Carbon::parse($s['end_date'])->format('M j, Y') : 'Active' }}</span></div>
    </div>

    <div class="sign-row">
      <div class="sign-block">
        <span class="sig">Undek Management</span>
        <div class="title">Authorized signatory</div>
      </div>
      <div class="verify">
        Issued {{ $certificate->issued_at->format('M j, Y') }}<br>
        Certificate no. <span class="code">{{ $certificate->code }}</span><br>
        Verify at {{ parse_url(route('certificates.verify', $certificate->code), PHP_URL_HOST) }}{{ parse_url(route('certificates.verify', $certificate->code), PHP_URL_PATH) }}
      </div>
    </div>
  </div>
</div>

</body>
</html>
