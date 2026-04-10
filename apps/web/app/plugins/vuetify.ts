import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";

export default defineNuxtPlugin((nuxtApp) => {
  const vuetify = createVuetify({
    components,
    directives,
    theme: {
      defaultTheme: "gymTheme",
      themes: {
        gymTheme: {
          dark: true,
          colors: {
            primary: "#FF3B3B",
            secondary: "#111111",
            accent: "#D4A017",
            background: "#0A0A0A",
            surface: "#1A1A1A",
            "surface-bright": "#242424",
            "surface-light": "#2A2A2A",
            "surface-variant": "#141414",
            "on-primary": "#111111",
            "on-secondary": "#F5F1E8",
            "on-surface": "#F5F1E8",
            "on-background": "#F5F1E8",
            error: "#FF6B6B",
            success: "#4ADE80",
            warning: "#D4A017",
            info: "#60A5FA",
          },
        },
      },
    },
    defaults: {
      VApp: {
        style:
          "background: var(--gym-background); color: var(--gym-text-primary);",
      },
      VBtn: {
        rounded: "xl",
        style:
          "text-transform: none; letter-spacing: 0.02em; font-weight: 700;",
      },
      VCard: {
        rounded: "xl",
        elevation: 0,
      },
      VSheet: {
        rounded: "xl",
      },
      VTextField: {
        variant: "outlined",
        density: "comfortable",
        color: "primary",
      },
      VSelect: {
        variant: "outlined",
        density: "comfortable",
        color: "primary",
      },
      VTextarea: {
        variant: "outlined",
        density: "comfortable",
        color: "primary",
      },
      VTable: {
        density: "comfortable",
      },
    },
  });

  nuxtApp.vueApp.use(vuetify);
});
