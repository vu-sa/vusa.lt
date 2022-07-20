declare global {
  export interface InertiaUserGlobal extends App.Models.User {
    padalinys: "string";
  }

  export interface InertiaProps {
    app: {
      env: string;
      url: string;
    };
    can: {
      [key: string]: boolean;
    };
    errors: {
      [key: string]: string;
    };
    locale: "lt" | "en";
    misc: any;
    padaliniai: Array<{
      id: number;
      alias: string;
      shortname: string;
      fullname: string;
    }>;

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
  export interface PaginatedModels<T> {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    // links are array of object with active, label and url
    links: Array<{
      active: boolean;
      label: string;
      url: string;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
  }

  // export interface InertiaPropsPage = Omit<Page<InertiaProps>, "props"> {
  //   props: InertiaProps;
  // }
}

import { Page, PageProps } from "@inertiajs/inertia";
