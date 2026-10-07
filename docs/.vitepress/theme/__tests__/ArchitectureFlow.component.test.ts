import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { defineComponent, h, ref } from 'vue'
import { useData } from 'vitepress'
import ArchitectureFlow from '../ArchitectureFlow.vue'
import { flows } from '../architecture/flows'

vi.mock('vitepress', () => ({ useData: vi.fn() }))

// The real canvas needs a browser layout engine.
vi.mock('../architecture/FlowCanvas.vue', () => ({
  __esModule: true,
  default: defineComponent({ props: ['flow', 'locale'], setup: () => () => h('div') }),
}))

function mountFlow(lang: string) {
  vi.mocked(useData).mockReturnValue({ lang: ref(lang) } as unknown as ReturnType<typeof useData>)

  return mount(ArchitectureFlow, {
    props: { flow: 'calendarExample' },
    global: { stubs: { ClientOnly: { template: '<div><slot /></div>' } } },
  })
}

describe('ArchitectureFlow', () => {
  it('lists every step as text in the page language', () => {
    const wrapper = mountFlow('en')

    const steps = wrapper.findAll('.af__steps li')

    expect(steps).toHaveLength(flows.calendarExample.nodes.length)
    expect(steps[0].text()).toContain(flows.calendarExample.nodes[0].detail.en)
  })
})
