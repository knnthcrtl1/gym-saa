export default defineNuxtConfig({
  compatibilityDate: "2025-01-01",
  devtools: { enabled: true },

  css: ["vuetify/styles", "@/assets/css/main.scss"],

  build: {
    transpile: ["vuetify"],
  },

  vite: {
    ssr: {
      noExternal: ["vuetify"],
    },
  },

  runtimeConfig: {
    public: {
      apiBase: "http://localhost:8000/api/v1",
    },
  },
});
