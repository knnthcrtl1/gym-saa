import vuetify from "vite-plugin-vuetify";
import type { PluginOption } from "vite";

export default defineNuxtConfig({
  compatibilityDate: "2025-01-01",
  devtools: { enabled: true },

  modules: ["@nuxt/icon"],

  css: ["vuetify/styles", "@/assets/css/main.css"],

  build: {
    transpile: ["vuetify"],
  },

  vite: {
    plugins: [vuetify({ autoImport: true }) as unknown as PluginOption],
    ssr: {
      noExternal: ["vuetify"],
    },
  },

  runtimeConfig: {
    public: {
      apiBase: "http://localhost:8000",
      dashboardPreviewMode: true,
    },
  },
});
