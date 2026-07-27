import type { LocaleEnum, ModelEnum } from './enums';

interface User extends Omit<App.Entities.User, 'tenants'> {
  tenants: Pick<App.Entities.Tenant, 'id' | 'shortname'>[];
  isSuperAdmin: boolean;
  unreadNotifications: Record<string, any>[] | null;
  tutorial_progress?: Record<string, string>;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  alias?: string;
  app: {
    env: 'local' | 'production' | 'testing';
    locale: LocaleEnum;
    path: string;
    url: string;
  };
  auth: {
    can: {
      index: { [str in ModelEnum]?: boolean };
      create: { [str in ModelEnum]?: boolean };
      /**
       * Coarse, class-level hint for whether the user may permanently delete a model.
       * False for every model that is not soft-deletable. The per-record decision is
       * still made server-side by the model's policy.
       */
      forceDelete: { [str in ModelEnum]?: boolean };
      manageSettings?: boolean;
      accessAdministration?: boolean;
    };
    changes: Array<{
      title: string;
      description: string;
      date: string;
    }>;
    /**
     * Ids of the member / student rep registration forms, already filtered by the
     * form policy — an id is only present when this user is allowed to open it.
     */
    registrationForms?: {
      member: string | null;
      studentRep: string | null;
    };
    user: User;
  } | null;
  csrf_token: string;
  flash: {
    data: any;
    info: string | null;
    success: string | null;
    error: string | null;
  };
  mainNavigation?: App.Entities.Navigation[];
  otherLangURL?: string | null;
  seo: Record<string, any>;
  tenants: Pick<
    App.Entities.Tenant,
    'id' | 'alias' | 'shortname' | 'fullname' | 'type' | 'primary_institution_id' | 'primary_institution'
  >[];
  tenant:
  | (Pick<App.Entities.Tenant, 'id' | 'alias' | 'shortname' | 'type'> & {
    subdomain: string;
    links: Array<App.Entities.QuickLink | null>;
    banners: Array<App.Entities.Banner> | [];
  })
  | undefined;
  search: {
    calendar: Array<{
      date: string;
      id: number;
      title: string;
    } | null>;
    news: Array<{
      id: number;
      lang: 'lt' | 'en';
      permalink: string;
      publish_time: string;
      title: string;
    } | null>;
    pages: Array<{
      id: number;
      lang: 'lt' | 'en';
      permalink: string;
      title: string;
    } | null>;
    documents: Array<{
      id: number;
      title: string;
      language: 'Lietuvių' | 'English';
      anonymous_url: string;
      document_date: string;
    } | null>;
  };
};
