import { AxiosStatic } from "axios";

export {};

// bootstrap.js
declare global {
  interface Window {
    axios: AxiosStatic;
  }

  interface RoleData {
    id: number;
    description: string;
    alias: string;
    name: string;
    created_at: string;
    updated_at: string;
  }
}
