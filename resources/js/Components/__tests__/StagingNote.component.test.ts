import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { afterEach, describe, expect, it, vi } from 'vitest';

import StagingNote from '@/Components/StagingNote.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('StagingNote', () => {
  afterEach(() => {
    vi.clearAllMocks();
  });

  it('shows the difference for its topic on staging', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      staging: { isStaging: true, filesReadOnly: true, sharepointReadOnly: false, mailToRequester: true },
    }));

    expect(mount(StagingNote, { props: { topic: 'mail' } }).get('[data-slot="staging-note"]').text()).toContain('staging.topics.mail');
    expect(mount(StagingNote, { props: { topic: 'files' } }).text()).toContain('staging.topics.files');
    expect(mount(StagingNote, { props: { topic: 'sharepoint' } }).text()).toContain('staging.topics.sharepoint_test_site');
  });

  it('renders nothing when the difference does not apply', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      staging: { isStaging: true, filesReadOnly: false, sharepointReadOnly: false },
    }));

    expect(mount(StagingNote, { props: { topic: 'files' } }).find('[data-slot="staging-note"]').exists()).toBe(false);
    expect(mount(StagingNote, { props: { topic: 'mail' } }).find('[data-slot="staging-note"]').exists()).toBe(false);
  });

  it('renders nothing outside staging', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({}));

    expect(mount(StagingNote, { props: { topic: 'mail' } }).find('[data-slot="staging-note"]').exists()).toBe(false);
  });
});
