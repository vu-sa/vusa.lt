import posthog from 'posthog-js';

export default {
  install(app) {
    posthog.init(import.meta.env.VITE_POSTHOG_API_KEY, {
      api_host: 'https://eu.posthog.com',
      autocapture: {
        dom_event_allowlist: ['click'],
        element_allowlist: ['a', 'button'],
      },
      persistence: 'memory',
    });
  },
};
