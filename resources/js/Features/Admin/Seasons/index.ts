/**
 * The winter season's public face: the only module the rest of the app may import from this
 * feature (ESLint `import-x/no-restricted-paths`). Each export is a single seam in shared code;
 * anything heavy is lazy, so a dormant season costs the shared bundles next to nothing.
 */
import { defineAsyncComponent } from 'vue';

/** The week before the season on Pradžia: the days left, in straw. */
export const SeasonCountdown = defineAsyncComponent(() => import('./countdown/SeasonCountdown.vue'));
