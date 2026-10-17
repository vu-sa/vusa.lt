export const calendarTemplate: Omit<App.Entities.Calendar, 'created_at' | 'updated_at' | 'registration_form_id' | 'tenant' | 'registration_form' | 'media'> = {
  title: { lt: '', en: '' },
  permalink: { lt: '', en: '' },
  date: null,
  end_date: null,
  description: { lt: '', en: '' },
  location: { lt: '', en: '' },
  organizer: { lt: '', en: '' },
  cto_url: { lt: '', en: '' },
  tenant_id: null,
  main_image_media: null,
  images: [],
  event_type_id: null,
  facebook_url: '',
  is_draft: false,
  is_all_day: false,
  is_international: false,
  hero_style: 'card',
  tags: [],
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

export const formFieldTemplate: Pick<App.Entities.FormField, 'label' | 'description' | 'default_value' | 'placeholder' | 'type' | 'subtype' | 'is_required'> & { option_source: null }
  = {
    label: { lt: '', en: '' },
    description: { lt: '', en: '' },
    default_value: { lt: '' },
    placeholder: { lt: '' },
    type: 'string',
    subtype: null,
    is_required: false,
    option_source: null,
  };

export const newsTemplate = {
  title: '',
  permalink: '',
  lang: 'lt',
  other_lang_id: null,
  tenant_id: null,
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
  image_media: null,
  tags: [],
  highlights: [],
};

export const tagTemplate: Pick<
  App.Entities.Tag,
  'name' | 'description' | 'alias' | 'is_topic'
> = {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  alias: '',
  is_topic: false,
};
