export {};

declare interface InertiaUserGlobal extends App.Models.User {
  padalinys: "string";
  isSuperAdmin: boolean;
  notifications: Record<string, any>[] | null;
}

declare global {
  export interface InertiaProps {
    app: {
      env: string;
      url: string;
    };
    auth: {
      can: {
        [key: string]: boolean;
      };
      user: InertiaUserGlobal;
    errors: {
      [key: string]: string;
    };
    flash: {
      data: any;
      info: string;
      success: string;
    };
    locale: "lt" | "en";
    misc: any;
    padaliniai: Pick<
      App.Models.Padalinys,
      "id" | "alias" | "shortname" | "fullname"
    >[];
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
      other: Array<{
        alias: string;
        id: number;
        name: string;
      } | null>;
    };
    user: InertiaUserGlobal;
  }
}

// TODO: somehow make these props seeable in the $page object in templates.
declare interface ProjectPage extends Page {
  props: InertiaProps;
}

declare module "@vue/runtime-core" {
  export interface ComponentCustomProperties {
    $page: Record<string, any>;
  }
}

import { ErrorBag, Errors, Page, PageProps } from "@inertiajs/inertia";
