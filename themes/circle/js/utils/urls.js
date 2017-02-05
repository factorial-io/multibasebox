export const getUrl = (path) => {
  if(!window.drupalSettings) {
    return path;
  }
  return `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}${path}`
}