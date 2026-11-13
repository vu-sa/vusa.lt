import { router, useForm } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { ConfirmDialog, SheetForm } from '@/Components/Patterns';
import { commonStubs } from '@/tests/stubs';
import { KnowledgeSectionKind } from '@/Types/enums';

import LearningSectionsSheet from '../LearningSectionsSheet.vue';
import type { LearningSectionOption } from '../types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
const stubs = {
  ...commonStubs,
  Sheet: commonStubs.Dialog,
  SheetContent: commonStubs.DialogContent,
  SheetHeader: commonStubs.DialogHeader,
  SheetTitle: commonStubs.DialogTitle,
  SheetDescription: commonStubs.DialogDescription,
  ConfirmDialog: true,
};
const section: LearningSectionOption = {
  id: 'section-1', kind: KnowledgeSectionKind.Topic, date: null, title: { lt: 'Mokymai', en: 'Training' }, description: { lt: 'Medžiaga', en: 'Reading' },
  order: 2, tenant_id: 1, tenant: { id: 1, shortname: 'MIF' }, canUpdate: true, canDelete: true,
};
function render(sections = [section], canCreate = true) {
  return mount(LearningSectionsSheet, {
    props: {
      open: true, locale: 'lt', canCreate, sections,
      scopes: {
        create: { canTargetEverywhere: false, tenants: [{ id: 1, shortname: 'MIF' }] },
        update: { canTargetEverywhere: true, tenants: [{ id: 1, shortname: 'MIF' }, { id: 2, shortname: 'IF' }] },
      },
    }, global: { stubs },
  });
}
beforeEach(() => vi.clearAllMocks());

describe('learning sections', () => {
  it('creates a section with bilingual fields and only the units allowed for creation', async () => {
    const wrapper = render();
    const form = vi.mocked(useForm).mock.results[0]!.value;
    await wrapper.get('[data-testid="create-section"]').trigger('click');
    expect(wrapper.get('#section-tenant').findAll('option').map(option => option.text())).toEqual(['forms.placeholders.select_tenant', 'MIF']);
    await wrapper.get('#section-title').setValue('Įvadas');
    await wrapper.get('#section-description').setValue('Perskaityk prieš mokymus');
    await wrapper.get('[data-testid="section-locale-en"]').trigger('click');
    await wrapper.get('#section-title').setValue('Introduction');
    await wrapper.getComponent(SheetForm).vm.$emit('submit');
    expect(form.title).toEqual({ lt: 'Įvadas', en: 'Introduction' });
    expect(form.tenant_id).toBe(1);
    expect(form.post).toHaveBeenCalledWith(expect.stringContaining('knowledgeSections.store'), expect.objectContaining({ preserveScroll: true }));
    wrapper.unmount();
  });

  it('opens straight into one section\'s editor from its page and closes once saved', async () => {
    const wrapper = mount(LearningSectionsSheet, {
      props: {
        open: true, locale: 'lt', canCreate: false, sections: [section], editOnly: 'section-1',
        scopes: { create: { canTargetEverywhere: true, tenants: [] }, update: { canTargetEverywhere: true, tenants: [] } },
      }, global: { stubs },
    });
    const form = vi.mocked(useForm).mock.results[0]!.value;
    expect(wrapper.get<HTMLInputElement>('#section-title').element.value).toBe('Mokymai');
    expect(wrapper.text()).not.toContain('learning_library.section_manager.back');
    await wrapper.getComponent(SheetForm).vm.$emit('submit');
    form.put.mock.calls[0]![1].onSuccess();
    expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false]);
    wrapper.unmount();
  });

  it('edits the selected section and uses the update scope', async () => {
    const wrapper = render();
    const form = vi.mocked(useForm).mock.results[0]!.value;
    await wrapper.get('[data-slot="managed-section"] button[aria-label^="Redaguoti"]').trigger('click');
    expect(wrapper.get<HTMLInputElement>('#section-title').element.value).toBe('Mokymai');
    await wrapper.get('[data-testid="section-locale-en"]').trigger('click');
    expect(wrapper.get<HTMLInputElement>('#section-title').element.value).toBe('Training');
    expect(wrapper.find('#section-tenant').exists()).toBe(false);
    await wrapper.get('[data-testid="section-locale-lt"]').trigger('click');
    await wrapper.get('#section-title').setValue('Atnaujinti mokymai');
    await wrapper.getComponent(SheetForm).vm.$emit('submit');
    expect(form.put).toHaveBeenCalledWith(expect.stringContaining('knowledgeSections.update'), expect.objectContaining({ preserveScroll: true }));
    wrapper.unmount();
  });

  it('reorders sections with the arrows and saves the whole order', async () => {
    const second: LearningSectionOption = { ...section, id: 'section-2', title: { lt: 'Sąvokos', en: 'Terms' }, order: 3 };
    const wrapper = render([section, second]);
    await wrapper.get('[data-testid="reorder-sections-topic"]').trigger('click');
    await wrapper.get('[aria-label="Perkelti žemiau"][title="Mokymai"]').trigger('click');
    await wrapper.getComponent(SheetForm).vm.$emit('submit');
    expect(router.post).toHaveBeenCalledWith(expect.stringContaining('knowledgeSections.reorder'), {
      sections: [{ id: 'section-2', order: 0 }, { id: 'section-1', order: 1 }],
    }, expect.objectContaining({ preserveScroll: true }));
    wrapper.unmount();
    const foreign = render([section, { ...second, canUpdate: false }]);
    expect(foreign.find('[data-testid="reorder-sections-topic"]').exists()).toBe(false);
    foreign.unmount();
  });

  it('confirms deletion and hides actions forbidden on the section', async () => {
    const wrapper = render();
    await wrapper.get('[data-slot="managed-section"]').findAll('button')[1]!.trigger('click');
    expect(router.delete).not.toHaveBeenCalled();
    const confirm = wrapper.findAllComponents(ConfirmDialog).find(dialog => dialog.props('open'))!;
    expect(confirm).toBeDefined();
    await confirm.vm.$emit('confirm');
    expect(router.delete).toHaveBeenCalledWith(expect.stringContaining('knowledgeSections.destroy'), expect.objectContaining({ preserveScroll: true }));
    wrapper.unmount();
    const readonly = render([{ ...section, canUpdate: false, canDelete: false }], false);
    expect(readonly.get('[data-slot="managed-section"]').findAll('button')).toHaveLength(0);
    expect(readonly.find('[data-testid="create-section"]').exists()).toBe(false);
    readonly.unmount();
  });
});
