{!! $replyBody !!}

@if ($original->body_text || $original->body_html)
On {!! $original->received_at?->format('D, M j, Y \a\t g:i A') !!}, {!! $original->from_name ?: $original->from_email !!} <{!! $original->from_email !!}> wrote:
{!! collect(preg_split('/\R/', \Illuminate\Support\Str::limit($original->body_text ?: trim(html_entity_decode(strip_tags((string) $original->body_html))), 5000)))->map(fn ($line) => '> '.$line)->implode("\n") !!}
@endif
