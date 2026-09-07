import { Link, router } from "@inertiajs/react";
import { Home, LogOut, UserRound } from "lucide-react";
import { Button } from "@/components/ui/button";

export function ParticipantQuickNav({ compact = false }: { compact?: boolean }) {
    return (
        <nav
            className="flex flex-wrap items-center justify-end gap-1"
            aria-label="Navigasi peserta"
        >
            <Button asChild variant="ghost" size={compact ? "icon" : "sm"} className="text-white hover:text-white">
                <Link href={route("dashboard")} aria-label="Dashboard">
                    <Home />
                    {!compact && <span>Dashboard</span>}
                </Link>
            </Button>
            <Button asChild variant="ghost" size={compact ? "icon" : "sm"} className="text-white hover:text-white">
                <Link href={route("profile.edit")} aria-label="Profil">
                    <UserRound />
                    {!compact && <span>Profil</span>}
                </Link>
            </Button>
            <Button
                type="button"
                variant="ghost"
                size={compact ? "icon" : "sm"}
                className="text-white hover:text-white"
                aria-label="Keluar"
                onClick={() => router.post(route("logout"))}
            >
                <LogOut />
                {!compact && <span>Keluar</span>}
            </Button>
        </nav>
    );
}
