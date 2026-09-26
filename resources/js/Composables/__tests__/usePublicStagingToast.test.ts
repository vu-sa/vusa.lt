import { defineComponent, nextTick, ref } from 'vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { toast } from 'vue-sonner';

import { usePublicStagingToast, type PublicStagingState } from '../usePublicStagingToast';

vi.mock('vue-sonner', () => ({
  toast: Object.assign(
    vi.fn(),
    { dismiss: vi.fn() },
  ),
}));

const STAGING: PublicStagingState = {
  isStaging: true,
  filesReadOnly: true,
  sharepointReadOnly: true,
};

beforeEach(() => {
  vi.clearAllMocks();
});

function mountWithComposable(
  stagingGetter: () => PublicStagingState | null | undefined,
  pageKeyGetter: () => string = () => '/lt',
): void {
  mount(defineComponent({
    setup() {
      usePublicStagingToast(stagingGetter, pageKeyGetter);
      return () => null;
    },
  }));
}

describe('usePublicStagingToast', () => {
  it('shows a closable staging toast with shared-resource warnings', () => {
    const staging = ref<PublicStagingState | null>(STAGING);

    mountWithComposable(() => staging.value);

    const [title, options] = vi.mocked(toast).mock.calls[0]!;
    expect(title).toBe('STAGING ENVIRONMENT');
    expect(options.id).toBe('public-staging-status:1');
    expect(options.description).toBe('File storage is shared with production (read-only) · SharePoint is shared with production (read-only)');
    expect(options.duration).toBe(Infinity);
    expect(options.closeButton).toBe(true);
    expect(options.dismissible).toBe(true);
  });

  it('shows the staging toast again on a new page', async () => {
    const staging = ref<PublicStagingState | null>(STAGING);
    const pageKey = ref('/lt');

    mountWithComposable(() => staging.value, () => pageKey.value);
    pageKey.value = '/lt/naujienos';
    await nextTick();

    expect(toast.dismiss).toHaveBeenCalledWith('public-staging-status:1');
    expect(toast).toHaveBeenCalledTimes(2);
    expect(vi.mocked(toast).mock.calls[1]![1].id).toBe('public-staging-status:2');
  });

  it('dismisses the toast when staging is no longer active', async () => {
    const staging = ref<PublicStagingState | null>(STAGING);

    mountWithComposable(() => staging.value);
    staging.value = { ...STAGING, isStaging: false };
    await nextTick();

    expect(toast.dismiss).toHaveBeenCalledWith('public-staging-status:1');
  });

  it('shows nothing outside staging', () => {
    const staging = ref<PublicStagingState | null>({ ...STAGING, isStaging: false });

    mountWithComposable(() => staging.value);

    expect(toast).not.toHaveBeenCalled();
  });
});
