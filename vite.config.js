import { readdirSync } from "node:fs";
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

/**
 * Every portal ships one resources/js/{portal}.js entry. If a matching
 * resources/css/{portal}.css exists it's bundled alongside it. New portals
 * scaffolded via `php artisan make:subdomain` are picked up automatically —
 * no manual edit here needed.
 */
function portalEntries() {
    const jsFiles = readdirSync("resources/js", { withFileTypes: true })
        .filter((entry) => entry.isFile() && entry.name.endsWith(".js"))
        .map((entry) => entry.name.replace(/\.js$/, ""));

    const cssFiles = new Set(
        readdirSync("resources/css", { withFileTypes: true })
            .filter((entry) => entry.isFile() && entry.name.endsWith(".css"))
            .map((entry) => entry.name.replace(/\.css$/, "")),
    );

    return jsFiles.flatMap((portal) => [
        ...(cssFiles.has(portal) ? [`resources/css/${portal}.css`] : []),
        `resources/js/${portal}.js`,
    ]);
}

export default defineConfig({
    plugins: [
        laravel({
            input: portalEntries(),
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
