import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, Camera, CalendarDays, MapPin } from "lucide-react";
import { Button } from "@/components/ui/button";

interface Attendance {
    id: number;
    attendance_date: string;
    type: "morning" | "afternoon";
    status: string;
    recorded_at: string;
    distance_meters: string;
    training_schedule: { subject: string | null };
    attendance_location: { name: string };
    photo: { id: number } | null;
}

interface PageLink { url: string | null; label: string; active: boolean }
interface Props { attendances: { data: Attendance[]; links: PageLink[]; last_page: number } }

const statusLabel: Record<string, string> = { on_time: "Tepat waktu", late: "Terlambat", present: "Hadir", early_leave: "Pulang cepat" };

export default function AttendanceHistory({ attendances }: Props) {
    return (
        <main className="min-h-screen bg-slate-50 pb-12">
            <Head title="Riwayat Presensi" />
            <header className="bg-slate-950 px-4 py-7 text-white">
                <div className="mx-auto max-w-3xl">
                    <Button asChild variant="ghost" className="mb-3 -ml-3 text-white hover:text-white">
                        <Link href={route("dashboard")}><ArrowLeft />Kembali</Link>
                    </Button>
                    <p className="text-sm text-blue-200">Catatan kehadiran</p>
                    <h1 className="text-2xl font-bold">Riwayat presensi</h1>
                </div>
            </header>
            <section className="mx-auto grid max-w-3xl gap-3 p-4">
                {attendances.data.length === 0 && <div className="rounded-2xl border bg-white p-10 text-center text-muted-foreground">Belum ada presensi tercatat.</div>}
                {attendances.data.map((item) => (
                    <article key={item.id} className="rounded-2xl border bg-white p-4 shadow-sm">
                        <div className="flex items-start justify-between gap-3">
                            <div><p className="font-bold">{item.training_schedule.subject || "Pelatihan"}</p><p className="mt-1 flex items-center gap-1 text-sm text-muted-foreground"><CalendarDays className="size-4" />{new Date(item.recorded_at).toLocaleString("id-ID")}</p></div>
                            <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{item.type === "morning" ? "Pagi" : "Sore"}</span>
                        </div>
                        <div className="mt-4 flex flex-wrap items-center gap-3 text-sm"><span className="font-semibold text-emerald-700">{statusLabel[item.status] || item.status}</span><span className="flex items-center gap-1 text-muted-foreground"><MapPin className="size-4" />{item.attendance_location.name} · {item.distance_meters} m</span>{item.photo && <Button asChild size="sm" variant="outline" className="ml-auto"><a href={route("attendance-photos.show", item.photo.id)} target="_blank" rel="noreferrer"><Camera />Lihat foto</a></Button>}</div>
                    </article>
                ))}
                {attendances.last_page > 1 && <div className="flex flex-wrap gap-2 pt-3">{attendances.links.map((link, index) => link.url ? <Button key={index} asChild size="sm" variant={link.active ? "default" : "outline"}><Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} /></Button> : <Button key={index} size="sm" variant="outline" disabled dangerouslySetInnerHTML={{ __html: link.label }} />)}</div>}
            </section>
        </main>
    );
}
