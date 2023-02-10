export {};

import { trans } from "laravel-vue-i18n";
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

declare module "@vue/runtime-core" {
  interface ComponentCustomProperties {
    $t: typeof trans;
  }
}
