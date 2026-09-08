import type { ReactNode } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import { BarChart3, Bell, ClipboardCheck, LayoutDashboard, LogOut, UserRound } from "lucide-react";
import { Brand } from "@/components/brand";
import { Button } from "@/components/ui/button";
import type { SharedProps } from "@/types";

const navigation = [
    ["Dashboard", "instructor.dashboard", LayoutDashboard],
    ["Absensi", "instructor.attendances.index", ClipboardCheck],
    ["Izin & sakit", "instructor.leave-requests.index", ClipboardCheck],
    ["Laporan", "instructor.reports.index", BarChart3],
    ["Profil", "instructor.profile.edit", UserRound],
] as const;

export function InstructorLayout({ children, title }: { children: ReactNode; title: string }) {
    const { auth } = usePage<SharedProps>().props;
    return <div className="min-h-screen bg-slate-50">
        <header className="border-b bg-slate-950 text-white">
            <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 p-4"><Link href={route("instructor.dashboard")}><Brand inverse /></Link><div className="flex items-center gap-2"><Button asChild variant="ghost" size="icon" className="relative text-white" aria-label="Notifikasi"><Link href={route("notifications.index")}><Bell />{!!auth.user?.unread_notifications_count && <span className="absolute right-0 top-0 rounded-full bg-red-500 px-1 text-[10px]">{auth.user.unread_notifications_count}</span>}</Link></Button><Button variant="ghost" className="text-white" onClick={() => router.post(route("logout"))}><LogOut /><span className="hidden sm:inline">Keluar</span></Button></div></div>
            <nav className="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 pb-3">{navigation.map(([label, name, Icon]) => <Button key={name} asChild variant="ghost" className="shrink-0 text-blue-100 hover:bg-white/10 hover:text-white"><Link href={route(name)}><Icon />{label}</Link></Button>)}</nav>
        </header>
        <main className="mx-auto max-w-7xl p-4 sm:p-6"><p className="text-xs font-semibold tracking-widest text-primary">PANEL INSTRUKTUR</p><h1 className="mt-1 text-2xl font-bold">{title}</h1><div className="mt-6">{children}</div></main>
    </div>;
}
