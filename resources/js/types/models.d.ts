/**
 * This file is auto generated using 'php artisan typescript:generate'
 *
 * Changes to this file will be lost when the command is run again
 */

declare namespace App.Models {
  export interface ModelTemplate extends Record<string, any> {
    id: number;
  }
  export interface Banner extends ModelTemplate {
    id: number;
    title: string;
    image_url: string;
    link_url: string;
    lang: string;
    order: number;
    is_active: number;
    user_id: number;
    padalinys_id: number;
    created_at: any;
    updated_at: any;
    user?: App.Models.User | null;
    padalinys?: App.Models.Padalinys | null;
  }

  export interface Calendar extends ModelTemplate {
    id: number;
    date: string;
    title: string;
    description: string | null;
    location: string | null;
    category: string | null;
    url: string | null;
    user_id: number | null;
    padalinys_id: number;
    attributes: object | null;
    created_at: any;
    updated_at: any;
    user?: App.Models.User | null;
    padalinys?: App.Models.Padalinys | null;
    category?: App.Models.Category | null;
  }

  export interface Category extends ModelTemplate {
    id: number;
    alias: string | null;
    name: string;
    description: string | null;
    created_at: any;
    updated_at: any;
    banners?: App.Models.Banner | null;
  }

  export interface Duty extends ModelTemplate {
    id: number;
    name: string;
    description: string | null;
    type_id: number;
    institution_id: number;
    email: string | null;
    attributes: Record<string, any> | null;
    places_to_occupy: number | null;
    created_at: any;
    updated_at: any;
    users?: Array<App.Models.User> | null;
    type?: App.Models.DutyType | null;
    institution?: App.Models.DutyInstitution | null;
    users_count?: number | null;
  }

  export interface DutyInstitution extends ModelTemplate {
    id: number;
    pid: number | null;
    name: string | null;
    short_name: string | null;
    alias: string;
    description: string | null;
    image_url: string | null;
    type_id: number | null;
    padalinys_id: number | null;
    created_at: any;
    updated_at: any;
    attributes: string | null;
    duties?: Array<App.Models.Duty> | null;
    type?: App.Models.DutyInstitutionType | null;
    padalinys?: App.Models.Padalinys | null;
    duties_count?: number | null;
  }

  export interface DutyInstitutionType extends ModelTemplate {
    id: number;
    name: string;
    alias: string;
    description: string | null;
    attributes: string | null;
    created_at: any;
    updated_at: any;
    duty_institution?: Array<App.Models.DutyInstitution> | null;
    duty_institution_count?: number | null;
  }

  export interface DutyType extends ModelTemplate {
    id: number;
    pid: number | null;
    name: string;
    alias: string;
    description: string | null;
    created_at: any;
    updated_at: any;
    duties?: Array<App.Models.Duty> | null;
    duties_count?: number | null;
  }

  export interface MainPage extends ModelTemplate {
    id: number;
    user_id: number | null;
    link: string | null;
    text: string | null;
    image: string | null;
    position: string;
    order: number | null;
    type: string | null;
    is_active: boolean;
    padalinys_id: number;
    lang: string | null;
    created_at: any;
    updated_at: any;
    padalinys?: App.Models.Padalinys | null;
    users?: Array<App.Models.User> | null;
    users_count?: number | null;
  }

  export interface Navigation extends ModelTemplate {
    id: number;
    parent_id: number;
    user_id: number | null;
    padalinys_id: number;
    name: string;
    lang: string;
    url: string;
    order: number;
    is_active: boolean;
    created_at: any;
    updated_at: any;
    user?: App.Models.User | null;
    padalinys?: App.Models.Padalinys | null;
  }

  export interface News extends ModelTemplate {
    id: number;
    user_id: number | null;
    title: string;
    category_id: number | null;
    permalink: string | null;
    short: string;
    text: string;
    lang: string;
    other_lang_id: number | null;
    image: string | null;
    image_author: string | null;
    important: boolean;
    padalinys_id: number;
    publish_time: any | null;
    main_points: string | null;
    read_more: string | null;
    draft: boolean | null;
    created_at: any;
    updated_at: any;
    user?: App.Models.User | null;
    padalinys?: App.Models.Padalinys | null;
    tags?: Array<App.Models.Tag> | null;
    tags_count?: number | null;
  }

  export interface Padalinys extends ModelTemplate {
    id: number;
    type: string | null;
    fullname: string;
    shortname: string;
    alias: string;
    en: boolean;
    phone: string | null;
    email: string | null;
    address: string | null;
    shortname_vu: string;
    banners?: Array<App.Models.Banner> | null;
    calendar?: Array<App.Models.Calendar> | null;
    duties?: Array<App.Models.Duty> | null;
    institutions?: Array<App.Models.DutyInstitution> | null;
    news?: Array<App.Models.News> | null;
    pages?: Array<App.Models.Page> | null;
    users?: Array<App.Models.User> | null;
    banners_count?: number | null;
    calendar_count?: number | null;
    duties_count?: number | null;
    institutions_count?: number | null;
    news_count?: number | null;
    pages_count?: number | null;
    users_count?: number | null;
  }

  export interface Page extends ModelTemplate {
    id: number;
    user_id: number | null;
    title: string;
    permalink: string | null;
    text: string;
    lang: string;
    other_lang_id: number | null;
    category_id: number | null;
    is_active: boolean;
    padalinys_id: number;
    aside: string | null;
    created_at: any;
    updated_at: any;
    padalinys?: App.Models.Padalinys | null;
    category?: App.Models.Category | null;
  }

  export interface PageView extends ModelTemplate {
    id: number;
    host: string;
    url: string;
    session_id: string | null;
    user_id: number | null;
    ip: string;
    agent: string | null;
    created_at: any | null;
    updated_at: any | null;
  }

  export interface Role extends ModelTemplate {
    id: number;
    description: string | null;
    alias: string | null;
    name: string;
    created_at: any;
    updated_at: any;
  }

  export interface SaziningaiExam extends ModelTemplate {
    id: number;
    uuid: string;
    name: string | null;
    email: string | null;
    exam_type: string | null;
    padalinys_id: number | null;
    place: string | null;
    duration: string | null;
    subject_name: string | null;
    exam_holders: number | null;
    students_need: number | null;
    phone: string | null;
    created_at: any;
    updated_at: any;
    padalinys?: App.Models.Padalinys | null;
    flows?: Array<App.Models.SaziningaiExamFlow> | null;
    observers?: Array<App.Models.SaziningaiExamObserver> | null;
    flows_count?: number | null;
    observers_count?: number | null;
  }

  export interface SaziningaiExamFlow extends ModelTemplate {
    id: number;
    exam_uuid: string;
    start_time: string;
    created_at: any;
    updated_at: any;
    exam?: App.Models.SaziningaiExam | null;
    observers?: Array<App.Models.SaziningaiExamObserver> | null;
    observers_count?: number | null;
  }

  export interface SaziningaiExamObserver extends ModelTemplate {
    id: number;
    exam_uuid: string;
    name: string;
    email: string | null;
    phone: string;
    flow: number;
    created_at: string;
    has_arrived: string;
    phone_p: string | null;
    updated_at: any;
    padalinys_id: number;
    flow?: App.Models.SaziningaiExamFlow | null;
    exam?: App.Models.SaziningaiExam | null;
    padalinys?: App.Models.Padalinys | null;
  }

  export interface Tag extends ModelTemplate {
    id: number;
    alias: string | null;
    name: string;
    description: string | null;
    created_at: any;
    updated_at: any;
  }

  export interface User extends ModelTemplate {
    id: number;
    email: string;
    phone: string | null;
    name: string;
    password: string | null;
    two_factor_secret: string | null;
    two_factor_recovery_codes: string | null;
    is_active: boolean;
    role_id: number | null;
    email_verified_at: any | null;
    remember_token: string | null;
    last_login: any | null;
    microsoft_token: string | null;
    google_token: string | null;
    updated_at: any | null;
    created_at: any;
    team_id: number | null;
    profile_photo_path: string | null;
    banners?: Array<App.Models.Banner> | null;
    calendar?: Array<App.Models.Calendar> | null;
    duties?: Array<App.Models.Duty> | null;
    role?: App.Models.Role | null;
    banners_count?: number | null;
    calendar_count?: number | null;
    duties_count?: number | null;
  }
}
