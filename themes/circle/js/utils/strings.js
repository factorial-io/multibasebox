export const getString = (key) => {
  if(!window.drupalSettings) {
    return key;
  }
  return `${drupalSettings.circle[key]}`
}