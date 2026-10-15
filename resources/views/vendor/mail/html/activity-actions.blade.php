@props(['question'])
<table class="activity-actions" role="presentation" cellspacing="0" cellpadding="0" style="margin:16px 0;max-width:100%;">
<tr>
<td style="padding-right:8px;vertical-align:top;"><a class="button button-neutral" href="{{ $question['met'] }}">{{ $question['primaryLabel'] }}</a></td>
<td style="vertical-align:top;"><a class="button button-neutral" href="{{ $question['notMet'] }}">{{ $question['secondaryLabel'] }}</a></td>
</tr>
</table>
