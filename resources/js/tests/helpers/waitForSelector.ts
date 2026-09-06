import { vi } from 'vitest';
import type { VueWrapper } from '@vue/test-utils';

/**
 * Poll until `selector` appears in the mounted wrapper's DOM.
 *
 * Needed after mounting a component with lazy-loaded (`defineAsyncComponent`) children:
 * resolving a dynamic `.vue` import can take longer than a single `flushPromises()`
 * round trip, especially the first time that chunk is transformed in a test run. A
 * plain `await flushPromises()` is enough on a warm cache but flakes on a cold one —
 * polling is the only reliable wait.
 */
export async function waitForSelector(wrapper: VueWrapper<unknown>, selector: string): Promise<void> {
  await vi.waitFor(() => {
    if (!wrapper.find(selector).exists()) throw new Error(`Selector not ready yet: ${selector}`);
  });
}
