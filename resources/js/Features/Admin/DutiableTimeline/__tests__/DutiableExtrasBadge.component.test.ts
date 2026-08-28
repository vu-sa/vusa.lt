import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import DutiableExtrasBadge from '../DutiableExtrasBadge.vue';
import type { ParsedRow } from '../types';

/**
 * The shared Tooltip stub drops the floating content, so the tooltip body is rendered
 * inline here instead — what is being tested is which entries the badge builds, not
 * reka-ui's positioning.
 */
const stubs = {
  Tooltip: { template: '<div><slot /></div>' },
  TooltipTrigger: { template: '<div><slot /></div>' },
  TooltipContent: { template: '<div><slot /></div>' },
};

function mountBadge(extras: ParsedRow['extras']) {
  return mount(DutiableExtrasBadge, { props: { extras }, global: { stubs } });
}

describe('DutiableExtrasBadge', () => {
  it('renders nothing when the row is only a period', () => {
    expect(mountBadge(null).find('[aria-label]').exists()).toBe(false);
  });

  it('shows the assignment photo rather than announcing that one exists', () => {
    const wrapper = mountBadge({ photo: 'https://cdn.example/contacts/petras.jpg' });

    expect(wrapper.find('img').attributes('src')).toBe('https://cdn.example/contacts/petras.jpg');
    expect(wrapper.text()).not.toContain('photo_set');
  });

  it('shows the study programme note beside the programme', () => {
    const wrapper = mountBadge({ study_program: 'Programų sistemos', study_program_note: '1 grupė' });

    expect(wrapper.text()).toContain('Programų sistemos');
    expect(wrapper.text()).toContain('1 grupė');
  });

  it('truncates a long description', () => {
    const wrapper = mountBadge({ description: 'a'.repeat(200) });

    expect(wrapper.text()).toContain('…');
    expect(wrapper.text()).not.toContain('a'.repeat(200));
  });
});
