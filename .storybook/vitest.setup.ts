import { beforeAll, vi } from 'vitest';
import { setProjectAnnotations } from '@storybook/vue3-vite';
import * as a11yAddonAnnotations from "@storybook/addon-a11y/preview";
import * as projectAnnotations from './preview';

// Mock the fn() function that might be used in story files
vi.stubGlobal('fn', vi.fn);

// Create a global route mock and make it available to all tests
const routeMock = vi.fn((name, params) => `/mocked-route/${name}`);
vi.stubGlobal('route', routeMock);

// For browser environment (Storybook running in browser)
if (typeof window !== 'undefined') {
  window.route = routeMock;
}

// Add global mock for stories
vi.mock('storybook/test', async () => {
  const actual = await vi.importActual('storybook/test');
  return {
    ...actual,
    userEvent: {
      click: vi.fn(),
      type: vi.fn(),
      keyboard: vi.fn(),
      tab: vi.fn(),
    },
    within: vi.fn(() => ({
      getByRole: vi.fn().mockReturnValue({}),
      getByText: vi.fn().mockReturnValue({}),
      findByText: vi.fn().mockResolvedValue({}),
      findByRole: vi.fn().mockResolvedValue({}),
      getAllByRole: vi.fn().mockReturnValue([{}]),
    })),
  };
});

// This is an important step to apply the right configuration when testing your stories.
const project = setProjectAnnotations([a11yAddonAnnotations, projectAnnotations]);

beforeAll(project.beforeAll);