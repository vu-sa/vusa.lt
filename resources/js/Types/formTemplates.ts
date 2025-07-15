export const calendarTemplate: Omit<App.Entities.Calendar, "created_at" | "updated_at" | "registration_form_id" | "tenant" | "registration_form" | "media"> = {
  title: { lt: '', en: '' },
  date: null,
  end_date: null,
  description: { lt: '', en: '' },
  location: { lt: '', en: '' },
  organizer: { lt: '', en: '' },
  cto_url: { lt: '', en: '' },
  tenant_id: null,
  images: [],
  category: null,
  facebook_url: "",
  youtube_url: "",
  is_draft: false,
  is_all_day: false,
  is_international: false,
};

export const changelogItemTemplate: Pick<App.Entities.ChangelogItem, "date"> & {
  title: Record<"lt" | "en", string>;
  description: Record<"lt" | "en", string>;
} = {
  title: {
    lt: "",
    en: "",
  },
  description: {
    lt: "",
    en: "",
  },
  date: null,
};

export const doingTemplate: Pick<
  App.Entities.Doing,
  "title" | "type_id" | "status" | "date"
> = {
  title: "",
  type_id: null,
  status: "Sukurtas",
  date: new Date().toISOString().split("T").join(" ").slice(0, 16) + ":00",
};

export const formTemplate: Pick<
  App.Entities.Form,
  "name" | "description" | "path" | "form_fields"
> = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  path: { lt: '', en: '' },
  form_fields: [],
  tenant_id: null,
};


export const formFieldTemplate: Pick<App.Entities.FormField, "label" | "description" | "default_value" | "placeholder" | "type" | "subtype" | "is_required">
  = {
  label: { lt: "", en: "" },
  description: { lt: "", en: "" },
  default_value: { lt: "" },
  placeholder: { lt: "" },
  type: "string",
  subtype: null,
  is_required: false,
};

export const matterTemplate: Pick<
  App.Entities.Matter,
  "title" | "description"
> = {
  title: "",
  description: "",
};

export const meetingTemplate = {
  institution_id: null,
  start_time: null,
};

export const newsTemplate = {
  title: "",
  permalink: "",
  lang: "lt",
  other_lang_id: null,
  content: {
    parts: [
      {
        type: "tiptap",
        json_content: {},
      },
    ]
  },
  short: "",
  publish_time: new Date().toISOString().slice(0, 19).replace("T", " "),
  draft: false,
  image: null,
  image_author: null,
  tags: [],
}

export const typeTemplate: Pick<
  App.Entities.Type,
  "title" | "slug" | "description" | "model_type" | "parent_id"
> = {
  title: { lt: '', en: '' },
  slug: "",
  description: { lt: '', en: '' },
  model_type: "",
  parent_id: null,
  roles: [],
};

export const tagTemplate: Pick<
  App.Entities.Tag,
  "name" | "description" | "alias"
> = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  alias: "",
};
