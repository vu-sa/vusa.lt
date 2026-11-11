import { router } from '@inertiajs/vue3';
import { beforeEach, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import { showWikiProgressToast } from '@/Features/Admin/Knowledge/wikiProgressToast';

import { useStepProgress } from '../composables/useStepProgress';
import { knowledge } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Features/Admin/Knowledge/wikiProgressToast', async importOriginal => ({
  ...(await importOriginal<typeof import('@/Features/Admin/Knowledge/wikiProgressToast')>()),
  showWikiProgressToast: vi.fn(),
}));

beforeEach(() => vi.clearAllMocks());

it('shows the Wiki progress toast from the flash the tick returns, and nothing without it', () => {
  const { checked } = useStepProgress(ref(knowledge()), ref(false));
  checked.value = true;
  const options = vi.mocked(router.post).mock.calls[0]![2] as { onFlash: (flash: Record<string, unknown>) => void };

  options.onFlash({});
  expect(showWikiProgressToast).not.toHaveBeenCalled();
  options.onFlash({ wikiProgress: { title: 'Read the guide', done: 2, total: 5, previousDone: 1 } });
  expect(showWikiProgressToast).toHaveBeenCalledWith({ title: 'Read the guide', done: 2, total: 5, previousDone: 1 });
});
