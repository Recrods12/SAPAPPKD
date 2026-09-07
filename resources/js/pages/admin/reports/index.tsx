import { Head, Link, router } from '@inertiajs/react';
import { Download, FileText, Printer, Search, UsersRound } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { AdminLayout } from '@/layouts/admin-layout';

interface Option { id: number; name: string }
interface Row { id: number; attendance_date: string; type: string; status: string; recorded_at: string; user: { name: string; participant_profile?: { participant_number: string } | null }; training_schedule: { training_class: { name: string } } }
interface ParticipantSummary { id: number; name: string; participant_number: string | null; present: number; late: number; leave: number; sick: number; absent: number; incomplete: number; attendanceRate: number }
interface PageLink { url: string | null; label: string; active: boolean }
interface Paginator<T> { data: T[]; links: PageLink[] }
interface Summary { total: number; onTime: number; late: number; incomplete: number; leave: number; sick: number; fraud: number }
interface Props { rows: Paginator<Row>; participantSummaries: Paginator<ParticipantSummary>; summary: Summary; programs: Option[]; batches: Option[]; classes: Option[]; participants: Option[]; instructors: Option[]; filters: Record<string, string> }

function Select({ name, label, items, value }: { name: string; label: string; items: Option[]; value?: string }) {
    return <label className="grid gap-1 text-xs font-medium text-muted-foreground">{label}<select name={name} defaultValue={value} className="h-11 rounded-md border bg-white px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary"><option value="">Semua</option>{items.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>;
}

function Pagination({ links, label }: { links: PageLink[]; label: string }) {
    if (links.length <= 3) return null;
    return <nav className="flex flex-wrap gap-2 border-t p-4 print:hidden" aria-label={label}>{links.map((link, index) => <Button key={index} asChild={Boolean(link.url)} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url}>{link.url ? <Link href={link.url} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} /> : <span dangerouslySetInnerHTML={{ __html: link.label }} />}</Button>)}</nav>;
}

export default function Reports({ rows, participantSummaries, summary, programs, batches, classes, participants, instructors, filters }: Props) {
    function submit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.get(route('admin.reports.index'), Object.fromEntries(new FormData(event.currentTarget) as never), { preserveState: true });
    }
    const query = new URLSearchParams(Object.entries(filters).filter(([, value]) => value) as [string, string][]).toString();

    return <AdminLayout title="Laporan">
        <Head title="Laporan Kehadiran" />
        <PageHeader eyebrow="LAPORAN" title="Rekap kehadiran" description="Rekap harian, mingguan, bulanan, atau rentang khusus. Excel memuat 8 sheet termasuk peserta, kelas, angkatan, program, izin-sakit, serta fraud." />
        <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-7"><StatCard label="Catatan" value={summary.total} icon={FileText} /><StatCard label="Tepat waktu" value={summary.onTime} icon={FileText} tone="green" /><StatCard label="Terlambat" value={summary.late} icon={FileText} tone="amber" /><StatCard label="Tidak lengkap" value={summary.incomplete} icon={FileText} tone="red" /><StatCard label="Izin" value={summary.leave} icon={FileText} /><StatCard label="Sakit" value={summary.sick} icon={FileText} /><StatCard label="Fraud terbuka" value={summary.fraud} icon={FileText} tone="red" /></div>
        <form onSubmit={submit} className="mt-5 grid gap-3 rounded-xl border bg-white p-4 sm:grid-cols-2 xl:grid-cols-4">
            <label className="grid gap-1 text-xs font-medium text-muted-foreground">Tanggal awal<Input name="start_date" type="date" defaultValue={filters.start_date} /></label>
            <label className="grid gap-1 text-xs font-medium text-muted-foreground">Tanggal akhir<Input name="end_date" type="date" defaultValue={filters.end_date} /></label>
            <label className="grid gap-1 text-xs font-medium text-muted-foreground">Bulan<Input name="month" type="number" min="1" max="12" defaultValue={filters.month} /></label>
            <label className="grid gap-1 text-xs font-medium text-muted-foreground">Tahun<Input name="year" type="number" min="2020" max="2100" defaultValue={filters.year} /></label>
            <Select name="program_id" label="Program" items={programs} value={filters.program_id} /><Select name="batch_id" label="Angkatan" items={batches} value={filters.batch_id} /><Select name="class_id" label="Kelas" items={classes} value={filters.class_id} /><Select name="participant_id" label="Peserta" items={participants} value={filters.participant_id} /><Select name="instructor_id" label="Instruktur" items={instructors} value={filters.instructor_id} />
            <label className="grid gap-1 text-xs font-medium text-muted-foreground">Status catatan<select name="status" defaultValue={filters.status} className="h-11 rounded-md border bg-white px-3 text-sm"><option value="">Semua</option><option value="on_time">Tepat waktu</option><option value="late">Terlambat</option><option value="present">Pulang sesuai jadwal</option><option value="early_leave">Pulang cepat</option><option value="needs_verification">Perlu verifikasi</option><option value="outside_schedule">Di luar jadwal</option></select></label>
            <label className="grid gap-1 text-xs font-medium text-muted-foreground">Status akhir<select name="final_status" defaultValue={filters.final_status} className="h-11 rounded-md border bg-white px-3 text-sm"><option value="">Semua</option><option value="present">Hadir</option><option value="late">Terlambat</option><option value="leave">Izin</option><option value="sick">Sakit</option><option value="absent">Alpa</option><option value="incomplete">Tidak lengkap</option><option value="holiday">Hari libur</option></select></label>
            <Button className="xl:col-span-4"><Search />Terapkan filter laporan</Button>
        </form>
        <div className="mt-4 flex flex-wrap gap-2 print:hidden"><Button asChild><a href={`${route('admin.reports.excel')}?${query}`}><Download />Excel lengkap (8 sheet)</a></Button><Button asChild variant="outline"><a href={`${route('admin.reports.pdf')}?${query}`}><FileText />PDF lengkap</a></Button><Button variant="outline" onClick={() => window.print()}><Printer />Cetak halaman</Button><Button asChild variant="outline"><Link href={route('admin.reports.participants')}><UsersRound />Rekap peserta terperinci</Link></Button></div>

        <section className="mt-6 overflow-hidden rounded-xl border bg-white"><h2 className="border-b p-4 text-lg font-bold">Catatan absensi pagi dan sore</h2><div className="overflow-x-auto"><table className="w-full min-w-[700px] text-sm"><thead className="bg-muted/60 text-left"><tr><th className="p-4">Tanggal</th><th className="p-4">Peserta</th><th className="p-4">Kelas</th><th className="p-4">Sesi</th><th className="p-4">Status</th><th className="p-4">Waktu</th></tr></thead><tbody>{rows.data.map((row) => <tr className="border-t" key={row.id}><td className="p-4">{row.attendance_date}</td><td className="p-4">{row.user.name}<small className="block">{row.user.participant_profile?.participant_number}</small></td><td className="p-4">{row.training_schedule.training_class.name}</td><td className="p-4">{row.type === 'morning' ? 'Pagi' : 'Sore'}</td><td className="p-4">{row.status}</td><td className="p-4">{new Date(row.recorded_at).toLocaleTimeString('id-ID')}</td></tr>)}{rows.data.length === 0 && <tr><td colSpan={6} className="p-12 text-center text-muted-foreground">Tidak ada data pada filter ini.</td></tr>}</tbody></table></div><Pagination links={rows.links} label="Paginasi catatan absensi" /></section>
        <section className="mt-6 overflow-hidden rounded-xl border bg-white"><h2 className="border-b p-4 text-lg font-bold">Ringkasan per peserta</h2><div className="overflow-x-auto"><table className="w-full min-w-[850px] text-sm"><thead className="bg-muted/60"><tr><th className="p-4 text-left">Peserta</th><th className="p-4">Hadir</th><th className="p-4">Terlambat</th><th className="p-4">Izin</th><th className="p-4">Sakit</th><th className="p-4">Alpa</th><th className="p-4">Tidak lengkap</th><th className="p-4">Kehadiran</th></tr></thead><tbody>{participantSummaries.data.map((row) => <tr key={row.id} className="border-t"><td className="p-4"><strong>{row.name}</strong><small className="block">{row.participant_number}</small></td><td className="p-4 text-center">{row.present}</td><td className="p-4 text-center">{row.late}</td><td className="p-4 text-center">{row.leave}</td><td className="p-4 text-center">{row.sick}</td><td className="p-4 text-center">{row.absent}</td><td className="p-4 text-center">{row.incomplete}</td><td className="p-4 text-center font-bold">{row.attendanceRate}%</td></tr>)}{participantSummaries.data.length === 0 && <tr><td colSpan={8} className="p-12 text-center text-muted-foreground">Tidak ada peserta pada filter ini.</td></tr>}</tbody></table></div><Pagination links={participantSummaries.links} label="Paginasi ringkasan peserta" /></section>
    </AdminLayout>;
}
