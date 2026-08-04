import type { Meta, StoryObj } from '@storybook/vue3-vite';


import NavigationBuilder from './NavigationBuilder.vue';
import type { AdminNavigationLink, AdminNavigationRoot } from './types';

import { TooltipProvider } from '@/Components/ui/tooltip';

/** Inline so the story needs no server to render the background-image card. */
const heroImage = `data:image/svg+xml;utf8,${encodeURIComponent(
  `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#b91c1c"/><stop offset="1" stop-color="#7f1d1d"/></linearGradient></defs><rect width="800" height="600" fill="url(#g)"/></svg>`,
)}`;

function link(id: number, name: string, overrides: Partial<AdminNavigationLink> = {}): AdminNavigationLink {
  return {
    id,
    name,
    url: `/lt/${name.toLowerCase().replace(/\s+/g, '-')}`,
    parent_id: 1,
    lang: 'lt',
    order: id,
    is_active: true,
    extra_attributes: {},
    ...overrides,
  };
}

/**
 * A root shaped like the ones that exposed the layout bugs: column 1 holds a
 * full-height background link (which has to stretch to the row's height), column 2 is
 * the tallest, and column 3 is empty.
 */
const studentamsRoot: AdminNavigationRoot = {
  ...link(1, 'Studentams'),
  parent_id: 0,
  cols: 3,
  links: [
    [link(10, 'Tapk savanoriu', { extra_attributes: { type: 'full-height-background-link', image: heroImage, column: 1 } })],
    [
      link(20, 'Studijų kokybė', { extra_attributes: { column: 2, description: 'Kaip pranešti apie problemą' } }),
      link(21, 'Socialinės stipendijos', { extra_attributes: { column: 2 } }),
      link(22, 'Akademinė informacija', { extra_attributes: { column: 2, small_text: 'Nauja', badge_variant: 'rose' } }),
      link(23, 'Tarptautinės studijos', { extra_attributes: { column: 2, featured: true } }),
    ],
    [],
  ],
};

const apieRoot: AdminNavigationRoot = {
  ...link(2, 'Apie VU SA'),
  parent_id: 0,
  cols: 3,
  links: [
    [
      link(30, 'Struktūra', { extra_attributes: { type: 'heading', column: 1 } }),
      link(31, 'Padaliniai', { extra_attributes: { column: 1 } }),
      link(32, 'Kontaktai', { extra_attributes: { column: 1 } }),
    ],
    [],
    [],
  ],
};

const meta: Meta<typeof NavigationBuilder> = {
  title: 'Features/Admin/NavigationBuilder',
  component: NavigationBuilder,
  // The admin layout provides this app-wide; every card and root header has a Switch
  // wrapped in a Tooltip, which throws without the provider in scope.
  decorators: [() => ({ components: { TooltipProvider }, template: '<TooltipProvider><story /></TooltipProvider>' })],
  args: {
    roots: [studentamsRoot, apieRoot],
    lang: 'lt',
  },
};

export default meta;
type Story = StoryObj<typeof meta>;

export const EditMode: Story = {};

/**
 * The builder is reachable on a phone, so the columns, the root header and the
 * full-height card all have to stay inside the admin content area at this width.
 */
export const EditModeMobile: Story = {
  globals: { viewport: { value: 'mobile1', isRotated: false } },
};

export const WithInactiveRoot: Story = {
  args: {
    roots: [{ ...studentamsRoot, is_active: false }],
  },
};
