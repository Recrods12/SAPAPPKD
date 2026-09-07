import "../css/app.css";
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { MotionConfig } from "motion/react";
import { Toaster } from "@/components/ui/sonner";
import { OfflineStatus } from "@/components/offline-status";
import type { ComponentType } from "react";

const pages = import.meta.glob<{ default: ComponentType }>("./pages/**/*.tsx");

createInertiaApp({
    title: (title) => (title ? `${title} — SAPA PPKD` : "SAPA PPKD"),
    resolve: async (name) => {
        const importer = pages[`./pages/${name}.tsx`];
        if (!importer)
            throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
        return (await importer()).default;
    },
    setup({ el, App, props }) {
        if (!el) throw new Error("Elemen root Inertia tidak ditemukan.");
        createRoot(el).render(
            <MotionConfig reducedMotion="user">
                <OfflineStatus />
                <App {...props} />
                <Toaster />
            </MotionConfig>,
        );
    },
    progress: { color: "#2563eb", showSpinner: false },
});

if (
    "serviceWorker" in navigator &&
    (window.isSecureContext ||
        location.hostname === "localhost" ||
        location.hostname === "127.0.0.1")
) {
    window.addEventListener("load", () =>
        navigator.serviceWorker.register("/sw.js").catch(() => undefined),
    );
}
