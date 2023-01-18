import { genitivize } from "@/Utils/String";

type Form = {
  dateValue: string | null;
  nameValue: string;
  typeValue: string;
};

type Fileable = {
  fileable_id: string;
  fileable_name: string;
  fileable_type: string;
};

export const generateNameForFile = (form: Form, fileable: Fileable) => {
  const date = form?.dateValue === null ? new Date() : new Date(form.dateValue);

  const dateFormatter = new Intl.DateTimeFormat("lt-LT", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });

  if (fileable.fileable_type === "App\\Models\\Meeting") {
    return {
      fileName: `${dateFormatter.format(date)} ${genitivize(
        fileable.fileable_name
      )} posėdžio protokolas`,
      isFileNameEditable: false,
    };
  }

  return { fileName: form.nameValue, isFileNameEditable: true };
};
