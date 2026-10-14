import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { afterEach, describe, expect, it, vi } from 'vitest';

import StagingWatermark from '@/Components/StagingWatermark.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function mockStaging(staging: Record<string, boolean>) {
  vi.mocked(usePage).mockReturnValue(createMockPage({ staging }));
}

describe('StagingWatermark', () => {
  afterEach(() => {
    vi.clearAllMocks();
  });

  it('covers the viewport on staging without blocking clicks or screen readers', () => {
    mockStaging({ isStaging: true, filesReadOnly: false, sharepointReadOnly: false });

    const watermark = mount(StagingWatermark).get('[data-slot="staging-watermark"]');

    expect(watermark.attributes('aria-hidden')).toBe('true');
    expect(watermark.classes()).toEqual(expect.arrayContaining(['fixed', 'inset-0', 'pointer-events-none', 'print:hidden']));
    expect(watermark.attributes('style')).toContain('STAGING');
    expect(watermark.attributes('style')).toContain(encodeURIComponent('BANDOMOJI APLINKA'));
  });

  it('renders nothing outside staging', () => {
    mockStaging({ isStaging: false, filesReadOnly: false, sharepointReadOnly: false });

    expect(mount(StagingWatermark).find('[data-slot="staging-watermark"]').exists()).toBe(false);
  });
});
