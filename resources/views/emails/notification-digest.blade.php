<x-mail::message>
# {{ \App\Support\MailGreeting::forName($user->name) }}

@foreach ($categorizedItems as $categoryLabel => $items)
<x-mail::digest-category
:label="$categoryLabel"
:color="$categoryColors[$categoryLabel]"
:items="$items"
:remaining="$remainingCounts[$categoryLabel]"
:more-url="$dashboardUrl" />

@endforeach
<x-mail::button color="neutral" align="left" :url="$dashboardUrl">
{{ __('notifications.digest_open_system') }}
</x-mail::button>

<x-slot:subcopy>
[{{ __('notifications.digest_footer') }}]({{ $settingsUrl }})
</x-slot:subcopy>
</x-mail::message>
