import { flushPromises, mount } from '@vue/test-utils';
import { expect, it } from 'vitest';

import { controlVariants } from '@/Components/ui/control';
import { commonStubs } from '@/tests/stubs';
import LearningTerms from '../LearningTerms.vue';

it('opens each definition from a sentence case bordered chip', async () => {
  const wrapper = mount(LearningTerms, {
    attachTo: document.body,
    props: { terms: [{ id: 'quorum', term: 'Quorum', abbreviation: '', definition: 'Required number of members.' }] },
    global: { stubs: commonStubs },
  });
  const chip = wrapper.get('button');
  expect(chip.text()).toBe('Quorum');
  for (const name of controlVariants({ size: 'sm', voice: 'sentence' }).split(' ')) {
    expect(chip.classes()).toContain(name);
  }
  await chip.trigger('click');
  await flushPromises();
  expect(document.body.textContent).toContain('Required number of members.');
  wrapper.unmount();
});

it('labels a chip with the abbreviation and spells it out in the definition', async () => {
  const wrapper = mount(LearningTerms, {
    attachTo: document.body,
    props: { terms: [{ id: 'spk', term: 'Studijų programos komitetas', abbreviation: 'SPK', definition: 'Programos komitetas.' }] },
    global: { stubs: commonStubs },
  });
  const chip = wrapper.get('button');
  expect(chip.text()).toBe('SPK');
  expect(chip.attributes('title')).toBe('Studijų programos komitetas');
  await chip.trigger('click');
  await flushPromises();
  expect(document.body.textContent).toContain('Studijų programos komitetas (SPK)');
  wrapper.unmount();
});
