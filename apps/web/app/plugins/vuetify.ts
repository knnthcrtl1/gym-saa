import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";

export default defineNuxtPlugin((nuxtApp) => {
  const vuetify = createVuetify({
    components,
    directives,
    theme: {
      defaultTheme: "gymLight",
      themes: {
        gymLight: {
          dark: false,
          colors: {
            primary: "#4F46E5",
            secondary: "#F4F7FE",
            accent: "#FF9F43",
            background: "#F4F7FE",
            surface: "#FFFFFF",
            "surface-bright": "#FFFFFF",
            "surface-light": "#F0F2F8",
            "surface-variant": "#E8EAF0",
            "on-primary": "#FFFFFF",
            "on-secondary": "#1E293B",
            "on-surface": "#1E293B",
            "on-background": "#1E293B",
            error: "#EF4444",
            success: "#22C55E",
            warning: "#F59E0B",
            info: "#3B82F6",
          },
        },
        gymDark: {
          dark: true,
          colors: {
            primary: "#6366F1",
            secondary: "#111111",
            accent: "#FF9F43",
            background: "#0A0A0F",
            surface: "#1A1A24",
            "surface-bright": "#242430",
            "surface-light": "#2A2A36",
            "surface-variant": "#14141C",
            "on-primary": "#FFFFFF",
            "on-secondary": "#E2E8F0",
            "on-surface": "#E2E8F0",
            "on-background": "#E2E8F0",
            error: "#F87171",
            success: "#4ADE80",
            warning: "#FBBF24",
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
          "text-transform: none; letter-spacing: 0.02em; font-weight: 600;",
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
