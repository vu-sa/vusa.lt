import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { markRaw } from 'vue';
import PublicBreadcrumb from './PublicBreadcrumb.vue';
import IFluentDocument16Regular from '~icons/fluent/document-16-regular';
import IFluentFolder16Regular from '~icons/fluent/folder-16-regular';
import IFluentBookSearch16Regular from '~icons/fluent/book-16-regular';
import IFluentArrowSort16Regular from '~icons/fluent/arrow-sort-16-regular';

// Import Storybook testing utilities instead of Vitest
import { fn } from 'storybook/test';

// Create mock files for the modules we need to mock
// Create laravel-vue-i18n.mock.ts in a separate file and import it here
import { trans } from '../../../../../.storybook/mocks/laravel-vue-i18n.mock';

// Create inertia.mock.ts in a separate file and import it here
import { usePage, router } from '../../../../../.storybook/mocks/inertia.mock';

// Mock the route function
window.route = fn((name: string, params?: Record<string, any>) => {
  return params?.subdomain ? `/${params.subdomain}/${name}` : `/${name}`;
});

// Add a decorator to provide necessary context
const withBreadcrumbContext = (story: any) => ({
  components: { story },
  setup() {
    // No need to add complex breadcrumb context as we're directly passing items to component
    return {};
  },
  template: '<story />'
});

const meta = {
  title: 'Public/Navigation/Breadcrumb',
  component: PublicBreadcrumb,
  decorators: [withBreadcrumbContext],
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'A responsive breadcrumb navigation component used across the public-facing sections of the site. It displays the current location within the website hierarchy and provides navigation links to parent pages.'
      }
    }
  },
  tags: ['autodocs'],
  argTypes: {
    items: {
      description: 'Array of breadcrumb items with label, href, and optional icon',
      control: 'object'
    }
  }
} satisfies Meta<typeof PublicBreadcrumb>;

export default meta;
type Story = StoryObj<typeof meta>;

/**
 * Default state with no items - breadcrumb will not render
 */
export const Empty: Story = {
  args: {
    items: []
  }
};

/**
 * A single-level breadcrumb showing just one item after the home link
 */
export const SingleLevel: Story = {
  args: {
    items: [
      {
        label: 'Documents',
        href: undefined,
        icon: markRaw(IFluentDocument16Regular)
      }
    ]
  },
  parameters: {
    docs: {
      description: {
        story: 'Single-level breadcrumb used for top-level pages. Shows the home icon followed by the current page.'
      }
    }
  }
};

/**
 * A two-level breadcrumb navigation
 */
export const TwoLevels: Story = {
  args: {
    items: [
      {
        label: 'Documents',
        href: '/documents',
        icon: markRaw(IFluentFolder16Regular)
      },
      {
        label: 'University Guidelines',
        href: undefined,
        icon: markRaw(IFluentDocument16Regular)
      }
    ]
  },
  parameters: {
    docs: {
      description: {
        story: 'Two-level breadcrumb showing a parent page that is clickable and the current page.'
      }
    }
  }
};

/**
 * A multi-level breadcrumb navigation showing a deeper path
 */
export const MultipleLevels: Story = {
  args: {
    items: [
      {
        label: 'Documents',
        href: '/documents',
        icon: markRaw(IFluentFolder16Regular)
      },
      {
        label: 'Regulations',
        href: '/documents/regulations',
        icon: markRaw(IFluentBookSearch16Regular)
      },
      {
        label: 'Student Code of Ethics',
        href: undefined,
        icon: markRaw(IFluentDocument16Regular)
      }
    ]
  },
  parameters: {
    docs: {
      description: {
        story: 'Complex breadcrumb navigation for deeply nested content. Shows the full path to the current page with clickable parent links.'
      }
    },
    a11y: {
      config: {
        rules: [
          { id: 'list', enabled: true },
          { id: 'listitem', enabled: true },
          { id: 'aria-allowed-attr', enabled: true }
        ]
      }
    }
  }
};

/**
 * Long labels in breadcrumbs are handled gracefully
 */
export const LongLabels: Story = {
  args: {
    items: [
      {
        label: 'University Administration Documents',
        href: '/documents/administration',
        icon: markRaw(IFluentFolder16Regular)
      },
      {
        label: 'Student Representation in University Governance Bodies and Committees',
        href: undefined,
        icon: markRaw(IFluentArrowSort16Regular)
      }
    ]
  },
  parameters: {
    docs: {
      description: {
        story: 'Demonstrates how breadcrumbs handle long text labels on smaller screens.'
      }
    },
    viewport: {
      defaultViewport: 'mobile2'
    }
  }
};