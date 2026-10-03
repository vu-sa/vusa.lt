@props(['question'])
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;table-layout:fixed;margin:16px 0;">
<tr>
<td width="50%" style="width:50%;padding-right:4px;vertical-align:middle;"><a href="{{ $question['met'] }}" style="display:block;padding:14px 8px;text-align:center;background:#bd1737;color:#ffffff;text-decoration:none;font-weight:bold;">{{ $question['primaryLabel'] }}</a></td>
<td width="50%" style="width:50%;padding-left:4px;vertical-align:middle;"><a class="button-secondary" href="{{ $question['notMet'] }}" style="display:block;padding:13px 8px;text-align:center;border:1px solid #71717a;color:#27272a;text-decoration:none;font-weight:bold;">{{ $question['secondaryLabel'] }}</a></td>
</tr>
</table>
