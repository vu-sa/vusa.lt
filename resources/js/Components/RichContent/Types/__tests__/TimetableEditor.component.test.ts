import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import TimetableEditor from '../TimetableEditor.vue';

import { useApi } from '@/Composables/useApi';
import { commonStubs } from '@/tests/stubs';

vi.mock('@/Composables/useApi', () => ({ useApi: vi.fn() }));
vi.mock('@/Composables/useIsMobile', () => ({ useIsMobile: () => ({ value: true }) }));

beforeEach(() => {
  vi.mocked(useApi).mockReset();
});

describe('TimetableEditor on a phone', () => {
  it('imports a meeting agenda as editable timetable rows', async () => {
    const recentData = ref<{ id: string; title: string; institution_name: string }[] | null>(null);
    const agendaData = ref<{ startTime: string; endTime: string; title: string }[] | null>(null);
    const recentExecute = vi.fn(async () => {
      recentData.value = [{ id: '7', title: 'Meeting', institution_name: 'Unit' }];
    });
    const agendaExecute = vi.fn(async () => {
      agendaData.value = [{ startTime: '10:00', endTime: '11:00', title: 'Agenda item' }];
    });

    vi.mocked(useApi)
      .mockReturnValueOnce({ data: recentData, error: ref(null), execute: recentExecute } as unknown as ReturnType<typeof useApi>)
      .mockReturnValueOnce({ data: agendaData, error: ref(null), isFetching: ref(false), execute: agendaExecute } as unknown as ReturnType<typeof useApi>);

    const wrapper = mount(TimetableEditor, {
      props: { modelValue: [], options: { title: '' } },
      global: { stubs: commonStubs },
    });

    await wrapper.findAll('button').find(button => button.text().includes('rich-content.import_from_meeting'))!.trigger('click');
    expect(recentExecute).toHaveBeenCalledOnce();
    expect(wrapper.text()).toContain('Meeting');
    expect(wrapper.text()).toContain('rich-content.back_to_timetable');

    await wrapper.findAll('button').find(button => button.text().includes('Meeting'))!.trigger('click');
    expect(agendaExecute).toHaveBeenCalledOnce();
    expect(wrapper.emitted('update:modelValue')?.at(-1)?.[0]).toEqual([
      { startTime: '10:00', endTime: '11:00', title: 'Agenda item' },
    ]);
  });
});
