import { h, markRaw } from 'vue';
import { toast } from 'vue-sonner';

import WikiProgressToast from './WikiProgressToast.vue';

/** Flashed by the progress endpoint when an article is ticked as learned. */
export interface WikiProgressFlash {
  title: string;
  done: number;
  total: number;
  previousDone: number;
}

export function isWikiProgressFlash(value: unknown): value is WikiProgressFlash {
  return typeof value === 'object' && value !== null
    && ['done', 'total', 'previousDone'].every(key => typeof (value as Record<string, unknown>)[key] === 'number');
}

export function showWikiProgressToast(progress: WikiProgressFlash): void {
  toast.custom(markRaw(h(WikiProgressToast, { progress })), { duration: 4500 });
}
