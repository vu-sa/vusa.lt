import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import FlowStepNode from '../architecture/FlowStepNode.vue'

const data = {
  label: 'CalendarController::store()',
  detail: 'Saves the event.',
  file: 'app/Http/Controllers/Admin/CalendarController.php',
  placement: 'below' as const,
}

function mountNode(selected: boolean) {
  return mount(FlowStepNode, { props: { data, selected }, global: { stubs: { Handle: true } } })
}

describe('FlowStepNode', () => {
  it('opens its tooltip with the explanation and file link once selected (tap or Enter)', () => {
    const tip = mountNode(true).get('[role="tooltip"]')

    expect(tip.classes()).toContain('af-tip--open')
    expect(tip.text()).toContain('Saves the event.')
    expect(tip.get('a').attributes('href')).toBe('https://github.com/vu-sa/vusa.lt/blob/main/app/Http/Controllers/Admin/CalendarController.php')
  })

  it('keeps the tooltip for hover only while not selected', () => {
    expect(mountNode(false).get('[role="tooltip"]').classes()).not.toContain('af-tip--open')
  })
})
