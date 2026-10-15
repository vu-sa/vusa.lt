import { mount } from '@vue/test-utils';
import { defineComponent, nextTick, ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import OverviewStatusList from '@/Components/Patterns/OverviewStatusList.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mountPage = (institutionsEmpty = ref(true)) => mount(defineComponent({
  components: { OverviewPage, OverviewSection },
  setup: () => ({ institutionsEmpty }),
  template: `
    <OverviewPage title="Pradžia">
      <OverviewSection title="Tavo institucijos" empty-text="visos fiksuoja laiku" :empty="institutionsEmpty">
        <p data-test="institutions">Rows</p>
      </OverviewSection>
      <OverviewSection title="Posėdžiai" :empty="false">
        <p data-test="meetings">Rows</p>
      </OverviewSection>
    </OverviewPage>
  `,
}));

describe('overview status list', () => {
  it('moves an empty section out of the flow into the status list at the end of the page', async () => {
    const wrapper = mountPage();
    await nextTick();

    const status = wrapper.get('[data-slot="overview-status-list"]');
    expect(status.text()).toContain('shell.chrome.all_clear');
    expect(status.text()).toContain('Tavo institucijos — visos fiksuoja laiku');
    expect(wrapper.findAll('[data-slot="overview-section"]')).toHaveLength(1);
    expect(wrapper.find('[data-test="meetings"]').exists()).toBe(true);
  });

  it('brings the section back and drops the list once it has content', async () => {
    const institutionsEmpty = ref(true);
    const wrapper = mountPage(institutionsEmpty);
    await nextTick();

    institutionsEmpty.value = false;
    await nextTick();

    expect(wrapper.find('[data-slot="overview-status-list"]').exists()).toBe(false);
    expect(wrapper.find('[data-test="institutions"]').exists()).toBe(true);
  });

  it('renders the list where the page places it, and not again at the page end', async () => {
    const wrapper = mount(defineComponent({
      components: { OverviewPage, OverviewSection, OverviewStatusList },
      template: `
        <OverviewPage title="Pradžia">
          <div data-test="tasks-column">
            <OverviewSection title="Tavo institucijos" :empty="true" />
            <OverviewStatusList />
          </div>
          <aside><OverviewSection title="Neseniai redaguota" :empty="true" /></aside>
        </OverviewPage>
      `,
    }));
    await nextTick();

    const lists = wrapper.findAll('[data-slot="overview-status-list"]');
    expect(lists).toHaveLength(1);
    expect(wrapper.get('[data-test="tasks-column"] [data-slot="overview-status-list"]').text())
      .toContain('Neseniai redaguota');
  });

  it('still collapses an empty section to one line outside an overview page', () => {
    const wrapper = mount(OverviewSection, { props: { title: 'Rolės', empty: true, emptyText: 'nėra' } });

    expect(wrapper.get('[data-slot="overview-section"]').text()).toContain('Rolės — nėra');
  });
});
