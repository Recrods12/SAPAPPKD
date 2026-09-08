import { useState, type ReactNode } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import { AnimatePresence, motion } from "motion/react";
import {
    LayoutDashboard,
    UsersRound,
    GraduationCap,
    Menu,
    X,
    LogOut,
    CalendarRange,
    MapPin,
    School,
    Contact,
    CalendarDays,
    CalendarOff,
    TicketCheck,
    ClipboardCheck,
    BarChart3,
    ListChecks,
    FileUp,
    Settings,
    ScrollText,
    ShieldCheck,
    Bell,
    Megaphone,
    ShieldAlert,
    KeyRound,
} from "lucide-react";
import { Brand } from "@/components/brand";
import { Button } from "@/components/ui/button";
import { slideSidebar } from "@/lib/motion";
import type { SharedProps } from "@/types";
const items = [
    ["Dashboard", "admin.dashboard", LayoutDashboard, null],
    ["Verifikasi registrasi", "admin.verifications.index", UsersRound, "verify registrations"],
    ["Data peserta", "admin.participants.index", UsersRound, "manage participants"],
    ["Kode registrasi", "admin.registration-codes.index", TicketCheck, "manage training master"],
    ["Program pelatihan", "admin.programs.index", GraduationCap, "manage training master"],
    ["Angkatan", "admin.batches.index", CalendarRange, "manage training master"],
    ["Kelas", "admin.classes.index", School, "manage training master"],
    ["Instruktur", "admin.instructors.index", Contact, "manage users"],
    ["Jadwal", "admin.schedules.index", CalendarDays, "manage training master"],
    ["Lokasi absensi", "admin.locations.index", MapPin, "manage training master"],
    ["Hari libur", "admin.holidays.index", CalendarOff, "manage training master"],
    ["Izin dan sakit", "admin.leave-requests.index", ClipboardCheck, "process leave requests"],
    ["Data kehadiran", "admin.attendances.index", ListChecks, "monitor attendances"],
    ["Laporan", "admin.reports.index", BarChart3, "download reports"],
    ["Rekap peserta", "admin.reports.participants", BarChart3, "download reports"],
    ["Import peserta", "admin.imports.participants.index", FileUp, "manage participants"],
    ["Pengaturan", "admin.settings.edit", Settings, "manage settings"],
    ["Manajemen pengguna", "admin.users.index", ShieldCheck, "manage users"],
    ["Activity log", "admin.activity-logs.index", ScrollText, "view activity log"],
    ["Peninjauan fraud", "admin.fraud-flags.index", ShieldAlert, "review fraud flags"],
    ["Pengumuman", "admin.announcements.create", Megaphone, "send announcements"],
    ["Role & permission", "admin.role-permissions.index", KeyRound, "manage users"],
] as const;
function Navigation() {
    const { auth } = usePage<SharedProps>().props;
    const isSuperAdmin = auth.user?.roles.includes("super-admin");
    const visibleItems = items.filter(([, , , permission]) => isSuperAdmin || permission === null || auth.user?.permissions.includes(permission));
    return (
        <nav className="grid min-h-0 flex-1 content-start gap-1 overflow-y-auto overscroll-contain p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
            {visibleItems.map(([label, name, Icon]) => (
                <Link
                    key={name}
                    href={route(name)}
                    className="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium text-blue-100 transition hover:bg-white/10 hover:text-white"
                >
                    <Icon className="size-5" />
                    <span>{label}</span>
                </Link>
            ))}
        </nav>
    );
}
export function AdminLayout({
    children,
    title,
}: {
    children: ReactNode;
    title: string;
}) {
    const [open, setOpen] = useState(false);
    const { auth } = usePage<SharedProps>().props;
    return (
        <div className="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
            <aside className="fixed inset-y-0 hidden h-dvh w-[260px] flex-col overflow-hidden bg-slate-950 lg:flex">
                <div className="border-b border-white/10 p-5">
                    <Brand inverse />
                </div>
                <Navigation />
            </aside>
            <AnimatePresence>
                {open && (
                    <>
                        <motion.button
                            aria-label="Tutup menu"
                            className="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            onClick={() => setOpen(false)}
                        />
                        <motion.aside
                            className="fixed inset-y-0 left-0 z-50 flex h-dvh w-[280px] flex-col overflow-hidden bg-slate-950 lg:hidden"
                            variants={slideSidebar}
                            initial="hidden"
                            animate="visible"
                            exit="exit"
                        >
                            <div className="flex items-center justify-between border-b border-white/10 p-5">
                                <Brand inverse />
                                <button
                                    aria-label="Tutup sidebar"
                                    className="text-white"
                                    onClick={() => setOpen(false)}
                                >
                                    <X />
                                </button>
                            </div>
                            <Navigation />
                        </motion.aside>
                    </>
                )}
            </AnimatePresence>
            <div className="lg:col-start-2">
                <header className="sticky top-0 z-30 flex h-18 items-center justify-between border-b bg-white/95 px-4 backdrop-blur sm:px-6">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="ghost" size="icon" className="relative" aria-label="Notifikasi">
                            <Link href={route("notifications.index")}>
                                <Bell className="size-4" />
                                {!!auth.user?.unread_notifications_count && <span className="absolute right-0 top-0 rounded-full bg-red-500 px-1 text-[10px] text-white">{auth.user.unread_notifications_count}</span>}
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            aria-label="Buka menu"
                            onClick={() => setOpen(true)}
                        >
                            <Menu />
                        </Button>
                        <div>
                            <p className="text-xs text-muted-foreground">
                                Panel Administrator
                            </p>
                            <h2 className="font-semibold">{title}</h2>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="hidden text-right sm:block">
                            <strong className="block text-sm">
                                {auth.user?.name}
                            </strong>
                            <span className="text-xs text-muted-foreground">
                                {auth.user?.roles[0]}
                            </span>
                        </div>
                        <Button
                            variant="outline"
                            aria-label="Keluar"
                            onClick={() => router.post(route("logout"))}
                        >
                            <LogOut className="size-4" />
                            <span>Keluar</span>
                        </Button>
                    </div>
                </header>
                <main className="p-4 sm:p-6 lg:p-8">{children}</main>
            </div>
        </div>
    );
}
