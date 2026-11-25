import { LocaleEnum, TenantType } from '@/Types/enums';
import type { PageProps } from '@/Types/index';

export function defaultPageProps(): PageProps {
  const mainTenant = { id: 1, alias: 'vusa', shortname: 'VU SA' };

  const flash = {
    data: null,
    success: null,
    error: null,
    info: null,
    toast_duration: null,
    toast_description: null,
    access_change_warning: null,
  };

  return {
    app: { env: 'testing', locale: LocaleEnum.LT, path: 'mano', url: 'https://www.vusa.test' },
    ziggyContext: { url: 'https://www.vusa.test', port: null, defaults: {} },
    errors: {},
    head: [],
    organization: {
      contacts: { it: 'it@vusa.lt', accounting: 'saskaitos@vusa.lt', phone: '+37052687144' },
      social: {
        facebook: 'https://www.facebook.com/vieningai.vu.sa',
        instagram: 'https://www.instagram.com/vu.studentu.atstovybe',
        linkedin: 'https://www.linkedin.com/company/vusa-lt',
      },
      legal: {
        company_code: '193077294',
        vat_code: 'LT100015645710',
        address: { street: 'Universiteto g. 3, Observatorijos kiemelis', city: '01513, Vilnius, Lietuva' },
      },
      privacyPageUrl: null,
    },
    auth: {
      user: {
        id: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        name: 'Test User',
        email: 'test@vusa.lt',
        is_active: true,
        show_pronouns: false,
        pronouns: [],
        created_at: '2026-01-01T00:00:00.000000Z',
        updated_at: '2026-01-01T00:00:00.000000Z',
        tenants: [{ ...mainTenant }],
        isSuperAdmin: false,
        tasks_count: 0,
        overdue_tasks_count: 0,
        unreadNotifications: [],
        unreadNotificationsCount: 0,
        tutorial_progress: [],
        ui_preferences: { pinned_pages: [], recent_pages: [] },
      },
      can: {
        index: { meeting: true, document: true },
        create: { meeting: true, document: true },
        forceDelete: { meeting: false, document: false },
      },
      impersonating: null,
    },
    csrf_token: 'test-csrf-token',
    flash,
    seo: { title: null },
    search: null,
    pwa: { vapidPublicKey: null, hasPushSubscription: false, subscriptionEndpoints: [] },
    typesenseConfig: [],
    adminNavigation: { workspaces: [] },
    map: { cartoApiKey: null },
    tenants: [
      {
        ...mainTenant,
        fullname: 'Vilniaus universiteto Studentų atstovybė',
        type: TenantType.Pagrindinis,
        primary_institution_id: null,
        primary_institution: null,
      },
      {
        id: 2,
        alias: 'if',
        shortname: 'VU SA IF',
        fullname: 'Vilniaus universiteto Studentų atstovybė Istorijos fakultete',
        type: TenantType.Padalinys,
        primary_institution_id: null,
        primary_institution: null,
      },
      {
        id: 3,
        alias: 'mif',
        shortname: 'VU SA MIF',
        fullname: 'Vilniaus universiteto Studentų atstovybė Matematikos ir informatikos fakultete',
        type: TenantType.Padalinys,
        primary_institution_id: null,
        primary_institution: null,
      },
    ],
    institutionTypes: [],
    curatorDutyTypeIds: [8, 9],
    eventTypes: [
      { id: 1, slug: 'stovykla', name: 'Stovykla' },
      { id: 2, slug: 'konferencija', name: 'Konferencija' },
    ],
    tags: [
      { id: 1, alias: 'akademine-informacija', name: 'Akademinė informacija', is_topic: true },
      { id: 2, alias: 'renginys', name: 'Renginys', is_topic: false },
    ],
  };
}
