export {};

import type Echo from "laravel-echo";
import type Pusher from "pusher-js/types/src/core/pusher";

declare global {
  interface Window {
    Echo: Echo;
    Pusher: Pusher;
  }
}
