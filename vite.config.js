import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/css/backoffice.css",
                "resources/js/backoffice.js",
                "resources/css/auth.css",
                "resources/js/auth.js",
                "resources/css/landing.css",
                "resources/js/landing.js",
                "resources/css/account.css",
                "resources/js/account.js",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
