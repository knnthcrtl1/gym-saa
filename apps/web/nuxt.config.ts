import vuetify from "vite-plugin-vuetify";

export default defineNuxtConfig({
  compatibilityDate: "2025-01-01",
  devtools: { enabled: true },

  css: ["vuetify/styles", "@/assets/css/main.css"],

  build: {
    transpile: ["vuetify"],
  },

  vite: {
    plugins: [vuetify({ autoImport: true })],
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
