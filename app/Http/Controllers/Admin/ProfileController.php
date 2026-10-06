<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetUserAccessSummary;
use App\Enums\NotificationType;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Requests\MuteNotificationsRequest;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateUserSettingsRequest;
use App\Mail\NotificationDigest;
use App\Models\User;
use App\Notifications\TestPushNotification;
use App\Services\NotificationRouter;
use App\Services\Notifications\NotificationAudience;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ProfileController extends AdminController
{
    use ApiResponses;

    public function userSettings()
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);

        // The roles list moved to `profile.roles` (PR 5.9); only the shallow relations stay in the payload.
        $user->load('roles:id,name', 'current_duties:id,name,institution_id')->makeVisible(['name_was_changed', 'show_pronouns']);

        return $this->inertiaResponse('Admin/ShowProfile', [
            'user' => $user->append('has_password')->toFullArray(),
        ]);
    }

    /**
     * Read-only and self-scoped: the subject is always the acting user, so there is nothing to authorize against.
     */
    public function roles()
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);

        return $this->inertiaResponse('Admin/ShowMyRoles', [
            'access' => GetUserAccessSummary::execute($user),
        ]);
    }

    /**
     * Read-only and self-scoped, like `roles()`: the subject is always the acting user.
     */
    public function notificationSettings(NotificationAudience $audience)
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);
        $preferences = $user->notification_preferences;

        return $this->inertiaResponse('Admin/ShowNotificationSettings', [
            'notificationTypes' => array_map(fn (NotificationType $type): array => [
                'value' => $type->value,
                'section' => $type->section()->value,
                'sectionModelEnumKey' => $type->section()->modelEnumKey(),
                'lockedEmail' => $type->lockedEmail()?->value,
                'email' => $user->emailDeliveryFor($type)->value,
                'push' => $user->wantsPushFor($type),
            ], $audience->typesFor($user)),
            'notificationPreferences' => [
                'digest_frequency_hours' => $preferences['digest_frequency_hours'],
                'emails' => $preferences['emails'],
                'muted_until' => $user->isGloballyMuted() ? $preferences['muted_until'] : null,
                'reminder_settings' => $preferences['reminder_settings'],
            ],
            'availableEmails' => $user->getAvailableNotificationEmails(),
            'defaultEmail' => app(NotificationRouter::class)->preferredEmail($user),
        ]);
    }

    public function updateUserSettings(UpdateUserSettingsRequest $request)
    {
        $user = User::find(Auth::id());

        $validated = $request->validated();

        // The display name may only be changed once.
        if (array_key_exists('name', $validated) && $user->name !== $validated['name'] && ! $user->name_was_changed) {
            $user->name_was_changed = true;
        } else {
            unset($validated['name']);
        }

        $user->update($validated);

        return $this->redirectBackWithSuccess(__('messages.dashboard.settings_saved'));
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = User::find(Auth::id());

        $user->password = bcrypt($request->password);
        $user->save();

        return redirect()->back()->with('success', __('messages.auth.password_changed'));
    }

    /**
     * Stores per-type choices as overrides only, so a changed default still reaches this user
     * for every type they never touched.
     */
    public function updateNotificationPreferences(UpdateNotificationPreferencesRequest $request)
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);
        $validated = $request->validated();
        $preferences = $user->notification_preferences;

        if (array_key_exists('types', $validated)) {
            $preferences['types'] = $this->typeOverrides($validated['types'] ?? []);
        }

        if (array_key_exists('digest_frequency_hours', $validated)) {
            $preferences['digest_frequency_hours'] = $validated['digest_frequency_hours'];
        }

        if (array_key_exists('emails', $validated)) {
            $available = array_column($user->getAvailableNotificationEmails(), 'email');
            $preferences['emails'] = array_values(array_intersect($validated['emails'], $available));
        }

        foreach (['task_reminder_days', 'meeting_reminder_hours'] as $key) {
            if (array_key_exists($key, $validated['reminder_settings'] ?? [])) {
                $values = array_values(array_unique(array_map(intval(...), $validated['reminder_settings'][$key])));
                rsort($values);
                $preferences['reminder_settings'][$key] = $values;
            }
        }

        $user->notification_preferences = $preferences;
        $user->save();

        return $this->redirectBackWithSuccess(__('messages.dashboard.notification_settings_saved'));
    }

    /**
     * Back to the defaults for every type, reminder and the digest frequency; the chosen addresses
     * and an active mute stay, since those are not preferences with a default.
     */
    public function resetNotificationPreferences()
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);
        $preferences = $user->notification_preferences;

        $user->notification_preferences = [
            'emails' => $preferences['emails'],
            'muted_until' => $preferences['muted_until'],
        ];
        $user->save();

        return $this->redirectBackWithSuccess(__('notifications.preferences.reset_done'));
    }

    public function muteNotifications(MuteNotificationsRequest $request)
    {
        $user = User::query()->find(Auth::id()) ?? abort(404);
        $hours = $request->validated('hours');

        $user->muteNotificationsUntil($hours === null ? null : now()->addHours((int) $hours));

        return $this->redirectBackWithSuccess($hours === null
            ? __('notifications.preferences.unmuted')
            : __('notifications.preferences.muted_until', ['date' => now()->addHours((int) $hours)->format('Y-m-d H:i')]));
    }

    /**
     * @param  array<string, array{email?: string, push?: bool}>  $types
     * @return array<string, array{email?: string, push?: bool}>
     */
    private function typeOverrides(array $types): array
    {
        $overrides = [];

        foreach ($types as $value => $choice) {
            $type = NotificationType::from($value);
            $override = [];

            if (isset($choice['email']) && $type->lockedEmail() === null && $choice['email'] !== $type->defaultEmail()->value) {
                $override['email'] = $choice['email'];
            }

            if (isset($choice['push']) && (bool) $choice['push'] !== $type->defaultPush()) {
                $override['push'] = (bool) $choice['push'];
            }

            if ($override !== []) {
                $overrides[$value] = $override;
            }
        }

        return $overrides;
    }

    /**
     * Send a sample digest email to the addresses the user's notifications go to.
     *
     * Sent with sendNow() rather than send(): NotificationDigest is a ShouldQueue
     * mailable, so send() would only enqueue it and report success even when the
     * mail server is rejecting everything — which defeats the point of a test.
     */
    public function sendTestNotificationEmail(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $emails = $user->notificationEmails();

        // Build the sample from a real notification so the item shape cannot drift
        // away from what the digest template expects.
        $notification = new TestPushNotification;

        $sampleItems = [
            $notification->category()->value => [$notification->toDigestItem($user)],
        ];

        try {
            Mail::to($emails)->sendNow(new NotificationDigest($user, $sampleItems));
        } catch (TransportExceptionInterface $e) {
            return $this->jsonError(
                __('notifications.test_email_failed', ['error' => $e->getMessage()]),
                500
            );
        }

        return $this->jsonSuccess(
            message: __('notifications.test_email_sent', ['emails' => implode(', ', $emails)])
        );
    }
}
