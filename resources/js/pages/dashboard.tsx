import { Head, Link } from "@inertiajs/react";
import {
    CalendarDays,
    Clock3,
    History,
    Home,
    MapPinCheck,
    UserRound,
    Bell,
} from "lucide-react";
import { PageTransition } from "@/components/page-transition";
import { ParticipantQuickNav } from "@/components/participant-quick-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";

interface Props {
    participant: {
        name: string;
        profile?: { participant_number: string } | null;
        program?: string | null;
        batch?: string | null;
        class?: string | null;
    };
    stats: { present: number; late:number; leave:number; sick:number; absent:number; incomplete:number; holiday:number; attendanceRate: number };
    todaySchedule?: {subject:string|null;start_time:string;end_time:string}|null;
    todayAttendances: {type:string;status:string;recorded_at:string}[];
    recentAttendances: {id:number;type:string;status:string;recorded_at:string;training_schedule:{subject:string|null};attendance_location:{name:string}}[];
}

export default function Dashboard({ participant, stats, todaySchedule, todayAttendances, recentAttendances }: Props) {
    return (
        <>
            <Head title="Dashboard Peserta" />
            <div className="min-h-screen pb-24">
                <header className="bg-slate-950 px-5 pb-16 pt-8 text-white">
                    <div className="mx-auto max-w-5xl relative">
                        <div className="mb-5 flex flex-wrap items-center justify-end gap-1 sm:absolute sm:right-0 sm:top-0 sm:mb-0"><Button asChild aria-label="Notifikasi" variant="ghost" size="icon" className="text-white"><Link href={route("notifications.index")}><Bell/></Link></Button><ParticipantQuickNav /></div>
                        <p className="text-sm text-blue-200">Selamat datang,</p>
                        <h1 className="mt-1 text-3xl font-bold">
                            {participant.name}
                        </h1>
                        <p className="mt-2 text-sm text-blue-100/60">
                            {participant.profile?.participant_number ??
                                "Nomor peserta belum tersedia"}
                        </p>
                        <p className="mt-1 text-sm text-blue-100/80">{[participant.program,participant.batch,participant.class].filter(Boolean).join(" · ")}</p>
                    </div>
                </header>
                <PageTransition>
                    <main className="mx-auto -mt-9 grid max-w-5xl gap-5 px-5">
                        <Card>
                            <CardContent>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Status hari ini
                                        </p>
                                        <h2 className="mt-1 text-xl font-bold">
                                            {todaySchedule?.subject || "Belum ada jadwal aktif"}
                                        </h2>
                                    </div>
                                    <CalendarDays className="size-7 text-primary" />
                                </div>
                                {todaySchedule&&<p className="mt-3 text-sm text-muted-foreground">{todaySchedule.start_time}–{todaySchedule.end_time} · Pagi: {todayAttendances.some(a=>a.type==="morning")?"sudah":"belum"} · Sore: {todayAttendances.some(a=>a.type==="afternoon")?"sudah":"belum"}</p>}
                                <Button
                                    className="mt-5 w-full"
                                    size="lg"
                                    asChild
                                >
                                    <Link href={route("attendance.create")}>
                                        <MapPinCheck />
                                        Absen sekarang
                                    </Link>
                                </Button>
                                <p className="mt-2 text-center text-xs text-muted-foreground">
                                    GPS, kamera, dan waktu server akan
                                    diverifikasi.
                                </p>
                            </CardContent>
                        </Card>
                        <div className="grid grid-cols-2 gap-3">
                            <Card>
                                <CardContent>
                                    <Clock3 className="size-5 text-emerald-600" />
                                    <strong className="mt-3 block text-2xl">
                                        {stats.present}
                                    </strong>
                                    <span className="text-xs text-muted-foreground">
                                        Total hadir
                                    </span>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent>
                                    <History className="size-5 text-amber-600" />
                                    <strong className="mt-3 block text-2xl">
                                        {stats.attendanceRate}%
                                    </strong>
                                    <span className="text-xs text-muted-foreground">
                                        Kehadiran
                                    </span>
                                </CardContent>
                            </Card>
                        </div>
                        <div className="grid grid-cols-3 gap-3 sm:grid-cols-6">{[["Terlambat",stats.late],["Izin",stats.leave],["Sakit",stats.sick],["Alpa",stats.absent],["Belum lengkap",stats.incomplete],["Kehadiran",`${stats.attendanceRate}%`]].map(([label,value])=><Card key={String(label)}><CardContent className="p-4"><strong className="block text-xl">{value}</strong><span className="text-xs text-muted-foreground">{label}</span></CardContent></Card>)}</div>
                        <Card><CardContent><div className="flex items-center justify-between"><h2 className="font-bold">Riwayat terbaru</h2><Button asChild size="sm" variant="outline"><Link href={route("attendance.history")}>Semua</Link></Button></div><div className="mt-3 divide-y">{recentAttendances.map(item=><div key={item.id} className="flex justify-between gap-3 py-3 text-sm"><div><strong>{item.training_schedule.subject||"Pelatihan"}</strong><p className="text-muted-foreground">{item.attendance_location.name}</p></div><div className="text-right"><span>{item.type==="morning"?"Pagi":"Sore"}</span><p className="text-muted-foreground">{new Date(item.recorded_at).toLocaleDateString("id-ID")}</p></div></div>)}{recentAttendances.length===0&&<p className="py-5 text-center text-sm text-muted-foreground">Belum ada riwayat.</p>}</div><Button asChild variant="outline" className="mt-4 w-full"><Link href={route("leave-requests.index")}>Ajukan izin atau sakit</Link></Button></CardContent></Card>
                    </main>
                </PageTransition>
                <nav className="fixed inset-x-0 bottom-0 z-30 border-t bg-white/95 px-3 pb-[max(.75rem,env(safe-area-inset-bottom))] pt-2 backdrop-blur">
                    <div className="mx-auto flex max-w-md justify-around">
                        {[
                            [Home, "Beranda"],
                            [MapPinCheck, "Absen"],
                            [CalendarDays, "Jadwal"],
                            [History, "Riwayat"],
                            [UserRound, "Profil"],
                        ].map(([Icon, label]) => {
                            const href = label === "Beranda" ? route("dashboard") : label === "Absen" ? route("attendance.create") : label === "Jadwal" ? route("schedule.index") : label === "Riwayat" ? route("attendance.history") : label === "Profil" ? route("profile.edit") : null;
                            const content = <><Icon className="size-5" />{String(label)}</>;
                            return href ? <Link
                                key={String(label)}
                                className={`grid min-w-14 place-items-center gap-1 py-1 text-[11px] ${label === "Beranda" ? "font-semibold text-primary" : "text-muted-foreground"}`}
                                href={href}
                            >
                                {content}
                            </Link> : <span key={String(label)} className="grid min-w-14 place-items-center gap-1 py-1 text-[11px] text-muted-foreground opacity-50">{content}</span>;
                        })}
                    </div>
                </nav>
            </div>
        </>
    );
}
