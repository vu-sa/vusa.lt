import { defineComponent, nextTick, ref } from 'vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { toast } from 'vue-sonner';

import { usePublicEditLinkToast, type PublicEditLink } from '../usePublicEditLinkToast';

vi.mock('vue-sonner', () => ({
  toast: Object.assign(
    vi.fn(),
    { dismiss: vi.fn() },
  ),
}));

const LINK: PublicEditLink = { url: 'https://www.vusa.test/mano/pages/1/edit', type: 'page', id: 1 };
const OTHER_LINK: PublicEditLink = { url: 'https://www.vusa.test/mano/news/2/edit', type: 'news', id: 2 };

const openMock = vi.fn();

beforeEach(() => {
  vi.clearAllMocks();
  vi.stubGlobal('open', openMock);
});

/**
 * The composable registers onMounted, so it must run inside a component — same as
 * its real host (PublicLayout), whose onMounted fires after the <Toaster> child
 * has mounted and subscribed.
 */
function mountWithComposable(linkGetter: () => PublicEditLink | null | undefined): void {
  mount(defineComponent({
    setup() {
      usePublicEditLinkToast(linkGetter);
      return () => null;
    },
  }));
}

describe('usePublicEditLinkToast', () => {
  it('shows a persistent toast with the edit action on initial mount', () => {
    const link = ref<PublicEditLink | null>(LINK);

    mountWithComposable(() => link.value);

    expect(toast).toHaveBeenCalledTimes(1);
    const [message, options] = vi.mocked(toast).mock.calls[0]!;
    expect(message).toBe('edit-link.hint');
    expect(options.id).toBe('public-edit-link:page:1');
    expect(options.duration).toBe(Infinity);
    expect(options.closeButton).toBe(true);
    expect(options.action.label).toBe('edit-link.edit');
    expect(options.class).toBe('public-toast');
  });

  it('opens the edit page in a new tab when the action is clicked', () => {
    const link = ref<PublicEditLink | null>(LINK);

    mountWithComposable(() => link.value);

    const options = vi.mocked(toast).mock.calls[0]![1];
    options.action.onClick();
    expect(openMock).toHaveBeenCalledWith(LINK.url, '_blank', 'noopener');
  });

  it('dismisses the toast when navigating to a non-editable page', async () => {
    const link = ref<PublicEditLink | null>(LINK);

    mountWithComposable(() => link.value);
    expect(toast.dismiss).not.toHaveBeenCalled();

    link.value = null;
    await nextTick();
    expect(toast.dismiss).toHaveBeenCalledWith('public-edit-link:page:1');
    expect(toast).toHaveBeenCalledTimes(1);
  });

  it('destroys the previous toast and shows a fresh one on a different record', async () => {
    const link = ref<PublicEditLink | null>(LINK);

    mountWithComposable(() => link.value);

    link.value = OTHER_LINK;
    await nextTick();

    // Old toast destroyed, new one with its own id — not an in-place update
    expect(toast.dismiss).toHaveBeenCalledWith('public-edit-link:page:1');
    expect(toast).toHaveBeenCalledTimes(2);
    expect(vi.mocked(toast).mock.calls[1]![1].id).toBe('public-edit-link:news:2');
  });

  it('keeps the toast when the same record is revisited (partial reload)', async () => {
    const link = ref<PublicEditLink | null>(LINK);

    mountWithComposable(() => link.value);

    link.value = { ...LINK };
    await nextTick();

    expect(toast).toHaveBeenCalledTimes(1);
    expect(toast.dismiss).not.toHaveBeenCalled();
  });

  it('shows nothing for guests (null link from the start)', () => {
    const link = ref<PublicEditLink | null>(null);

    mountWithComposable(() => link.value);

    expect(toast).not.toHaveBeenCalled();
    expect(toast.dismiss).not.toHaveBeenCalled();
  });
});
