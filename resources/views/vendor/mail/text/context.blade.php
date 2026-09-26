@props(['rows' => []])
@foreach ($rows as $row)
{{ $row['label'] }}: {{ $row['value'] }}
@endforeach
