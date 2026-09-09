<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateUserSettingsRequest;
use App\Mail\NotificationDigest;
use App\Models\User;
use App\Notifications\TestPushNotification;
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

        $user->load('roles:id,name',
            'current_duties:id,name,institution_id',
            'current_duties.roles:id,name', 'current_duties.roles.permissions:id,name',
            'current_duties.institution:id,tenant_id',
            'current_duties.institution.tenant:id,shortname')->makeVisible(['name_was_changed', 'show_pronouns']);

        return $this->inertiaResponse('Admin/ShowUserSettings', [
            'user' => $user->append('has_password')->toFullArray(),
            'notificationPreferences' => $user->notification_preferences,
            'notificationCategories' => NotificationCategory::toOptions(),
            'notificationChannels' => NotificationChannel::toOptions(),
            'availableDigestEmails' => $user->getAvailableDigestEmails(),
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

    public function updateNotificationPreferences(UpdateNotificationPreferencesRequest $request)
    {
        $user = User::find(Auth::id());

        $validated = $request->validated();

        $preferences = $user->notification_preferences;

        if (isset($validated['channels'])) {
            $preferences['channels'] = $validated['channels'];
        }

        if (isset($validated['digest_frequency_hours'])) {
            $preferences['digest_frequency_hours'] = $validated['digest_frequency_hours'];
        }

        if (array_key_exists('digest_emails', $validated)) {
            // Validate against available emails (only store valid ones)
            $availableEmails = collect($user->getAvailableDigestEmails())->pluck('email')->toArray();
            $preferences['digest_emails'] = array_values(array_intersect($validated['digest_emails'] ?? [], $availableEmails));
        }

        if (array_key_exists('muted_until', $validated)) {
            $preferences['muted_until'] = $validated['muted_until'];
        }

        if (isset($validated['reminder_settings'])) {
            $preferences['reminder_settings'] = array_merge(
                $preferences['reminder_settings'] ?? [],
                $validated['reminder_settings']
            );
        }

        $user->notification_preferences = $preferences;
        $user->save();

        return $this->redirectBackWithSuccess(__('messages.dashboard.notification_settings_saved'));
    }

    /**
     * Send a sample digest email to the current user's configured digest addresses.
     *
     * Sent with sendNow() rather than send(): NotificationDigest is a ShouldQueue
     * mailable, so send() would only enqueue it and report success even when the
     * mail server is rejecting everything — which defeats the point of a test.
     */
    public function sendTestNotificationEmail(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $emails = $user->getDigestEmails();

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
