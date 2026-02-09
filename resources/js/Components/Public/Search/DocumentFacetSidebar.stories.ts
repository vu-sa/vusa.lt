import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { fn, userEvent, within } from 'storybook/test';

import DocumentFacetSidebar from './DocumentFacetSidebar.vue';

import type { DocumentFacet, DocumentSearchFilters } from '@/Types/DocumentSearchTypes';

// Mock facet data that matches the expected DocumentFacet[] structure
const mockFacets: DocumentFacet[] = [
  {
    field: 'tenant',
    label: 'Dariniai',
    values: [
      { value: 'VU SA', label: 'VU SA', count: 156, highlighted: 'VU SA' },
      { value: 'VU IF', label: 'VU IF', count: 89, highlighted: 'VU IF' },
      { value: 'VU MIF', label: 'VU MIF', count: 67, highlighted: 'VU MIF' },
      { value: 'VU GMC', label: 'VU GMC', count: 34, highlighted: 'VU GMC' },
      { value: 'VU TF', label: 'VU TF', count: 28, highlighted: 'VU TF' },
    ],
  },
  {
    field: 'content_type',
    label: 'Dokumentų tipas',
    values: [
      { value: 'protokolas', label: 'Protokolas', count: 198, highlighted: 'protokolas' },
      { value: 'ataskaita', label: 'Ataskaita', count: 87, highlighted: 'ataskaita' },
      { value: 'sprendimas', label: 'Sprendimas', count: 56, highlighted: 'sprendimas' },
      { value: 'pranešimas', label: 'Pranešimas', count: 43, highlighted: 'pranešimas' },
    ],
  },
  {
    field: 'language',
    label: 'Kalba',
    values: [
      { value: 'Lietuvių', label: 'Lietuvių', count: 298, highlighted: 'Lietuvių' },
      { value: 'English', label: 'English', count: 86, highlighted: 'English' },
    ],
  },
];

const mockFilters: DocumentSearchFilters = {
  query: '',
  tenants: [],
  contentTypes: [],
  languages: [],
  dateRange: {
    preset: undefined,
    from: undefined,
    to: undefined,
  },
};

const mockActiveFilters: DocumentSearchFilters = {
  query: 'VU SR protokolai',
  tenants: ['VU SA', 'VU IF'],
  contentTypes: ['protokolas'],
  languages: ['Lietuvių'],
  dateRange: {
    preset: '3months',
    from: new Date('2024-11-01'),
    to: new Date('2024-11-30'),
  },
};

const meta: Meta<typeof DocumentFacetSidebar> = {
  title: 'Components/Search/DocumentFacetSidebar',
  component: DocumentFacetSidebar,
  tags: ['autodocs'],
  argTypes: {
    facets: { control: 'object' },
    filters: { control: 'object' },
    isLoading: { control: 'boolean' },
    activeFilterCount: { control: 'number' },
    onUpdateTenant: fn(),
    onUpdateContentType: fn(),
    onUpdateLanguage: fn(),
    onUpdateDateRange: fn(),
    onClearFilters: fn(),
    onApplyPreset: fn(),
  },
  args: {
    facets: mockFacets,
    filters: mockFilters,
    isLoading: false,
    activeFilterCount: 0,
    onUpdateTenant: fn(),
    onUpdateContentType: fn(),
    onUpdateLanguage: fn(),
    onUpdateDateRange: fn(),
    onClearFilters: fn(),
    onApplyPreset: fn(),
  },
  decorators: [
    story => ({
      components: { story },
      template: `
        <div class="p-6 bg-gray-50 min-h-screen">
          <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-4 gap-6">
            <div class="lg:col-span-1">
              <h2 class="text-2xl font-bold mb-6 text-gray-900">Document Filters</h2>
              <story />
            </div>
            <div class="lg:col-span-3 bg-white rounded-lg p-6">
              <h3 class="text-lg font-semibold mb-4">Search Results Area</h3>
              <p class="text-gray-600">Search results would appear here...</p>
            </div>
          </div>
        </div>
      `,
    }),
  ],
  parameters: {
    layout: 'fullscreen',
    backgrounds: {
      default: 'light',
    },
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

// Default state with no active filters
export const Default: Story = {
  render: args => ({
    components: { DocumentFacetSidebar },
    setup() {
      return { args };
    },
    template: `
      <DocumentFacetSidebar
        v-bind="args"
        @update:tenant="args.onUpdateTenant"
        @update:contentType="args.onUpdateContentType"
        @update:language="args.onUpdateLanguage"
        @update:dateRange="args.onUpdateDateRange"
        @clearFilters="args.onClearFilters"
        @applyPreset="args.onApplyPreset"
      />
    `,
  }),
};

// With active filters
export const WithActiveFilters: Story = {
  args: {
    filters: mockActiveFilters,
    activeFilterCount: 4,
  },
  render: args => ({
    components: { DocumentFacetSidebar },
    setup() {
      return { args };
    },
    template: `
      <DocumentFacetSidebar
        v-bind="args"
        @update:tenant="args.onUpdateTenant"
        @update:contentType="args.onUpdateContentType"
        @update:language="args.onUpdateLanguage"
        @update:dateRange="args.onUpdateDateRange"
        @clearFilters="args.onClearFilters"
        @applyPreset="args.onApplyPreset"
      />
    `,
  }),
};

// Loading state
export const Loading: Story = {
  args: {
    isLoading: true,
    facets: [],
  },
  render: args => ({
    components: { DocumentFacetSidebar },
    setup() {
      return { args };
    },
    template: `
      <DocumentFacetSidebar
        v-bind="args"
        @update:tenant="args.onUpdateTenant"
        @update:contentType="args.onUpdateContentType"
        @update:language="args.onUpdateLanguage"
        @update:dateRange="args.onUpdateDateRange"
        @clearFilters="args.onClearFilters"
        @applyPreset="args.onApplyPreset"
      />
    `,
  }),
};

// Mobile view
export const MobileView: Story = {
  args: {
    showMobileFilters: true,
    filters: mockActiveFilters,
  },
  decorators: [
    story => ({
      components: { story },
      template: `
        <div class="p-4 bg-gray-50 min-h-screen">
          <div class="max-w-sm mx-auto">
            <h2 class="text-xl font-bold mb-4 text-gray-900">Mobile Filters</h2>
            <story />
          </div>
        </div>
      `,
    }),
  ],
  parameters: {
    layout: 'fullscreen',
    viewport: {
      defaultViewport: 'mobile1',
    },
  },
  render: args => ({
    components: { DocumentFacetSidebar },
    setup() {
      return { args };
    },
    template: `
      <DocumentFacetSidebar
        v-bind="args"
        @update:tenant="args.onUpdateTenant"
        @update:contentType="args.onUpdateContentType"
        @update:language="args.onUpdateLanguage"
        @update:dateRange="args.onUpdateDateRange"
        @clearFilters="args.onClearFilters"
        @applyPreset="args.onApplyPreset"
      />
    `,
  }),
};

// No facets available
export const NoFacets: Story = {
  args: {
    facets: [],
  },
  render: args => ({
    components: { DocumentFacetSidebar },
    setup() {
      return { args };
    },
    template: `
      <DocumentFacetSidebar
        v-bind="args"
        @update:tenant="args.onUpdateTenant"
        @update:contentType="args.onUpdateContentType"
        @update:language="args.onUpdateLanguage"
        @update:dateRange="args.onUpdateDateRange"
        @clearFilters="args.onClearFilters"
        @applyPreset="args.onApplyPreset"
      />
    `,
  }),
};

// Limited facets (some empty categories)
export const LimitedFacets: Story = {
  args: {
    facets: [
      {
        field: 'tenant',
        label: 'Dariniai',
        values: [
          { value: 'VU SA', label: 'VU SA', count: 156, highlighted: 'VU SA' },
          { value: 'VU IF', label: 'VU IF', count: 89, highlighted: 'VU IF' },
        ],
      },
      {
        field: 'content_type',
        label: 'Dokumentų tipas',
        values: [
          { value: 'protokolas', label: 'Protokolas', count: 245, highlighted: 'protokolas' },
        ],
      },
      {
        field: 'language',
        label: 'Kalba',
        values: [],
      },
    ],
  },
  render: args => ({
    components: { DocumentFacetSidebar },
    setup() {
      return { args };
    },
    template: `
      <DocumentFacetSidebar
        v-bind="args"
        @update:tenant="args.onUpdateTenant"
        @update:contentType="args.onUpdateContentType"
        @update:language="args.onUpdateLanguage"
        @update:dateRange="args.onUpdateDateRange"
        @clearFilters="args.onClearFilters"
        @applyPreset="args.onApplyPreset"
      />
    `,
  }),
};

// Interactive story with user actions
export const Interactive: Story = {
  render: args => ({
    components: { DocumentFacetSidebar },
    setup() {
      return { args };
    },
    template: `
      <DocumentFacetSidebar
        v-bind="args"
        @update:tenant="args.onUpdateTenant"
        @update:contentType="args.onUpdateContentType"
        @update:language="args.onUpdateLanguage"
        @update:dateRange="args.onUpdateDateRange"
        @clearFilters="args.onClearFilters"
        @applyPreset="args.onApplyPreset"
      />
    `,
  }),
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);

    // Wait for component to render
    await new Promise(resolve => setTimeout(resolve, 500));

    try {
      // Click on a tenant filter
      const vuSaCheckbox = canvas.getByLabelText('VU SA (156)');
      await userEvent.click(vuSaCheckbox);

      // Click on a content type filter
      const protocolCheckbox = canvas.getByLabelText('protokolas (198)');
      await userEvent.click(protocolCheckbox);

      // Click on a language filter
      const lithuanianCheckbox = canvas.getByLabelText('Lietuvių (298)');
      await userEvent.click(lithuanianCheckbox);

      // Wait for actions to be logged
      await new Promise(resolve => setTimeout(resolve, 500));
    }
    catch (error) {
      console.warn('Some interactive elements might not be available in this story:', error);
    }
  },
};

// Many facets (scrollable)
export const ManyFacets: Story = {
  args: {
    facets: [
      {
        field: 'tenant',
        label: 'Dariniai',
        values: [
          { value: 'VU SA', label: 'VU SA', count: 156, highlighted: 'VU SA' },
          { value: 'VU IF', label: 'VU IF', count: 89, highlighted: 'VU IF' },
          { value: 'VU MIF', label: 'VU MIF', count: 67, highlighted: 'VU MIF' },
          { value: 'VU GMC', label: 'VU GMC', count: 34, highlighted: 'VU GMC' },
          { value: 'VU TF', label: 'VU TF', count: 28, highlighted: 'VU TF' },
          { value: 'VU FF', label: 'VU FF', count: 25, highlighted: 'VU FF' },
          { value: 'VU ChGF', label: 'VU ChGF', count: 22, highlighted: 'VU ChGF' },
          { value: 'VU AGAI', label: 'VU AGAI', count: 19, highlighted: 'VU AGAI' },
          { value: 'VU KnF', label: 'VU KnF', count: 16, highlighted: 'VU KnF' },
          { value: 'VU ŠA', label: 'VU ŠA', count: 14, highlighted: 'VU ŠA' },
          { value: 'VU EF', label: 'VU EF', count: 12, highlighted: 'VU EF' },
          { value: 'VU VM', label: 'VU VM', count: 8, highlighted: 'VU VM' },
        ],
      },
      {
        field: 'content_type',
        label: 'Dokumentų tipas',
        values: [
          { value: 'protokolas', label: 'Protokolas', count: 198, highlighted: 'protokolas' },
          { value: 'ataskaita', label: 'Ataskaita', count: 87, highlighted: 'ataskaita' },
          { value: 'sprendimas', label: 'Sprendimas', count: 56, highlighted: 'sprendimas' },
          { value: 'pranešimas', label: 'Pranešimas', count: 43, highlighted: 'pranešimas' },
          { value: 'nuostatai', label: 'Nuostatai', count: 32, highlighted: 'nuostatai' },
          { value: 'taisyklės', label: 'Taisyklės', count: 28, highlighted: 'taisyklės' },
          { value: 'metodika', label: 'Metodika', count: 21, highlighted: 'metodika' },
          { value: 'reglamentas', label: 'Reglamentas', count: 15, highlighted: 'reglamentas' },
        ],
      },
      mockFacets[2], // Use the language facet from mockFacets
    ],
  },
  render: args => ({
    components: { DocumentFacetSidebar },
    setup() {
      return { args };
    },
    template: `
      <DocumentFacetSidebar
        v-bind="args"
        @update:tenant="args.onUpdateTenant"
        @update:contentType="args.onUpdateContentType"
        @update:language="args.onUpdateLanguage"
        @update:dateRange="args.onUpdateDateRange"
        @clearFilters="args.onClearFilters"
        @applyPreset="args.onApplyPreset"
      />
    `,
  }),
};
