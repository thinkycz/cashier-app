import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import cs from './locales/cs.json';

const messages = {
    en,
    cs
};

export const i18n = createI18n({
    legacy: false,
    locale: 'en', // default, will be overridden by server state
    fallbackLocale: 'en',
    messages,
});
