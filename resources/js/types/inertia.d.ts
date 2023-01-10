export {};

import { LocaleEnum, ModelEnum } from "./enums";

interface User extends Omit<App.Entities.User, "padaliniai"> {
  padaliniai: Pick<App.Entities.Padalinys, "id" | "shortname">;
  isSuperAdmin: boolean;
  unreadNotifications: Record<string, any>[] | null;
}

declare module "@inertiajs/inertia" {
  interface PageProps {
    app: {
      env: "local" | "production";
      url: string;
    };
    auth: {
      can: {
        index: { [str in ModelEnum]?: boolean };
      };
      user: User;
    } | null;
    flash: {
      data: any;
      info: string | null;
      success: string | null;
    };
    locale: LocaleEnum;
    misc: any;
    padaliniai: Pick<
      App.Entities.Padalinys,
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
