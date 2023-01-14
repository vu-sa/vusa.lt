export const contactTemplate: Pick<
  App.Entities.Contact,
  "name" | "email" | "phone" | "extra_attributes"
> = {
  name: "",
  email: "",
  phone: null,
  extra_attributes: {
    degree: null,
    pedagogical_name: null,
  },
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

export const typeTemplate: Pick<
  App.Entities.Type,
  "title" | "slug" | "description" | "model_type" | "parent_id"
> = {
  title: "",
  slug: "",
  description: "",
  model_type: "",
  parent_id: null,
};
