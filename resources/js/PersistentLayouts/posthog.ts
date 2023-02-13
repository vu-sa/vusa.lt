import { usePage } from "@inertiajs/vue3";
import posthog from "posthog-js";

if (usePage().props?.auth?.user) {
  posthog.identify(usePage().props.auth?.user.id);
}
