import type { ChecklistItemStatus, OnboardingChecklistData } from '../types';

export function checklistFixture(statuses: Record<string, ChecklistItemStatus> = {}): OnboardingChecklistData {
  const items = ['access', 'contacts', 'preferences', 'installation', 'push', 'orientation'].map(key => ({
    key,
    title: `onboarding.items.${key}.title`,
    description: `onboarding.items.${key}.description`,
    actionLabel: `onboarding.items.${key}.action`,
    confirmLabel: `onboarding.items.${key}.confirm`,
    skipLabel: ['installation', 'push'].includes(key) ? `onboarding.items.${key}.skip` : null,
    href: ['installation', 'push'].includes(key) ? null : `/mano/${key}`,
    status: statuses[key] ?? 'pending',
  }));
  const resolved = items.filter(item => item.status !== 'pending').length;
  const next = items.find(item => item.status === 'pending');
  return {
    key: 'getting-started-v1', title: 'onboarding.title', items, resolved, total: 6,
    hidden: false, complete: resolved === 6, href: '/mano/onboarding',
    nextHref: next ? `/mano/onboarding?item=${next.key}` : '/mano/onboarding',
  };
}
