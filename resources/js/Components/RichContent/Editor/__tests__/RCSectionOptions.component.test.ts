import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCSectionOptions from '../RCSectionOptions.vue';

interface SectionOptions {
  title?: string;
  subtitle?: string;
  presentation?: string;
  headingLevel?: number;
  align?: string;
  showSeparator?: boolean;
}

function makeOptions(): SectionOptions {
  return { title: '', subtitle: '' };
}

/** The collapsible trigger is a <button> carrying the "section options" heading; in the
 *  flat mode that heading is a <label>, so no button matches. That distinguishes the
 *  two render modes without depending on reka-ui internals shared with Select. */
function findTrigger(wrapper: ReturnType<typeof mount>) {
  return wrapper.findAll('button').find(b => b.text().includes('rich-content.section_options'));
}

describe('RCSectionOptions', () => {
  it('collapses the fields behind a trigger by default and reveals them on click', async () => {
    const wrapper = mount(RCSectionOptions, { props: { modelValue: makeOptions() } });

    const trigger = findTrigger(wrapper);
    expect(trigger).toBeTruthy();
    expect(trigger!.attributes('aria-expanded')).toBe('false');

    // Collapsed: the inner title/subtitle inputs are not rendered yet.
    expect(wrapper.findAll('input[type="text"]')).toHaveLength(0);

    await trigger!.trigger('click');

    expect(trigger!.attributes('aria-expanded')).toBe('true');
    // Title + subtitle + eyebrow = three text inputs; headingLevel/align = two selects;
    // the separator toggle = one switch. Presentation is a 3-button picker, not a select/switch.
    expect(wrapper.findAll('input[type="text"]')).toHaveLength(3);
    expect(wrapper.findAll('[data-slot="select-trigger"]')).toHaveLength(2);
    expect(wrapper.findAll('button[role="switch"]')).toHaveLength(1);
  });

  it('renders the fields flat (always visible) when collapsible=false', () => {
    const wrapper = mount(RCSectionOptions, {
      props: { modelValue: makeOptions(), collapsible: false },
    });

    // No trigger button — the section options header is a plain FieldLabel (<label>).
    expect(findTrigger(wrapper)).toBeUndefined();
    expect(wrapper.findAll('input[type="text"]')).toHaveLength(3);
    expect(wrapper.findAll('[data-slot="select-trigger"]')).toHaveLength(2);
    expect(wrapper.findAll('button[role="switch"]')).toHaveLength(1);
  });

  it('mutates the shared options object in place when a field is edited', async () => {
    // Every RichContent editor mutates its defineModel object in place to preserve
    // object identity (see ContentEditorFactory); the title input must do the same.
    const options = makeOptions();
    const wrapper = mount(RCSectionOptions, {
      props: { modelValue: options, collapsible: false },
    });

    await wrapper.findAll('input[type="text"]')[0]!.setValue('Mano antraštė');

    expect(options.title).toBe('Mano antraštė');
  });
});
