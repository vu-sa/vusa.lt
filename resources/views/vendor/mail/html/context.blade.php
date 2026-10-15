@props(['rows' => []])
@if (count($rows) > 0)
<table class="context" width="100%" cellpadding="0" cellspacing="0" role="presentation">
@foreach ($rows as $row)
<tr>
<td class="context-label">{{ $row['label'] }}</td>
<td class="context-value">{{ $row['value'] }}</td>
</tr>
@endforeach
</table>
@endif
