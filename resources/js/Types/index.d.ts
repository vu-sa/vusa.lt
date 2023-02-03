export {};

import type { PostHog } from "posthog-js";
import type Echo from "laravel-echo";
import type Pusher from "pusher-js/types/src/core/pusher";

declare global {
  const $posthog: PostHog;

  interface Window {
    Echo: Echo;
    Pusher: Pusher;
  }
}
