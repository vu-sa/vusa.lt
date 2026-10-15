import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { useData } from 'vitepress'
import DocReviewDate from '../DocReviewDate.vue'
import DocReview from '../DocReview.vue'
import TestEvidence from '../TestEvidence.vue'

vi.mock('vitepress', () => ({ useData: vi.fn() }))

function setFrontmatter(value: Record<string, unknown>) {
  vi.mocked(useData).mockReturnValue({ frontmatter: ref(value) } as ReturnType<typeof useData>)
}

describe('guide review metadata', () => {
  it.each(['2026-10-02', '2026-10-02T00:00:00.000Z'])('shows a readable review date without requiring test evidence (%s)', value => {
    setFrontmatter({ doc_status: 'reviewed', last_reviewed: value })
    const date = mount(DocReviewDate)

    expect(date.text()).toBe('Turinys peržiūrėtas 2026-10-02')
    expect(date.get('time').attributes('datetime')).toBe('2026-10-02')
    expect(mount(TestEvidence).find('details').exists()).toBe(false)
    expect(mount(DocReview).find('[aria-label="Puslapio parengtis"]').exists()).toBe(false)
  })

  it('omits a review date when none is declared, even with test evidence', () => {
    setFrontmatter({ tests: ['tests/Feature/System/SystemStatusTest.php'] })

    expect(mount(DocReviewDate).find('time').exists()).toBe(false)
    expect(mount(TestEvidence).get('summary').text()).toBe('Testų nuorodos (1)')
  })

  it.each([['draft', 'Rašoma'], ['partial', 'Dalinis']])('keeps the %s readiness label', (status, label) => {
    setFrontmatter({ doc_status: status })

    expect(mount(DocReview).text()).toBe(label)
  })
})
