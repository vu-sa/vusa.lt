export const calendarTemplate: Omit<App.Entities.Calendar, 'created_at' | 'updated_at' | 'registration_form_id' | 'tenant' | 'registration_form' | 'media'> = {
  title: { lt: '', en: '' },
  date: null,
  end_date: null,
  description: { lt: '', en: '' },
  location: { lt: '', en: '' },
  organizer: { lt: '', en: '' },
  cto_url: { lt: '', en: '' },
  tenant_id: null,
  main_image: null,
  images: [],
  category_id: null,
  facebook_url: '',
  youtube_url: '',
  is_draft: false,
  is_all_day: false,
  is_international: false,
  hero_style: 'card',
};

export const formTemplate: Pick<
  App.Entities.Form,
  'name' | 'description' | 'path' | 'form_fields'
> = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  path: { lt: '', en: '' },
  form_fields: [],
  tenant_id: null,
};

export const formFieldTemplate: Pick<App.Entities.FormField, 'label' | 'description' | 'default_value' | 'placeholder' | 'type' | 'subtype' | 'is_required'>
  = {
    label: { lt: '', en: '' },
    description: { lt: '', en: '' },
    default_value: { lt: '' },
    placeholder: { lt: '' },
    type: 'string',
    subtype: null,
    is_required: false,
  };

export const meetingTemplate = {
  institution_id: null,
  start_time: null,
};

export const newsTemplate = {
  title: '',
  permalink: '',
  lang: 'lt',
  other_lang_id: null,
  content: {
    parts: [
      {
        type: 'tiptap',
        json_content: {},
        key: 'initial-tiptap',
      },
    ],
  },
  short: '',
  // ISO instant (not a naive "YYYY-MM-DD HH:mm:ss" string) so both the date picker
  // (`new Date(form.publish_time)`, NewsForm.vue) and the backend
  // (StoreNewsRequest::prepareForValidation(), which runs this through strtotime())
  // read it as the same moment instead of re-interpreting it in Europe/Vilnius —
  // that mismatch used to backdate every new, untouched publish_time by ~3 hours.
  publish_time: new Date().toISOString(),
  draft: false,
  image: null,
  image_author: null,
  tags: [],
  highlights: [],
};

export const pageTemplate = {
  title: '',
  permalink: '',
  lang: 'lt',
  category_id: null,
  other_lang_id: null,
  is_active: true,
  content: {
    parts: [
      {
        type: 'tiptap',
        json_content: {},
        key: 'initial-tiptap',
      },
    ],
  },
  highlights: [],
  layout: 'default' as const,
  featured_image: null,
  meta_description: '',
  publish_time: null,
};

export const typeTemplate: Pick<
  App.Entities.Type,
  'title' | 'slug' | 'description' | 'model_type' | 'parent_id' | 'extra_attributes'
> = {
  title: { lt: '', en: '' },
  slug: '',
  description: { lt: '', en: '' },
  model_type: '',
  parent_id: null,
  roles: [],
  extra_attributes: {},
};

export const tagTemplate: Pick<
  App.Entities.Tag,
  'name' | 'description' | 'alias'
> = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  alias: '',
};

export const studySetTemplate = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  order: 0,
  is_visible: true,
  tenant_id: null as number | null,
  courses: [] as Array<{
    id?: string;
    name: { lt: string; en: string };
    semester: string;
    credits: number;
    order: number;
    is_visible: boolean;
  }>,
  reviews: [] as Array<{
    id?: string;
    study_set_course_id: string;
    lecturer: { lt: string; en: string };
    comment: { lt: string; en: string };
    is_visible: boolean;
  }>,
};
