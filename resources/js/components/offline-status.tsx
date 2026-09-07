import { useEffect, useState } from "react";
import { WifiOff } from "lucide-react";
import { AnimatePresence, motion } from "motion/react";

export function OfflineStatus() {
    const [online, setOnline] = useState(() => navigator.onLine);

    useEffect(() => {
        const handleOnline = () => setOnline(true);
        const handleOffline = () => setOnline(false);
        window.addEventListener("online", handleOnline);
        window.addEventListener("offline", handleOffline);

        return () => {
            window.removeEventListener("online", handleOnline);
            window.removeEventListener("offline", handleOffline);
        };
    }, []);

    return (
        <AnimatePresence>
            {!online && (
                <motion.div
                    initial={{ opacity: 0, y: -12 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -12 }}
                    role="alert"
                    aria-live="assertive"
                    className="fixed inset-x-0 top-0 z-[100] flex min-h-11 items-center justify-center gap-2 bg-amber-500 px-4 py-2 text-center text-sm font-semibold text-amber-950 shadow-md"
                >
                    <WifiOff className="size-4 shrink-0" aria-hidden="true" />
                    Anda sedang offline. Absensi tidak akan dikirim sampai
                    koneksi kembali tersedia.
                </motion.div>
            )}
        </AnimatePresence>
    );
}
