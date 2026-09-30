import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SocialEmbedEditor from '../SocialEmbedEditor.vue';

describe('SocialEmbedEditor', () => {
  it('labels its controls and identifies a supported URL', async () => {
    const content: { url: string; platform: string | null } = { url: '', platform: null };
    const wrapper = mount(SocialEmbedEditor, {
      props: { modelValue: content, options: { showCaption: true } },
      global: { stubs: { SocialEmbedPreview: true } },
    });

    const url = wrapper.find('input[type="url"]');
    expect(wrapper.find(`label[for="${url.attributes('id')}"]`).exists()).toBe(true);
    const checkbox = wrapper.find('[role="checkbox"]');
    expect(wrapper.find(`label[for="${checkbox.attributes('id')}"]`).exists()).toBe(true);

    await url.setValue('https://www.instagram.com/p/example');
    expect(content.platform).toBe('instagram');
    expect(wrapper.text()).toContain('Nuoroda atpažinta');
  });
});
