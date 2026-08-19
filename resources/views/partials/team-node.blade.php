@php
    $user = $node['user'];
    $profile = $user->employeeProfile;
    $initials = collect(explode(' ', $user->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<div style="display:flex;flex-direction:column;align-items:center;gap:18px">
    {{-- Deliberately not using data-reveal here — real content (who's actually
         on the team) shouldn't be gated behind a JS fade-in that can leave it
         invisible if anything else on the page misbehaves. --}}
    <div style="width:240px;padding:22px;border-radius:18px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);text-align:center;position:relative;overflow:hidden">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#7c5cff,#22d3ee)"></div>
        @if ($user->avatarUrl())
            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;display:block;object-fit:cover">
        @else
            <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#7c5cff,#22d3ee);font-family:'Space Grotesk';font-weight:700;font-size:18px;color:#08080c">{{ $initials }}</div>
        @endif
        <div style="font-family:'Space Grotesk';font-weight:600;font-size:16px;color:#f5f5fa">{{ $user->name }}</div>
        @php
            $roleName = $user->roles->first()->name ?? 'Team';
            $roleLabel = $roleName === 'ceo' ? 'CEO' : \Illuminate\Support\Str::of($roleName)->replace('_', ' ')->title();
        @endphp
        <div style="font-family:'JetBrains Mono';font-size:11px;letter-spacing:.06em;color:#22d3ee;margin-top:4px">{{ $profile->position->name ?? $roleLabel }}</div>
        @if ($profile->department)
            <div style="font-size:12px;color:#7a7a92;margin-top:6px">{{ $profile->department->name }}</div>
        @endif
        @if ($profile->bio)
            <p style="font-size:12.5px;line-height:1.55;color:#9b9bb0;margin:10px 0 0">{{ \Illuminate\Support\Str::limit($profile->bio, 90) }}</p>
        @endif
    </div>

    @if ($node['children']->isNotEmpty())
        <div style="width:1px;height:20px;background:linear-gradient(180deg,rgba(124,92,255,.5),transparent)"></div>
        <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:28px">
            @foreach ($node['children'] as $child)
                @include('partials.team-node', ['node' => $child])
            @endforeach
        </div>
    @endif
</div>
