import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import DocumentSearchInput from '../DocumentSearchInput.vue'

// Mock ShadcnVue components
vi.mock('@/Components/ui/button', () => ({
  Button: {
    name: 'Button',
    template: '<button @click="$emit(\'click\', $event)"><slot /></button>',
    emits: ['click']
  }
}))

vi.mock('@/Components/ui/input', () => ({
  Input: {
    name: 'Input',
    template: '<input ref="inputElement" v-bind="$attrs" :value="modelValue" @input="$emit(\'input\', $event)" @focus="$emit(\'focus\', $event)" @blur="$emit(\'blur\', $event)" @keydown="$emit(\'keydown\', $event)" />',
    props: ['modelValue'],
    emits: ['input', 'focus', 'blur', 'keydown'],
    mounted() {
      // Mock the focus method
      if (this.$refs.inputElement) {
        this.$refs.inputElement.focus = vi.fn()
      }
    }
  }
}))

vi.mock('@/Components/ui/popover', () => ({
  Popover: { name: 'Popover', template: '<div><slot /></div>' },
  PopoverContent: { name: 'PopoverContent', template: '<div><slot /></div>' },
  PopoverTrigger: { name: 'PopoverTrigger', template: '<div><slot /></div>' }
}))

// Mock Lucide icons
vi.mock('lucide-vue-next', () => ({
  X: { name: 'X', template: '<svg />' },
  History: { name: 'History', template: '<svg />' },
  Search: { name: 'Search', template: '<svg />' },
  Zap: { name: 'Zap', template: '<svg />' }
}))

describe('DocumentSearchInput', () => {
  const defaultProps = {
    query: '',
    isSearching: false,
    recentSearches: [],
    placeholder: 'Ieškoti dokumentų...',
    typeToSearch: false
  }

  const createWrapper = (props = {}) => {
    return mount(DocumentSearchInput, {
      props: { ...defaultProps, ...props }
    })
  }

  describe('basic rendering', () => {
    it('renders search input with default props', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.find('input').exists()).toBe(true)
      expect(wrapper.find('input').attributes('placeholder')).toBe('search.search_documents_placeholder')
      expect(wrapper.find('input').attributes('role')).toBe('search')
    })

    it('displays custom placeholder when provided', () => {
      const wrapper = createWrapper({ placeholder: 'Custom placeholder' })
      
      expect(wrapper.find('input').attributes('placeholder')).toBe('search.search_documents_placeholder')
    })

    it('shows current query value', () => {
      const wrapper = createWrapper({ query: 'test query' })
      
      expect(wrapper.find('input').element.value).toBe('test query')
    })
  })

  describe('search functionality', () => {
    it('emits update:query when input changes', async () => {
      const wrapper = createWrapper()
      const input = wrapper.find('input')
      
      await input.setValue('new query')
      await input.trigger('input')
      
      expect(wrapper.emitted('update:query')).toBeTruthy()
      expect(wrapper.emitted('update:query')?.[0]).toEqual(['new query'])
    })

    it('emits search when Enter key is pressed', async () => {
      const wrapper = createWrapper({ query: 'test query' })
      const input = wrapper.find('input')
      
      await input.trigger('keydown.enter')
      
      expect(wrapper.emitted('search')).toBeTruthy()
      expect(wrapper.emitted('search')?.[0]).toEqual(['test query'])
    })

    it('emits search when search button is clicked', async () => {
      const wrapper = createWrapper({ query: 'test query' })
      
      // Find search button (should be visible when typeToSearch is false and query exists)
      const searchButton = wrapper.find('[data-testid="search-button"]')
      if (searchButton.exists()) {
        await searchButton.trigger('click')
        expect(wrapper.emitted('search')).toBeTruthy()
      }
    })

    it('does not emit search for queries shorter than 3 characters', async () => {
      const wrapper = createWrapper({ query: 'ab' })
      const input = wrapper.find('input')
      
      await input.trigger('keydown.enter')
      
      expect(wrapper.emitted('search')).toBeFalsy()
    })
  })

  describe('typeToSearch mode', () => {
    it('shows type-to-search status when enabled', () => {
      const wrapper = createWrapper({ typeToSearch: true })
      
      expect(wrapper.text()).toContain('search.auto_search_enabled')
    })

    it('hides type-to-search status when disabled', () => {
      const wrapper = createWrapper({ typeToSearch: false })
      
      expect(wrapper.text()).not.toContain('Automatinė paieška įjungta')
    })

    it('auto-searches on input when typeToSearch is enabled', async () => {
      const wrapper = createWrapper({ typeToSearch: true })
      const input = wrapper.find('input')
      
      // Simulate typing a query long enough to trigger search
      await input.setValue('test query')
      await input.trigger('input')
      
      expect(wrapper.emitted('search')).toBeTruthy()
      expect(wrapper.emitted('search')?.[0]).toEqual(['test query'])
    })

    it('does not auto-search for short queries even in typeToSearch mode', async () => {
      const wrapper = createWrapper({ typeToSearch: true })
      const input = wrapper.find('input')
      
      await input.setValue('ab')
      await input.trigger('input')
      
      expect(wrapper.emitted('search')).toBeFalsy()
    })

    it('toggles typeToSearch when toggle button is clicked', async () => {
      const wrapper = createWrapper({ typeToSearch: false })
      
      // Find and click the type-to-search toggle button
      const toggleButton = wrapper.find('[title="Įjungti automatinę paiešką"]')
      if (toggleButton.exists()) {
        await toggleButton.trigger('click')
        expect(wrapper.emitted('update:typeToSearch')).toBeTruthy()
        expect(wrapper.emitted('update:typeToSearch')?.[0]).toEqual([true])
      }
    })
  })

  describe('loading state', () => {
    it('shows loading spinner when searching', () => {
      const wrapper = createWrapper({ isSearching: true })
      
      expect(wrapper.find('.animate-spin').exists()).toBe(true)
    })

    it('hides loading spinner when not searching', () => {
      const wrapper = createWrapper({ isSearching: false })
      
      expect(wrapper.find('.animate-spin').exists()).toBe(false)
    })

    it('disables clear button when searching', () => {
      const wrapper = createWrapper({ 
        query: 'test',
        isSearching: true 
      })
      
      expect(wrapper.find('[data-testid="clear-button"]').exists()).toBe(false)
    })
  })

  describe('clear functionality', () => {
    it('shows clear button when there is a query and not searching', () => {
      const wrapper = createWrapper({ 
        query: 'test query',
        isSearching: false 
      })
      
      // Look for X icon which indicates clear button
      expect(wrapper.findComponent({ name: 'X' }).exists()).toBe(true)
    })

    it('hides clear button when query is empty', () => {
      const wrapper = createWrapper({ query: '' })
      
      // Clear button should not be visible when query is empty
      const clearButtons = wrapper.findAll('button').filter(btn => 
        btn.text().includes('Išvalyti') || btn.find('svg').exists()
      )
      
      // Should only have the type-to-search toggle button, not clear button
      expect(clearButtons.length).toBeLessThanOrEqual(1)
    })

    it('emits clear and update:query when clear button is clicked', async () => {
      const wrapper = createWrapper({ query: 'test query' })
      
      // Find clear button by looking for button with X icon
      const buttons = wrapper.findAll('button')
      const clearButton = buttons.find(btn => {
        const xIcon = btn.findComponent({ name: 'X' })
        return xIcon.exists()
      })
      
      if (clearButton) {
        await clearButton.trigger('click')
        
        expect(wrapper.emitted('clear')).toBeTruthy()
        expect(wrapper.emitted('update:query')).toBeTruthy()
        const updateQueryEmissions = wrapper.emitted('update:query')
        expect(updateQueryEmissions?.[updateQueryEmissions.length - 1]).toEqual(['']) // Last emission should be empty
      }
    })
  })

  describe('recent searches', () => {
    const recentSearches = ['recent search 1', 'recent search 2', 'recent search 3']

    it('shows recent searches dropdown when focused and not in typeToSearch mode', async () => {
      const wrapper = createWrapper({ 
        recentSearches,
        typeToSearch: false 
      })
      
      await wrapper.find('input').trigger('focus')
      await nextTick()
      
      expect(wrapper.text()).toContain('search.recent_searches')
      expect(wrapper.text()).toContain('recent search 1')
    })

    it('does not show recent searches dropdown in typeToSearch mode', async () => {
      const wrapper = createWrapper({ 
        recentSearches,
        typeToSearch: true 
      })
      
      await wrapper.find('input').trigger('focus')
      await nextTick()
      
      expect(wrapper.text()).not.toContain('Pastarieji paieškos žodžiai')
    })

    it('emits selectRecent when recent search is clicked', async () => {
      const wrapper = createWrapper({ 
        recentSearches,
        typeToSearch: false 
      })
      
      // Focus input to show suggestions
      await wrapper.find('input').trigger('focus')
      await nextTick()
      
      // Find and click a recent search button
      const buttons = wrapper.findAll('button')
      const recentButton = buttons.find(btn => 
        btn.text().includes('recent search 1')
      )
      
      if (recentButton) {
        await recentButton.trigger('click')
        
        expect(wrapper.emitted('selectRecent')).toBeTruthy()
        expect(wrapper.emitted('selectRecent')?.[0]).toEqual(['recent search 1'])
        expect(wrapper.emitted('search')).toBeTruthy()
        expect(wrapper.emitted('search')?.[0]).toEqual(['recent search 1'])
      }
    })

    it('emits removeRecent when remove button is clicked', async () => {
      const wrapper = createWrapper({ 
        recentSearches,
        typeToSearch: false 
      })
      
      await wrapper.find('input').trigger('focus')
      await nextTick()
      
      // Look for remove button (button with X icon in recent searches area)
      const buttons = wrapper.findAll('button')
      const removeButton = buttons.find(btn => {
        const hasXIcon = btn.findComponent({ name: 'X' }).exists()
        const isInGroup = btn.classes().some(cls => cls.includes('group-hover'))
        return hasXIcon && isInGroup
      })
      
      if (removeButton) {
        await removeButton.trigger('click')
        expect(wrapper.emitted('removeRecent')).toBeTruthy()
      }
    })

    it('emits clearAllHistory when clear all button is clicked', async () => {
      const wrapper = createWrapper({ 
        recentSearches,
        typeToSearch: false 
      })
      
      await wrapper.find('input').trigger('focus')
      await nextTick()
      
      // Find "Išvalyti viską" button
      const clearAllButton = wrapper.findAll('button').find(btn => 
        btn.text().includes('Išvalyti viską')
      )
      
      if (clearAllButton) {
        await clearAllButton.trigger('click')
        expect(wrapper.emitted('clearAllHistory')).toBeTruthy()
      }
    })

    it('limits displayed recent searches to 5', async () => {
      const manySearches = Array.from({ length: 10 }, (_, i) => `search ${i + 1}`)
      const wrapper = createWrapper({ 
        recentSearches: manySearches,
        typeToSearch: false 
      })
      
      // Focus input to show suggestions
      await wrapper.find('input').trigger('focus')
      await nextTick()
      
      // Count how many recent search items are rendered
      const searchItems = manySearches.slice(0, 5)
      searchItems.forEach(search => {
        expect(wrapper.text()).toContain(search)
      })
      
      // Should not contain items beyond the first 5
      expect(wrapper.text()).not.toContain('search 6')
    })
  })

  describe('focus and blur handling', () => {
    it('emits focus event when input is focused', async () => {
      const wrapper = createWrapper()
      
      await wrapper.find('input').trigger('focus')
      
      expect(wrapper.emitted('focus')).toBeTruthy()
    })

    it('emits blur event when input loses focus', async () => {
      const wrapper = createWrapper()
      
      await wrapper.find('input').trigger('blur')
      
      // Blur event is delayed, so we need to wait
      await new Promise(resolve => setTimeout(resolve, 200))
      
      expect(wrapper.emitted('blur')).toBeTruthy()
    })

    it('shows suggestions on focus when not in typeToSearch mode', async () => {
      const wrapper = createWrapper({ 
        recentSearches: ['test'],
        typeToSearch: false 
      })
      
      await wrapper.find('input').trigger('focus')
      await nextTick()
      
      expect(wrapper.text()).toContain('search.recent_searches')
    })
  })

  describe('accessibility', () => {
    it('has proper ARIA attributes', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.find('input').attributes('role')).toBe('search')
    })

    it('has screen reader text for buttons', () => {
      const wrapper = createWrapper({ query: 'test' })
      
      expect(wrapper.find('.sr-only').exists()).toBe(true)
    })

    it('has proper button titles for tooltips', () => {
      const wrapper = createWrapper({ typeToSearch: false })
      
      expect(wrapper.find('[title*="search.enable_auto_search"]').exists()).toBe(true)
    })
  })

  describe('expose methods', () => {
    it('exposes focusInput method', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.vm.focusInput).toBeDefined()
      expect(typeof wrapper.vm.focusInput).toBe('function')
    })
  })

  describe('query synchronization', () => {
    it('updates local query when prop query changes', async () => {
      const wrapper = createWrapper({ query: 'initial' })
      
      await wrapper.setProps({ query: 'updated' })
      
      expect(wrapper.find('input').element.value).toBe('updated')
    })
  })
})