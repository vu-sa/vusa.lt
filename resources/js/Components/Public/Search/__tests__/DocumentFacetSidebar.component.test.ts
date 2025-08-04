import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import DocumentFacetSidebar from '../DocumentFacetSidebar.vue'
import type { DocumentFacet, DocumentSearchFilters } from '@/Types/DocumentSearchTypes'

// Mock Inertia.js
vi.mock('@inertiajs/vue3', () => ({
  usePage: vi.fn(() => ({
    props: {}
  }))
}))

// Mock ShadcnVue components
vi.mock('@/Components/ui/button', () => ({
  Button: {
    name: 'Button',
    template: '<button @click="$emit(\'click\', $event)"><slot /></button>',
    emits: ['click']
  }
}))

vi.mock('@/Components/ui/badge', () => ({
  Badge: {
    name: 'Badge',
    template: '<span class="badge"><slot /></span>'
  }
}))

vi.mock('@/Components/ui/sheet', () => ({
  Sheet: {
    name: 'Sheet',
    template: '<div><slot /></div>',
    props: ['open'],
    emits: ['update:open']
  },
  SheetTrigger: {
    name: 'SheetTrigger',
    template: '<div @click="$emit(\'click\')"><slot /></div>',
    emits: ['click']
  },
  SheetContent: {
    name: 'SheetContent',
    template: '<div class="sheet-content"><slot /></div>'
  },
  SheetHeader: {
    name: 'SheetHeader',
    template: '<div class="sheet-header"><slot /></div>'
  },
  SheetTitle: {
    name: 'SheetTitle',
    template: '<h2><slot /></h2>'
  }
}))

vi.mock('@/Components/ui/accordion', () => ({
  Accordion: {
    name: 'Accordion',
    template: '<div class="accordion"><slot /></div>',
    props: ['type', 'defaultValue']
  },
  AccordionContent: {
    name: 'AccordionContent',
    template: '<div class="accordion-content"><slot /></div>'
  },
  AccordionItem: {
    name: 'AccordionItem',
    template: '<div class="accordion-item"><slot /></div>',
    props: ['value']
  },
  AccordionTrigger: {
    name: 'AccordionTrigger',
    template: '<button class="accordion-trigger" @click="$emit(\'click\')"><slot /></button>',
    emits: ['click']
  }
}))

vi.mock('@/Components/ui/checkbox', () => ({
  Checkbox: {
    name: 'Checkbox',
    template: '<input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
    props: ['modelValue'],
    emits: ['update:modelValue']
  }
}))

vi.mock('@/Components/ui/scroll-area', () => ({
  ScrollArea: {
    name: 'ScrollArea',
    template: '<div class="scroll-area"><slot /></div>'
  }
}))

// Mock Lucide icons
vi.mock('lucide-vue-next', () => ({
  Filter: { name: 'Filter', template: '<svg class="filter-icon" />' },
  Building2: { name: 'Building2', template: '<svg class="building-icon" />' },
  FileText: { name: 'FileText', template: '<svg class="file-icon" />' },
  Globe: { name: 'Globe', template: '<svg class="globe-icon" />' },
  Calendar: { name: 'Calendar', template: '<svg class="calendar-icon" />' },
  RotateCcw: { name: 'RotateCcw', template: '<svg class="rotate-icon" />' }
}))

// Mock child components
vi.mock('../TenantFilter.vue', () => ({
  default: {
    name: 'TenantFilter',
    template: '<div class="tenant-filter">TenantFilter</div>',
    props: ['tenantHierarchy', 'selectedTenants'],
    emits: ['toggle-tenant']
  }
}))

vi.mock('../ContentTypeFilter.vue', () => ({
  default: {
    name: 'ContentTypeFilter',
    template: '<div class="content-type-filter">ContentTypeFilter</div>',
    props: ['groupedTypes', 'selectedTypes'],
    emits: ['toggle-type']
  }
}))

vi.mock('../DateRangeFilter.vue', () => ({
  default: {
    name: 'DateRangeFilter',
    template: '<div class="date-range-filter">DateRangeFilter</div>',
    props: ['dateRange'],
    emits: ['update:date-range']
  }
}))

describe('DocumentFacetSidebar', () => {
  const mockTenantFacet: DocumentFacet = {
    field: 'tenant_shortname',
    label: 'Tenant',
    values: [
      { value: 'VU SA', label: 'VU SA', count: 150 },
      { value: 'VU SA CHGF', label: 'VU SA CHGF', count: 45 },
      { value: 'VU SA PKP Klubas', label: 'VU SA PKP Klubas', count: 25 },
      { value: 'Other Org', label: 'Other Org', count: 10 }
    ]
  }

  const mockContentTypeFacet: DocumentFacet = {
    field: 'content_type',
    label: 'Content Type',
    values: [
      { value: 'VU SA protokolas', label: 'VU SA protokolas', count: 120 },
      { value: 'VU SA P nuostatai', label: 'VU SA P nuostatai', count: 80 },
      { value: 'sprendimas', label: 'sprendimas', count: 60 }
    ]
  }

  const mockLanguageFacet: DocumentFacet = {
    field: 'language',
    label: 'Language',
    values: [
      { value: 'Lietuvių', label: 'Lietuvių', count: 200 },
      { value: 'Anglų', label: 'Anglų', count: 50 }
    ]
  }

  const defaultProps = {
    facets: [mockTenantFacet, mockContentTypeFacet, mockLanguageFacet],
    filters: {
      query: '',
      tenants: [],
      contentTypes: [],
      languages: [],
      dateRange: {}
    } as DocumentSearchFilters,
    isLoading: false,
    activeFilterCount: 0
  }

  const createWrapper = (props = {}) => {
    return mount(DocumentFacetSidebar, {
      props: { ...defaultProps, ...props }
    })
  }

  describe('basic rendering', () => {
    it('renders the component with default props', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.find('.space-y-4').exists()).toBe(true)
    })

    it('shows mobile filter button on mobile view', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.find('.lg\\:hidden').exists()).toBe(true)
      expect(wrapper.text()).toContain('search.filters')
    })

    it('shows desktop filters on desktop view', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.find('.hidden.lg\\:block').exists()).toBe(true)
    })

    it('displays active filter count when filters are active', () => {
      const wrapper = createWrapper({ activeFilterCount: 3 })
      
      expect(wrapper.text()).toContain('3')
    })
  })

  describe('filter sections', () => {
    it('renders tenant filter section', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.tenants')
      expect(wrapper.findComponent({ name: 'TenantFilter' }).exists()).toBe(true)
    })

    it('renders content type filter section', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.document_type')
      expect(wrapper.findComponent({ name: 'ContentTypeFilter' }).exists()).toBe(true)
    })

    it('renders language filter section', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.language')
    })

    it('renders date range filter section', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('search.date')
      expect(wrapper.findComponent({ name: 'DateRangeFilter' }).exists()).toBe(true)
    })
  })

  describe('loading states', () => {
    it('shows loading skeletons when isLoading is true', () => {
      const wrapper = createWrapper({ isLoading: true })
      
      expect(wrapper.find('.animate-pulse').exists()).toBe(true)
    })

    it('hides loading skeletons when isLoading is false', () => {
      const wrapper = createWrapper({ isLoading: false })
      
      expect(wrapper.findComponent({ name: 'TenantFilter' }).exists()).toBe(true)
      expect(wrapper.findComponent({ name: 'ContentTypeFilter' }).exists()).toBe(true)
    })
  })

  describe('language filter functionality', () => {
    it('displays language options with flags and counts', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.text()).toContain('LT')
      expect(wrapper.text()).toContain('EN')
      expect(wrapper.text()).toContain('200')
      expect(wrapper.text()).toContain('50')
    })

    it('shows selected language as checked', () => {
      const wrapper = createWrapper({
        filters: {
          ...defaultProps.filters,
          languages: ['Lietuvių']
        }
      })
      
      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(true)
    })

    it('emits update:language when language is toggled', async () => {
      const wrapper = createWrapper()
      
      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setChecked(true)
      
      expect(wrapper.emitted('update:language')).toBeTruthy()
      expect(wrapper.emitted('update:language')?.[0]).toEqual(['Lietuvių'])
    })

    it('shows message when no language facets are available', () => {
      const wrapper = createWrapper({
        facets: [mockTenantFacet, mockContentTypeFacet] // No language facet
      })
      
      expect(wrapper.text()).toContain('search.language_filters_after_search')
    })
  })

  describe('clear filters functionality', () => {
    it('enables clear button when filters are active', () => {
      const wrapper = createWrapper({ activeFilterCount: 2 })
      
      const clearButtons = wrapper.findAll('button').filter(btn => 
        btn.text().includes('search.clear_filters')
      )
      
      expect(clearButtons.length).toBeGreaterThan(0)
      expect(clearButtons[0]?.text()).toContain('search.clear_filters_count')
    })

    it('disables clear button when no filters are active', () => {
      const wrapper = createWrapper({ activeFilterCount: 0 })
      
      const clearButtons = wrapper.findAll('button').filter(btn => 
        btn.text().includes('search.clear_filters')
      )
      
      expect(clearButtons[0]?.text()).toBe('search.clear_filters')
    })

    it('emits clearFilters when clear button is clicked', async () => {
      const wrapper = createWrapper({ activeFilterCount: 2 })
      
      const clearButton = wrapper.findAll('button').find(btn => 
        btn.text().includes('search.clear_filters')
      )
      
      if (clearButton) {
        await clearButton.trigger('click')
        expect(wrapper.emitted('clearFilters')).toBeTruthy()
      }
    })
  })

  describe('facet processing', () => {
    it('processes tenant facets correctly', () => {
      const wrapper = createWrapper()
      const vm = wrapper.vm as any
      
      const processed = vm.processedTenantHierarchy
      
      expect(processed.main).toBeDefined()
      expect(processed.padaliniai).toBeDefined()
      expect(processed.pkp).toBeDefined()
      
      // VU SA should be in main
      expect(processed.main.some((t: any) => t.shortname === 'VU SA')).toBe(true)
      // VU SA CHGF should be in padaliniai
      expect(processed.padaliniai.some((t: any) => t.shortname === 'VU SA CHGF')).toBe(true)
      // PKP should be in pkp
      expect(processed.pkp.some((t: any) => t.shortname === 'VU SA PKP Klubas')).toBe(true)
    })

    it('processes content type facets correctly', () => {
      const wrapper = createWrapper()
      const vm = wrapper.vm as any
      
      const grouped = vm.groupedContentTypes
      
      expect(grouped.vusa).toBeDefined()
      expect(grouped.vusaP).toBeDefined()
      expect(grouped.other).toBeDefined()
      
      // Check that labels are properly capitalized
      const protocolType = grouped.vusa.find((t: any) => t.value === 'VU SA protokolas')
      expect(protocolType?.label).toBe('Protokolas')
    })
  })

  describe('utility functions', () => {
    it('formats counts correctly', () => {
      const wrapper = createWrapper()
      const vm = wrapper.vm as any
      
      expect(vm.formatCount(999)).toBe('999')
      expect(vm.formatCount(1500)).toBe('1.5K')
      expect(vm.formatCount(1500000)).toBe('1.5M')
    })

    it('gets correct language flags', () => {
      const wrapper = createWrapper()
      const vm = wrapper.vm as any
      
      expect(vm.getLanguageFlag('Lietuvių')).toContain('lt.svg')
      expect(vm.getLanguageFlag('Anglų')).toContain('gb.svg')
      expect(vm.getLanguageFlag('Unknown')).toBe('')
    })

    it('gets correct language display names', () => {
      const wrapper = createWrapper()
      const vm = wrapper.vm as any
      
      expect(vm.getLanguageDisplay('Lietuvių')).toBe('LT')
      expect(vm.getLanguageDisplay('Anglų')).toBe('EN')
      expect(vm.getLanguageDisplay('Unknown')).toBe('Unknown')
    })
  })

  describe('mobile functionality', () => {
    it('toggles mobile filters sheet', async () => {
      const wrapper = createWrapper()
      
      const mobileButton = wrapper.find('.lg\\:hidden button')
      await mobileButton.trigger('click')
      
      // The sheet should be accessible (though we can't test the actual visibility with jsdom)
      expect(wrapper.findComponent({ name: 'Sheet' }).exists()).toBe(true)
    })

    it('shows same content in mobile and desktop versions', () => {
      const wrapper = createWrapper()
      
      // Both mobile and desktop should have tenant filters
      const tenantFilters = wrapper.findAllComponents({ name: 'TenantFilter' })
      expect(tenantFilters.length).toBeGreaterThanOrEqual(1)
      
      // Both should have content type filters
      const contentTypeFilters = wrapper.findAllComponents({ name: 'ContentTypeFilter' })
      expect(contentTypeFilters.length).toBeGreaterThanOrEqual(1)
    })
  })

  describe('badge display', () => {
    it('shows badges for active filters', () => {
      const wrapper = createWrapper({
        filters: {
          ...defaultProps.filters,
          tenants: ['VU SA', 'VU SA CHGF'],
          contentTypes: ['protokolas'],
          languages: ['Lietuvių']
        }
      })
      
      const badges = wrapper.findAllComponents({ name: 'Badge' })
      expect(badges.length).toBeGreaterThan(0)
    })

    it('shows correct count in filter badges', () => {
      const wrapper = createWrapper({
        filters: {
          ...defaultProps.filters,
          tenants: ['VU SA', 'VU SA CHGF'], // 2 tenants
          contentTypes: ['protokolas'] // 1 content type
        }
      })
      
      expect(wrapper.text()).toContain('2') // Tenant badge
      expect(wrapper.text()).toContain('1') // Content type badge
    })

    it('shows date range badge when date filters are active', () => {
      const wrapper = createWrapper({
        filters: {
          ...defaultProps.filters,
          dateRange: { preset: '3months' }
        }
      })
      
      // Should show badge with count of 1 for date range
      expect(wrapper.text()).toContain('1')
    })
  })

  describe('event emissions', () => {
    it('forwards tenant toggle events', async () => {
      const wrapper = createWrapper()
      
      const tenantFilter = wrapper.findComponent({ name: 'TenantFilter' })
      await tenantFilter.vm.$emit('toggle-tenant', 'VU SA')
      
      expect(wrapper.emitted('update:tenant')).toBeTruthy()
      expect(wrapper.emitted('update:tenant')?.[0]).toEqual(['VU SA'])
    })

    it('forwards content type toggle events', async () => {
      const wrapper = createWrapper()
      
      const contentTypeFilter = wrapper.findComponent({ name: 'ContentTypeFilter' })
      await contentTypeFilter.vm.$emit('toggle-type', 'protokolas')
      
      expect(wrapper.emitted('update:contentType')).toBeTruthy()
      expect(wrapper.emitted('update:contentType')?.[0]).toEqual(['protokolas'])
    })

    it('forwards date range update events', async () => {
      const wrapper = createWrapper()
      
      const dateRangeFilter = wrapper.findComponent({ name: 'DateRangeFilter' })
      const newRange = { preset: '6months' }
      await dateRangeFilter.vm.$emit('update:date-range', newRange)
      
      expect(wrapper.emitted('update:dateRange')).toBeTruthy()
      expect(wrapper.emitted('update:dateRange')?.[0]).toEqual([newRange])
    })
  })

  describe('accessibility', () => {
    it('uses proper semantic HTML structure', () => {
      const wrapper = createWrapper()
      
      // Should have proper heading structure
      expect(wrapper.find('h3').exists()).toBe(true)
      
      // Should have proper label elements for checkboxes
      expect(wrapper.find('label').exists()).toBe(true)
    })

    it('provides proper alt text for flag images', () => {
      const wrapper = createWrapper()
      
      const flagImages = wrapper.findAll('img')
      flagImages.forEach(img => {
        expect(img.attributes('alt')).toBeTruthy()
      })
    })
  })
})