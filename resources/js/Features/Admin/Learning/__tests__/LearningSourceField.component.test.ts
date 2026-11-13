import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import LearningSourceField, { type LearningSourceDraft } from '../LearningSourceField.vue';

import { commonStubs } from '@/tests/stubs';

describe('LearningSourceField PDF hint', () => {
  it.each([
    ['https://x.test/a.PDF?x=1', true],
    ['https://x.test/a.pdf#page=2', true],
    ['https://x.test/page', false],
    ['https://x.test/page?file=a.pdf', false],
    ['invalid url', false],
  ])('shows the archive hint for PDF paths: %s', (url, visible) => {
    const modelValue: LearningSourceDraft = { lt: { document_id: null, url }, en: { document_id: null, url: '' } };
    const wrapper = mount(LearningSourceField, {
      props: { locale: 'lt', errors: {}, modelValue },
      global: { stubs: commonStubs },
    });

    expect(wrapper.find('[data-testid="source-pdf-hint-lt"]').exists()).toBe(visible);
    wrapper.unmount();
  });

  it('uses the selected locale when deciding whether to show the hint', async () => {
    const wrapper = mount(LearningSourceField, {
      props: {
        locale: 'lt', errors: {},
        modelValue: { lt: { document_id: null, url: 'https://x.test/page' }, en: { document_id: null, url: 'https://x.test/file.pdf' } },
      },
      global: { stubs: commonStubs },
    });
    expect(wrapper.find('[data-testid="source-pdf-hint-lt"]').exists()).toBe(false);

    await wrapper.setProps({ locale: 'en' });

    expect(wrapper.find('[data-testid="source-pdf-hint-en"]').exists()).toBe(true);
    wrapper.unmount();
  });
});
