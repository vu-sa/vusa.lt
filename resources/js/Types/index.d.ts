import { LocaleEnum, ModelEnum } from "./enums";

interface User extends Omit<App.Entities.User, "tenants"> {
  tenants: Pick<App.Entities.Tenant, "id" | "shortname">[];
  isSuperAdmin: boolean;
  unreadNotifications: Record<string, any>[] | null;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  alias?: string;
  app: {
    env: "local" | "production" | "testing";
    locale: LocaleEnum;
    path: string;
    url: string;
  };
  auth: {
    can: {
      index: { [str in ModelEnum]?: boolean };
      create: { [str in ModelEnum]?: boolean };
    };
    changes: Array<{
      title: string;
      description: string;
      date: string;
    }>;
    user: User;
  } | null;
  csrf_token: string;
  flash: {
    data: any;
    info: string | null;
    success: string | null;
    statusCode: number | null;
  };
  mainNavigation?: App.Entities.Navigation[];
  otherLangURL?: string | null;
  seo: Record<string, any>;
  tenants: Pick<
    App.Entities.Tenant,
    "id" | "alias" | "shortname" | "fullname" | "type"
  >[];
  tenant:
  | (Pick<App.Entities.Tenant, "id" | "alias" | "shortname" | "type"> & {
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
      lang: "lt" | "en";
      permalink: string;
      publish_time: string;
      title: string;
    } | null>;
    pages: Array<{
      id: number;
      lang: "lt" | "en";
      permalink: string;
      title: string;
    } | null>;
    documents: Array<{
      id: number;
      title: string;
      language: "Lietuvių" | "English";
      anonymous_url: string;
      document_date: string;
    } | null>;
  };
}
