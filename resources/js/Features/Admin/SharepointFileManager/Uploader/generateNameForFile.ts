import { genitivize } from '@/Utils/String';

interface Form {
  dateValue: string | null;
  nameValue: string;
  typeValue: string;
}

export const generateNameForFile = (form: Form, fileable?: FileableFormData) => {
  // Meeting files get auto-generated names if fileable_name is available
  if (fileable?.type === 'Meeting' && fileable.fileable_name) {
    return {
      fileName: `${genitivize(fileable.fileable_name)} protokolas`,
      isFileNameEditDisabled: true,
    };
  }

  return { fileName: form.nameValue, isFileNameEditDisabled: false };
};
