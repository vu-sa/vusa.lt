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
  if (fileable.fileable_type === "Meeting") {
    return {
      fileName: `${genitivize(fileable.fileable_name)} protokolas`,
      isFileNameEditDisabled: true,
    };
  }

  return { fileName: form.nameValue, isFileNameEditDisabled: false };
};
