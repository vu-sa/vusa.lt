<x-mail::message>
# {{ __('notifications.digest_greeting', ['name' => $user->name]) }}

{{ trans_choice('notifications.digest_subject', $totalCount, ['count' => $totalCount]) }}

@foreach ($categorizedItems as $categoryLabel => $items)
<x-mail::digest-category
:label="$categoryLabel"
:color="$categoryColors[$categoryLabel]"
:items="$items"
:remaining="$remainingCounts[$categoryLabel]"
:more-url="$dashboardUrl" />

@endforeach
<x-mail::button :url="$dashboardUrl">
{{ __('notifications.digest_open_system') }}
</x-mail::button>

<x-slot:subcopy>
[{{ __('notifications.digest_footer') }}]({{ $settingsUrl }})
</x-slot:subcopy>
</x-mail::message>
