export {};

interface InertiaUserGlobal extends App.Models.User {
  padalinys: App.Models.Padalinys;
  isSuperAdmin: boolean;
  notifications: Record<string, any>[] | null;
}

declare module "@inertiajs/inertia" {
  interface PageProps {
    app: {
      env: "local" | "production";
      url: string;
    };
    auth: {
      can: {
        [key: string]: boolean;
      };
      user?: InertiaUserGlobal;
    };
    flash: {
      data: any;
      info: string | null;
      success: string | null;
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
  }
}
