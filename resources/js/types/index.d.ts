import { AxiosStatic } from "axios";

export {};

// bootstrap.js
declare global {
  interface Window {
    axios: AxiosStatic;
  }

  interface vusaPage {
    app: {
      env: string;
      url: string;
    };
    can: Array | null;
    padaliniai: Object;
  }
}
