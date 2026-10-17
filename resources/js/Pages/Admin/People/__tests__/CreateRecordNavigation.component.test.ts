import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import CreateDuty from '../CreateDuty.vue';
import CreateInstitution from '../CreateInstitution.vue';

import { createMockForm } from '@/tests/helpers/createMockForm';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

beforeEach(() => vi.clearAllMocks());

describe('standalone record creation', () => {
  it.each([
    ['duty', CreateDuty, 'DutyForm', 'duties.store', {
      dutyTypes: [], assignableInstitutions: [], roles: [], assignableTenants: [], assignableDuties: [],
    }],
    ['institution', CreateInstitution, 'InstitutionForm', 'institutions.store', {
      assignableTenants: [], institutionTypes: [],
    }],
  ] as const)('lets the server choose the destination for a new %s', async (_label, component, formName, storeRoute, props) => {
    const form = createMockForm();
    const wrapper = mount(component, {
      props,
      global: {
        stubs: {
          [formName]: {
            emits: ['submit:form'],
            setup: () => ({ form }),
            template: '<button @click="$emit(\'submit:form\', form)">Save</button>',
          },
        },
      },
    });

    await wrapper.get('button').trigger('click');

    expect(form.post).toHaveBeenCalledExactlyOnceWith(route(storeRoute));
    expect(router.visit).not.toHaveBeenCalled();
    wrapper.unmount();
  });
});
