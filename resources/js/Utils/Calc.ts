export const fileSize = (size: number | undefined | null) => {
  if (!size) return "N/A";

  const bytes = size;
  const sizes = ["Bytes", "KB", "MB", "GB", "TB"];
  if (bytes === 0) return "0 Byte";
  const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)).toString());
  return Math.round(bytes / Math.pow(1024, i), 2) + " " + sizes[i];
};
