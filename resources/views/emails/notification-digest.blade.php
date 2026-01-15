<x-mail::message>
# {{ __('notifications.digest_greeting', ['name' => $user->name]) }}

{{ __('notifications.digest_intro', ['count' => $totalCount]) }}

@foreach ($categorizedItems as $categoryLabel => $items)
## {{ $categoryLabel }}

@foreach ($items as $item)
<x-mail::panel>
**{{ $item['icon'] }} {{ $item['title'] }}**

{{ $item['body'] }}

<x-mail::button :url="$item['url']">
{{ __('Peržiūrėti') }}
</x-mail::button>
</x-mail::panel>
@endforeach

@endforeach

---

<small>{{ __('notifications.digest_footer') }}</small>

{{ __('Ačiū') }},<br>
{{ config('app.name') }}
</x-mail::message>
