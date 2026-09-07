import { Head, Link, router } from '@inertiajs/react';
import { CalendarClock, KeyRound, Pencil, Plus, Search, TicketCheck, Trash2, UsersRound } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { PageHeader } from '@/components/page-header';
import { PaginationLinks } from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { AdminLayout } from '@/layouts/admin-layout';
import type { Paginated } from '@/types';

interface Code {
    id: number;
    code: string;
    expires_at: string | null;
    usage_limit: number | null;
    usage_count: number;
    is_active: boolean;
    training_batch: { id: number; name: string; training_program: { code: string; name: string } };
}
interface Batch { id: number; name: string; training_program: { code: string; name: string } }
interface Filters { search: string; batch_id: string; status: string }
interface Stats { total: number; active: number; expired: number; exhausted: number }

function codeStatus(code: Code): { label: string; className: string } {
    if (!code.is_active) return { label: 'Nonaktif', className: 'bg-slate-100 text-slate-600' };
    if (code.expires_at && new Date(code.expires_at).getTime() <= Date.now()) return { label: 'Kedaluwarsa', className: 'bg-red-50 text-red-700' };
    if (code.usage_limit !== null && code.usage_count >= code.usage_limit) return { label: 'Kuota habis', className: 'bg-amber-50 text-amber-800' };
    return { label: 'Aktif', className: 'bg-emerald-50 text-emerald-700' };
}

export default function Index({ codes, filters, batches, stats }: { codes: Paginated<Code>; filters: Filters; batches: Batch[]; stats: Stats }) {
    const [search, setSearch] = useState(filters.search);
    const [batchId, setBatchId] = useState(filters.batch_id);
    const [status, setStatus] = useState(filters.status);

    function applyFilters(event: React.FormEvent) {
        event.preventDefault();
        router.get(route('admin.registration-codes.index'), { search, batch_id: batchId, status }, { preserveState: true, replace: true });
    }

    function resetFilters() {
        setSearch('');
        setBatchId('');
        setStatus('');
        router.get(route('admin.registration-codes.index'), {}, { replace: true });
    }

    const summaryCards = [
        { label: 'Total kode', value: stats.total, icon: KeyRound, tone: 'text-primary bg-blue-50' },
        { label: 'Siap digunakan', value: stats.active, icon: TicketCheck, tone: 'text-emerald-700 bg-emerald-50' },
        { label: 'Kedaluwarsa', value: stats.expired, icon: CalendarClock, tone: 'text-red-700 bg-red-50' },
        { label: 'Kuota habis', value: stats.exhausted, icon: UsersRound, tone: 'text-amber-700 bg-amber-50' },
    ];

    return <AdminLayout title="Kode Registrasi">
        <Head title="Kode Registrasi" />
        <PageHeader eyebrow="REGISTRASI PESERTA" title="Kode registrasi" description="Kelola kode undangan per angkatan, masa berlaku, dan batas jumlah pendaftar." action={<Button asChild><Link href={route('admin.registration-codes.create')}><Plus />Buat kode baru</Link></Button>} />

        <div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            {summaryCards.map(({ label, value, icon: Icon, tone }) => <div key={label} className="flex items-center gap-4 rounded-xl border bg-white p-4 shadow-[var(--shadow-card)]"><span className={`grid size-11 shrink-0 place-items-center rounded-xl ${tone}`}><Icon className="size-5" /></span><div><p className="text-xs font-medium text-muted-foreground">{label}</p><strong className="text-2xl">{value}</strong></div></div>)}
        </div>

        <div className="mt-5 overflow-hidden rounded-xl border bg-white shadow-[var(--shadow-card)]">
            <form className="grid gap-3 border-b bg-slate-50/70 p-4 md:grid-cols-[minmax(220px,1fr)_minmax(190px,.75fr)_170px_auto]" onSubmit={applyFilters}>
                <label className="grid gap-1.5 text-xs font-semibold">Cari kode<Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Contoh: TIK-A1" /></label>
                <label className="grid gap-1.5 text-xs font-semibold">Angkatan<select className="h-11 rounded-lg border bg-white px-3 text-sm" value={batchId} onChange={(event) => setBatchId(event.target.value)}><option value="">Semua angkatan</option>{batches.map((batch) => <option key={batch.id} value={batch.id}>{batch.training_program.code} — {batch.name}</option>)}</select></label>
                <label className="grid gap-1.5 text-xs font-semibold">Status<select className="h-11 rounded-lg border bg-white px-3 text-sm" value={status} onChange={(event) => setStatus(event.target.value)}><option value="">Semua status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option><option value="expired">Kedaluwarsa</option><option value="exhausted">Kuota habis</option></select></label>
                <div className="flex items-end gap-2"><Button className="flex-1"><Search />Terapkan</Button><Button type="button" variant="ghost" onClick={resetFilters}>Reset</Button></div>
            </form>

            <div className="overflow-x-auto">
                <table className="w-full min-w-[900px] text-sm">
                    <thead className="bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground"><tr><th className="px-5 py-3">Kode</th><th className="px-5 py-3">Program dan angkatan</th><th className="px-5 py-3">Pemakaian</th><th className="px-5 py-3">Masa berlaku</th><th className="px-5 py-3">Status</th><th className="px-5 py-3 text-right">Tindakan</th></tr></thead>
                    <tbody>{codes.data.length ? codes.data.map((code) => {
                        const operationalStatus = codeStatus(code);
                        const usagePercentage = code.usage_limit ? Math.min(100, (code.usage_count / code.usage_limit) * 100) : 0;
                        return <tr key={code.id} className="border-t align-top hover:bg-slate-50/60">
                            <td className="px-5 py-4"><code className="rounded-md bg-blue-50 px-2 py-1 font-bold tracking-wide text-primary">{code.code}</code></td>
                            <td className="px-5 py-4"><strong className="block">{code.training_batch.training_program.name}</strong><span className="mt-1 block text-xs text-muted-foreground">{code.training_batch.training_program.code} · {code.training_batch.name}</span></td>
                            <td className="px-5 py-4"><strong>{code.usage_count}</strong> dari {code.usage_limit ?? 'tanpa batas'}{code.usage_limit !== null && <div className="mt-2 h-1.5 w-32 overflow-hidden rounded-full bg-slate-100"><div className="h-full rounded-full bg-blue-600" style={{ width: `${usagePercentage}%` }} /></div>}</td>
                            <td className="px-5 py-4">{code.expires_at ? <><span className="block">{new Date(code.expires_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</span><span className="text-xs text-muted-foreground">{new Date(code.expires_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })} WIB</span></> : <span className="text-muted-foreground">Tanpa batas waktu</span>}</td>
                            <td className="px-5 py-4"><Badge className={operationalStatus.className}>{operationalStatus.label}</Badge></td>
                            <td className="px-5 py-4"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="outline"><Link href={route('admin.registration-codes.edit', code.id)} aria-label={`Edit kode ${code.code}`}><Pencil className="size-3" />Edit</Link></Button><ConfirmDialog trigger={<Button size="icon" variant="ghost" className="text-red-600" disabled={code.usage_count > 0} aria-label={`Hapus kode ${code.code}`}><Trash2 /></Button>} title="Hapus kode registrasi" description={code.usage_count > 0 ? 'Kode yang sudah digunakan tidak dapat dihapus. Nonaktifkan kode melalui halaman edit.' : `Kode ${code.code} akan dihapus permanen.`} confirmLabel="Hapus kode" destructive onConfirm={() => router.delete(route('admin.registration-codes.destroy', code.id))} /></div></td>
                        </tr>;
                    }) : <tr><td colSpan={6} className="px-5 py-16 text-center"><TicketCheck className="mx-auto size-9 text-muted-foreground" /><strong className="mt-3 block">Tidak ada kode registrasi</strong><p className="mt-1 text-sm text-muted-foreground">Ubah filter atau buat kode undangan pertama.</p></td></tr>}</tbody>
                </table>
            </div>
            <div className="flex items-center justify-between border-t px-4 py-3 text-xs text-muted-foreground"><span>Menampilkan {codes.data.length} dari {codes.total} kode</span></div>
            <PaginationLinks links={codes.links} />
        </div>
    </AdminLayout>;
}
