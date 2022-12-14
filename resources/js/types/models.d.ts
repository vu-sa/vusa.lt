/**
 * This file is auto generated using 'php artisan typescript:generate'
 *
 * Changes to this file will be lost when the command is run again
 */

declare namespace App.Models {
  export interface ModelTemplate {
    id: number;
  }

  export interface Banner {
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

  export interface Calendar {
    id: number;
    date: string;
    end_date: string | null;
    title: string;
    description: string | null;
    location: string | null;
    category: string | null;
    url: string | null;
    user_id: number | null;
    padalinys_id: number;
    attributes: string | null;
    created_at: any;
    updated_at: any;
    registration_form_id: number | null;
    user?: App.Models.User | null;
    padalinys?: App.Models.Padalinys | null;
    registration_form?: App.Models.RegistrationForm | null;
  }

  export interface Category {
    id: number;
    alias: string | null;
    name: string;
    description: string | null;
    created_at: any;
    updated_at: any;
    banners?: App.Models.Banner | null;
  }

  export interface Comment {
    id: number;
    parent_id: number | null;
    comment: string;
    user_id: number;
    commentable_type: string;
    commentable_id: number;
    created_at: any;
    updated_at: any;
    commentable?: any | null;
    comments?: Array<App.Models.Comment> | null;
    user?: App.Models.User | null;
    comments_count?: number | null;
  }

  export interface Doing {
    id: number;
    title: string;
    status: string;
    date: string;
    created_at: any;
    updated_at: any;
    questions?: Array<App.Models.Question> | null;
    types?: Array<App.Models.Type> | null;
    documents?: Array<App.Models.SharepointDocument> | null;
    tasks?: Array<App.Models.Task> | null;
    users?: any | null;
    questions_count?: number | null;
    types_count?: number | null;
    documents_count?: number | null;
    tasks_count?: number | null;
  }

  export interface Duty {
    id: number;
    name: string;
    description: string | null;
    institution_id: number;
    order: number;
    email: string | null;
    attributes: string | null;
    places_to_occupy: number | null;
    created_at: any;
    updated_at: any;
    users?: Array<App.Models.User> | null;
    types?: Array<App.Models.Type> | null;
    institution?: App.Models.DutyInstitution | null;
    users_count?: number | null;
    types_count?: number | null;
  }

  export interface DutyInstitution {
    id: number;
    pid: number | null;
    name: string | null;
    short_name: string | null;
    alias: string;
    description: string | null;
    image_url: string | null;
    padalinys_id: number | null;
    created_at: any;
    updated_at: any;
    attributes: string | null;
    duties?: Array<App.Models.Duty> | null;
    types?: Array<App.Models.Type> | null;
    padalinys?: App.Models.Padalinys | null;
    questions?: Array<App.Models.Question> | null;
    doings?: any | null;
    users?: any | null;
    documents?: Array<App.Models.SharepointDocument> | null;
    given_relationships?: Array<App.Models.Relationship> | null;
    received_relationships?: Array<App.Models.Relationship> | null;
    duties_count?: number | null;
    types_count?: number | null;
    questions_count?: number | null;
    documents_count?: number | null;
    given_relationships_count?: number | null;
    received_relationships_count?: number | null;
  }

  export interface DutyUser {
    id: number;
    duty_id: number;
    user_id: number;
    attributes: string | null;
    created_at: any;
    updated_at: any;
    duty?: App.Models.Duty | null;
    user?: App.Models.User | null;
  }

  export interface MainPage {
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

  export interface Navigation {
    id: number;
    parent_id: number;
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

  export interface News {
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

  export interface Padalinys {
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

  export interface Page {
    id: number;
    user_id: number | null;
    title: string;
    permalink: string | null;
    text: string;
    lang: string;
    category_id: number | null;
    is_active: boolean;
    padalinys_id: number;
    created_at: any;
    updated_at: any;
    other_lang_id: number | null;
    padalinys?: App.Models.Padalinys | null;
    category?: App.Models.Category | null;
  }

  export interface Permission {
    id: number;
    name: string;
    guard_name: string;
    created_at: any | null;
    updated_at: any | null;
    roles?: Array<Spatie.Permission.Models.Role> | null;
    users?: Array<App.Models.User> | null;
    permissions?: Array<Spatie.Permission.Models.Permission> | null;
    roles_count?: number | null;
    users_count?: number | null;
    permissions_count?: number | null;
  }

  export interface Question {
    id: number;
    title: string;
    description: string | null;
    status: string;
    question_group_id: number | null;
    institution_id: number;
    created_at: any;
    updated_at: any;
    institution?: App.Models.DutyInstitution | null;
    doings?: Array<App.Models.Doing> | null;
    question_group?: App.Models.QuestionGroup | null;
    users?: any | null;
    doings_count?: number | null;
  }

  export interface QuestionGroup {
    id: number;
    title: string;
    created_at: any;
    updated_at: any;
    questions?: Array<App.Models.Question> | null;
    questions_count?: number | null;
  }

  export interface Registration {
    id: number;
    registration_form_id: number;
    data: string;
    created_at: any;
    updated_at: any;
    registration_form?: App.Models.RegistrationForm | null;
  }

  export interface RegistrationForm {
    id: number;
    user_id: number | null;
    data: string;
    created_at: any;
    updated_at: any;
    registrations?: Array<App.Models.Registration> | null;
    registrations_count?: number | null;
  }

  export interface Relationship {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: string | null;
    created_at: any;
    updated_at: any;
    duty_institutions?: Array<App.Models.DutyInstitution> | null;
    duty_institutions_count?: number | null;
    readonly related_model?: any;
  }

  export interface Relationshipable {
    id: number;
    relationship_id: number;
    relationshipable_type: string;
    relationshipable_id: number;
    related_model_id: number;
    created_at: any;
    updated_at: any;
  }

  export interface Role {
    id: number;
    name: string;
    guard_name: string;
    created_at: any | null;
    updated_at: any | null;
    permissions?: Array<Spatie.Permission.Models.Permission> | null;
    users?: Array<App.Models.User> | null;
    permissions_count?: number | null;
    users_count?: number | null;
  }

  export interface SaziningaiExam {
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

  export interface SaziningaiExamFlow {
    id: number;
    exam_uuid: string;
    start_time: string;
    created_at: any;
    updated_at: any;
    exam?: App.Models.SaziningaiExam | null;
    observers?: Array<App.Models.SaziningaiExamObserver> | null;
    observers_count?: number | null;
  }

  export interface SaziningaiExamObserver {
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

  export interface SharepointDocument {
    sharepoint_id: string;
    documentable_type: string;
    documentable_id: number;
  }

  export interface Tag {
    id: number;
    alias: string | null;
    name: string;
    description: string | null;
    created_at: any;
    updated_at: any;
  }

  export interface Task {
    id: number;
    name: string;
    description: string | null;
    due_date: string | null;
    taskable_type: string;
    taskable_id: number;
    completed_at: any | null;
    created_at: any;
    updated_at: any;
    taskable?: any | null;
    users?: Array<App.Models.User> | null;
    users_count?: number | null;
  }

  export interface Type {
    id: number;
    parent_id: number | null;
    title: string | null;
    model_type: string | null;
    description: string | null;
    slug: string | null;
    extra_attributes: string | null;
    created_at: any;
    updated_at: any;
    duty_institutions?: Array<App.Models.DutyInstitution> | null;
    duties?: Array<App.Models.Duty> | null;
    doings?: Array<App.Models.Doing> | null;
    documents?: Array<App.Models.SharepointDocument> | null;
    duty_institutions_count?: number | null;
    duties_count?: number | null;
    doings_count?: number | null;
    documents_count?: number | null;
  }

  export interface User {
    id: number;
    email: string;
    phone: string | null;
    name: string;
    password: string | null;
    two_factor_secret: string | null;
    two_factor_recovery_codes: string | null;
    is_active: boolean;
    email_verified_at: any | null;
    remember_token: string | null;
    last_login: any | null;
    microsoft_token: string | null;
    google_token: string | null;
    updated_at: any | null;
    created_at: any;
    profile_photo_path: string | null;
    banners?: Array<App.Models.Banner> | null;
    calendar?: Array<App.Models.Calendar> | null;
    duties?: Array<App.Models.Duty> | null;
    banners_count?: number | null;
    calendar_count?: number | null;
    duties_count?: number | null;
  }
}
