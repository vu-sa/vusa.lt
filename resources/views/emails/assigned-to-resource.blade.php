<x-mail::message>

{{ __('notifications.assigned_to_resource_body', ['assigner' => $assigner['name'], 'resource' => $resource['name']]) }}

<x-mail::button :url="$resource['url']">
{{ __('notifications.action_view_resource') }}
</x-mail::button>

</x-mail::message>
