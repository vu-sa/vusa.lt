import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import { toPlainText, useInstitutionCard } from '../useInstitutionCard';

import { TenantType } from '@/Types/enums';

describe('toPlainText', () => {
  it('keeps paragraphs apart and decodes common entities', () => {
    expect(toPlainText('<p>Rašyk&nbsp;<strong>mums</strong></p><p>Q&amp;A</p>')).toBe('Rašyk mums Q&A');
  });

  it('returns null when nothing visible is left', () => {
    expect(toPlainText('<p> </p>')).toBeNull();
    expect(toPlainText(null)).toBeNull();
  });
});

describe('useInstitutionCard url', () => {
  const padalinys = { id: 11, alias: 'mif', shortname: 'VU SA MIF', type: TenantType.Padalinys };

  it('opens the coordinators of a padalinys’s own institution', () => {
    const { url } = useInstitutionCard({ id: 'i1', alias: 'mif', tenant: padalinys });

    expect(url.value).toContain('contacts.dutyType');
    expect(url.value).toContain('type=koordinatoriai');
    expect(url.value).toContain('subdomain=mif');
  });

  it('opens other institutions of the padalinys by alias', () => {
    const { url } = useInstitutionCard({ id: 'i2', alias: 'mif-taryba', tenant: padalinys });

    expect(url.value).toContain('contacts.alias');
    expect(url.value).toContain('institution=mif-taryba');
  });
});
