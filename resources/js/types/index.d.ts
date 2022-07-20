import "./database";
import "./laravel";
import { AxiosStatic } from "axios";

export {};

// bootstrap.js
declare global {
  interface Window {
    axios: AxiosStatic;
  }
}
