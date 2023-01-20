import posthog from "posthog-js";

export default {
  install(app) {
    app.config.globalProperties.$posthog = posthog.init(
      import.meta.env.VITE_POSTHOG_API_KEY,
      {
        api_host: "https://app.posthog.com",
      }
    );
  },
};
