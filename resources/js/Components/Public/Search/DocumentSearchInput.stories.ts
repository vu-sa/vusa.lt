import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { fn, userEvent, within } from 'storybook/test';

import DocumentSearchInput from './DocumentSearchInput.vue';

// Mock the recent searches functionality
const mockRecentSearches = [
  'VU SR protokolai',
  'studijų programa',
  'padalinio susirinkimas',
  'rektorato posėdis',
];

const meta: Meta<typeof DocumentSearchInput> = {
  title: 'Components/Search/DocumentSearchInput',
  component: DocumentSearchInput,
  tags: ['autodocs'],
  argTypes: {
    query: { control: 'text' },
    loading: { control: 'boolean' },
    typeToSearch: { control: 'boolean' },
    disabled: { control: 'boolean' },
    placeholder: { control: 'text' },
    recentSearches: { control: 'object' },
    maxSuggestions: { control: 'number' },
    onUpdateQuery: fn(),
    onSearch: fn(),
    onSelectRecent: fn(),
    onRemoveRecent: fn(),
    onClearRecents: fn(),
    onFocus: fn(),
    onBlur: fn(),
    onClear: fn(),
  },
  args: {
    query: '',
    loading: false,
    typeToSearch: false,
    disabled: false,
    placeholder: 'Ieškokite dokumentų...',
    recentSearches: mockRecentSearches,
    maxSuggestions: 5,
    onUpdateQuery: fn(),
    onSearch: fn(),
    onSelectRecent: fn(),
    onRemoveRecent: fn(),
    onClearRecents: fn(),
    onFocus: fn(),
    onBlur: fn(),
    onClear: fn(),
  },
  decorators: [
    story => ({
      components: { story },
      template: `
        <div class="p-6 bg-gray-50 min-h-screen">
          <div class="max-w-2xl mx-auto">
            <h2 class="text-2xl font-bold mb-6 text-gray-900">Document Search Input</h2>
            <story />
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

// Default state
export const Default: Story = {
  render: args => ({
    components: { DocumentSearchInput },
    setup() {
      return { args };
    },
    template: `
      <DocumentSearchInput
        v-bind="args"
        @update:query="args.onUpdateQuery"
        @search="args.onSearch"
        @select-recent="args.onSelectRecent"
        @remove-recent="args.onRemoveRecent"
        @clear-recents="args.onClearRecents"
        @focus="args.onFocus"
        @blur="args.onBlur"
        @clear="args.onClear"
      />
    `,
  }),
};

// With initial query
export const WithQuery: Story = {
  args: {
    query: 'VU SR protokolai',
  },
  render: args => ({
    components: { DocumentSearchInput },
    setup() {
      return { args };
    },
    template: `
      <DocumentSearchInput
        v-bind="args"
        @update:query="args.onUpdateQuery"
        @search="args.onSearch"
        @select-recent="args.onSelectRecent"
        @remove-recent="args.onRemoveRecent"
        @clear-recents="args.onClearRecents"
        @focus="args.onFocus"
        @blur="args.onBlur"
        @clear="args.onClear"
      />
    `,
  }),
};

// Loading state
export const Loading: Story = {
  args: {
    loading: true,
    query: 'searching...',
  },
  render: args => ({
    components: { DocumentSearchInput },
    setup() {
      return { args };
    },
    template: `
      <DocumentSearchInput
        v-bind="args"
        @update:query="args.onUpdateQuery"
        @search="args.onSearch"
        @select-recent="args.onSelectRecent"
        @remove-recent="args.onRemoveRecent"
        @clear-recents="args.onClearRecents"
        @focus="args.onFocus"
        @blur="args.onBlur"
        @clear="args.onClear"
      />
    `,
  }),
};

// Type to search mode
export const TypeToSearch: Story = {
  args: {
    typeToSearch: true,
  },
  render: args => ({
    components: { DocumentSearchInput },
    setup() {
      return { args };
    },
    template: `
      <DocumentSearchInput
        v-bind="args"
        @update:query="args.onUpdateQuery"
        @search="args.onSearch"
        @select-recent="args.onSelectRecent"
        @remove-recent="args.onRemoveRecent"
        @clear-recents="args.onClearRecents"
        @focus="args.onFocus"
        @blur="args.onBlur"
        @clear="args.onClear"
      />
    `,
  }),
};

// Disabled state
export const Disabled: Story = {
  args: {
    disabled: true,
    query: 'Cannot edit',
  },
  render: args => ({
    components: { DocumentSearchInput },
    setup() {
      return { args };
    },
    template: `
      <DocumentSearchInput
        v-bind="args"
        @update:query="args.onUpdateQuery"
        @search="args.onSearch"
        @select-recent="args.onSelectRecent"
        @remove-recent="args.onRemoveRecent"
        @clear-recents="args.onClearRecents"
        @focus="args.onFocus"
        @blur="args.onBlur"
        @clear="args.onClear"
      />
    `,
  }),
};

// No recent searches
export const NoRecentSearches: Story = {
  args: {
    recentSearches: [],
  },
  render: args => ({
    components: { DocumentSearchInput },
    setup() {
      return { args };
    },
    template: `
      <DocumentSearchInput
        v-bind="args"
        @update:query="args.onUpdateQuery"
        @search="args.onSearch"
        @select-recent="args.onSelectRecent"
        @remove-recent="args.onRemoveRecent"
        @clear-recents="args.onClearRecents"
        @focus="args.onFocus"
        @blur="args.onBlur"
        @clear="args.onClear"
      />
    `,
  }),
};

// Interactive story with user actions
export const Interactive: Story = {
  render: args => ({
    components: { DocumentSearchInput },
    setup() {
      return { args };
    },
    template: `
      <DocumentSearchInput
        v-bind="args"
        @update:query="args.onUpdateQuery"
        @search="args.onSearch"
        @select-recent="args.onSelectRecent"
        @remove-recent="args.onRemoveRecent"
        @clear-recents="args.onClearRecents"
        @focus="args.onFocus"
        @blur="args.onBlur"
        @clear="args.onClear"
      />
    `,
  }),
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);

    // Focus the input
    const input = canvas.getByPlaceholderText('Ieškokite dokumentų...');
    await userEvent.click(input);

    // Type a search query
    await userEvent.type(input, 'VU SR protokolai');

    // Verify the query was updated
    await new Promise(resolve => setTimeout(resolve, 500));

    // Press Enter to search
    await userEvent.keyboard('{Enter}');

    // Wait a bit for the action to be logged
    await new Promise(resolve => setTimeout(resolve, 200));
  },
};

// Custom placeholder
export const CustomPlaceholder: Story = {
  args: {
    placeholder: 'Search for meeting minutes, reports, and documents...',
  },
  render: args => ({
    components: { DocumentSearchInput },
    setup() {
      return { args };
    },
    template: `
      <DocumentSearchInput
        v-bind="args"
        @update:query="args.onUpdateQuery"
        @search="args.onSearch"
        @select-recent="args.onSelectRecent"
        @remove-recent="args.onRemoveRecent"
        @clear-recents="args.onClearRecents"
        @focus="args.onFocus"
        @blur="args.onBlur"
        @clear="args.onClear"
      />
    `,
  }),
};

// Long recent searches list
export const ManyRecentSearches: Story = {
  args: {
    recentSearches: [
      'VU SR protokolai 2024',
      'studijų programa informatika',
      'padalinio susirinkimas rugsėjis',
      'rektorato posėdis spalio mėnuo',
      'studentų atstovybė rinkimai',
      'akademinės etikos komisija',
      'kokybės užtikrinimo sistema',
      'tarptautinio bendradarbiavimo sutartys',
      'studijų rezultatų analizė',
      'mokslinių tyrimų ataskaita',
    ],
    maxSuggestions: 3,
  },
  render: args => ({
    components: { DocumentSearchInput },
    setup() {
      return { args };
    },
    template: `
      <DocumentSearchInput
        v-bind="args"
        @update:query="args.onUpdateQuery"
        @search="args.onSearch"
        @select-recent="args.onSelectRecent"
        @remove-recent="args.onRemoveRecent"
        @clear-recents="args.onClearRecents"
        @focus="args.onFocus"
        @blur="args.onBlur"
        @clear="args.onClear"
      />
    `,
  }),
};
