import { defineConfig } from "vite";
import react, { reactCompilerPreset } from "@vitejs/plugin-react";
import babel from "@rolldown/plugin-babel";

// https://vite.dev/config/
export default defineConfig({
  server: {
    proxy: {
      "/api": "http://127.0.0.1:8000",
      "/uploads": "http://127.0.0.1:8000",
    },
  },
  plugins: [react(), babel({ presets: [reactCompilerPreset()] })],
});
