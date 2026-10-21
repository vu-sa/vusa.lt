import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import ShowSupportRequest from '../ShowSupportRequest.vue';

import { commonStubs } from '@/tests/stubs';
import type { SupportRequestItem } from '@/Types/supportRequests';

const request: SupportRequestItem = {
  id: 'request-1',
  support_service_id: 1,
  support_request_type_id: 1,
  support_request_area_id: 1,
  visibility: 'private',
  status: 'new',
  title: 'Prisijungimo klaida',
  description: 'Negaliu prisijungti.',
  created_at: '2026-10-21T10:00:00Z',
};

describe('support request record', () => {
  it.each([false, true])('shows description, files and discussion together (attachments: %s)', (hasAttachments) => {
    const wrapper = mount(ShowSupportRequest, {
      props: {
        supportRequest: {
          ...request,
          media: hasAttachments ? [{ id: 1, name: 'Screenshot', file_name: 'screenshot.png', mime_type: 'image/png', size: 100, original_url: '/screenshot.png' }] : [],
        },
        availableStatuses: [],
        assignees: [],
        permissions: { can_update: false, can_update_status: false, can_assign: false, can_manage_involved: false, can_delete: false, can_restore: false },
      },
      global: {
        stubs: {
          ...commonStubs,
          DiscussionPanel: { template: '<div data-testid="discussion">Discussion</div>' },
        },
      },
    });

    expect(wrapper.findAll('[role="tab"]')).toHaveLength(0);
    expect(wrapper.get('[aria-labelledby="record-section-description"]').text()).toContain(request.description);
    expect(wrapper.get('[aria-labelledby="record-section-evidence"]').text()).toContain(hasAttachments ? 'screenshot.png' : 'Prisegtų failų nėra.');
    expect(wrapper.get('[aria-labelledby="record-section-discussion"] [data-testid="discussion"]').exists()).toBe(true);
    for (const section of wrapper.findAll('section[aria-labelledby^="record-section-"]')) {
      expect(section.classes()).not.toContain('md:hidden');
    }
    expect(wrapper.find('button').exists()).toBe(false);
  });
});
