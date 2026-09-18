# Phase 0.4 manual testing — session continuity and Microsoft logout

Use this checklist after deploying phase 0.4 to an environment connected to Microsoft Entra. The
automated tests cover the application session and generated logout URL; this pass checks browser
cookies, Microsoft account selection and the real Entra redirect flow.

## Before testing

For the environment under test, confirm that:

- `SESSION_EXPIRE_ON_CLOSE=false`;
- `MICROSOFT_POST_LOGOUT_REDIRECT_URI` is the environment's exact `/login` URL;
- both `/auth/microsoft/callback` and `/login` are registered under the app registration's
  **Authentication → Web → Redirect URIs**;
- the **Front-channel logout URL** is empty — phase 0.4 does not implement that callback;
- the `login_hint` optional claim is enabled for the ID token under **Token configuration**;
- cached Laravel configuration was rebuilt after changing environment variables.

Test with a user who can access Mano VU SA and, where possible, has two Microsoft accounts in the
same browser. Use a normal browser window: private windows often delete their cookies when closed
and cannot prove session continuity.

## Core scenarios

### 1. Closing the browser does not immediately end the VU SA session

1. Sign in to Mano VU SA.
2. Close every window of that browser, wait briefly, and reopen it.
3. Open Mano VU SA again before the configured `SESSION_LIFETIME` has elapsed.

Expected: the user is still signed in and is not sent through Microsoft authentication. Check that
ordinary navigation never shows the Microsoft account picker. This does not mean the session is
permanent: it should still expire after the configured idle lifetime.

### 2. Local logout affects only Mano VU SA

1. Open the account menu.
2. Choose **Atsijungti**.
3. Confirm that a protected Mano VU SA URL now sends the user to `/login`.
4. Choose Microsoft login again.

Expected: the VU SA session is gone, but the Microsoft browser session remains. The Microsoft
account picker is still shown when the new OAuth login starts, allowing the user to select the right
account instead of silently using another signed-in account.

### 3. Microsoft logout clears both sessions

1. Sign in through Microsoft and open the account menu.
2. Choose **Atsijungti ir iš „Microsoft“**.
3. Observe the Microsoft logout redirect and its return to the configured `/login` URL.
4. Try opening a protected Mano VU SA URL.

Expected: the application session is invalidated immediately, Microsoft completes its logout flow,
and the browser returns to the same environment—not production when testing staging, nor staging
when testing production. With `login_hint` configured, Microsoft should normally know which account
to log out without displaying another account-selection screen.

Microsoft can retain device-level SSO through a Primary Refresh Token, and other Microsoft apps
such as Teams or Outlook may remain signed in. A later Microsoft login may therefore still offer or
silently re-establish the device account. That is Microsoft platform behavior, not evidence that the
VU SA session survived logout.

### 4. Multiple Microsoft accounts

Repeat both logout choices with two Microsoft accounts present in the browser.

Look for:

- the sign-in picker lists the expected accounts;
- selecting one account signs in as the matching VU SA user;
- local logout does not unexpectedly terminate either Microsoft session;
- Microsoft logout targets the account used for the current VU SA login;
- no account identifier, email address or logout hint appears in the VU SA page or flash messages.

### 5. Logout hint fallback

Temporarily test without the `login_hint` optional claim only if the Entra configuration is still
being rolled out.

Expected: Microsoft may ask which account to sign out, but completing that screen still returns to
the configured `/login` URL and the VU SA session is already invalid. Re-enable the claim after this
check.

## Environment matrix

Run scenarios 2 and 3 in every deployed environment. Verify exact scheme, host, port and path.

| Environment | Sign-in callback | Post-logout return |
|---|---|---|
| Production | `https://www.vusa.lt/auth/microsoft/callback` | `https://www.vusa.lt/login` |
| Staging | `https://www.naujas.vusa.lt/auth/microsoft/callback` | `https://www.naujas.vusa.lt/login` |
| Local | `http://www.vusa.test/auth/microsoft/callback` | `http://www.vusa.test/login` |

Also run scenario 1 in one Chromium browser and one mobile browser or installed PWA. A private
window is useful for account isolation, but not for testing persistence after closing the browser.

## Failure clues

| Symptom | Check first |
|---|---|
| Microsoft reports a redirect URI mismatch | `/login` is registered as a Web redirect URI and exactly matches `MICROSOFT_POST_LOGOUT_REDIRECT_URI` |
| Staging logout returns to production | staging environment configuration and its cached config |
| Microsoft asks which account to log out | the ID-token `login_hint` optional claim is enabled and a fresh login occurred after enabling it |
| Closing the browser logs the user out immediately | `SESSION_EXPIRE_ON_CLOSE`, cached config, private browsing, or browser cookie-clearing settings |
| Back button shows a protected page after logout | reload the page; it must redirect to `/login` and must not return protected data |
| Microsoft account is offered again after logout | expected with device SSO/Primary Refresh Token; verify the VU SA session itself is gone |

When reporting a failure, record the environment, browser, logout option used, start and final host,
and whether one or several Microsoft accounts were active. Do not copy access tokens, authorization
codes or the `logout_hint` value into an issue.
