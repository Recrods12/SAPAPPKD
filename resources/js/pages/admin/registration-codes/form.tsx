import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarClock, CheckCircle2, Copy, KeyRound, LoaderCircle, RefreshCw, Save, ShieldCheck, TicketCheck, UsersRound } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { AdminLayout } from '@/layouts/admin-layout';

interface Batch {
    id: number;
    name: string;
    training_program: { code: string; name: string };
}

interface RegistrationCode {
    id: number;
    training_batch_id: number;
    code: string;
    expires_at: string | null;
    usage_limit: number | null;
    usage_count: number;
    is_active: boolean;
}

function randomSuffix(): string {
    const bytes = new Uint8Array(3);
    crypto.getRandomValues(bytes);

    return Array.from(bytes, (byte) => byte.toString(36).padStart(2, '0')).join('').slice(0, 6).toUpperCase();
}

export default function Form({ registrationCode, batches }: { registrationCode: RegistrationCode | null; batches: Batch[] }) {
    const [copied, setCopied] = useState(false);
    const form = useForm({
        training_batch_id: String(registrationCode?.training_batch_id ?? ''),
        code: registrationCode?.code ?? '',
        expires_at: registrationCode?.expires_at?.slice(0, 16) ?? '',
        usage_limit: String(registrationCode?.usage_limit ?? ''),
        is_active: registrationCode?.is_active ?? true,
    });
    const selectedBatch = useMemo(() => batches.find((batch) => String(batch.id) === form.data.training_batch_id), [batches, form.data.training_batch_id]);
    const remainingUses = registrationCode?.usage_limit === null
        ? null
        : Math.max(0, (registrationCode?.usage_limit ?? Number(form.data.usage_limit || 0)) - (registrationCode?.usage_count ?? 0));

    function generateCode() {
        const programCode = selectedBatch?.training_program.code.replace(/[^A-Z0-9]/gi, '').toUpperCase() || 'PPKD';
        const batchCode = selectedBatch?.name.replace(/[^A-Z0-9]/gi, '').toUpperCase().slice(0, 8) || new Date().getFullYear().toString();
        form.setData('code', `${programCode}-${batchCode}-${randomSuffix()}`);
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();
        registrationCode
            ? form.put(route('admin.registration-codes.update', registrationCode.id))
            : form.post(route('admin.registration-codes.store'));
    }

    async function copyCode() {
        if (!form.data.code) return;
        await navigator.clipboard.writeText(form.data.code);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 1500);
    }

    const error = (key: keyof typeof form.data) => form.errors[key] && <span role="alert" className="text-xs font-medium text-red-600">{form.errors[key]}</span>;

    return <AdminLayout title="Kode Registrasi">
        <Head title={registrationCode ? 'Edit Kode Registrasi' : 'Buat Kode Registrasi'} />
        <PageHeader
            eyebrow="AKSES PENDAFTARAN"
            title={registrationCode ? 'Edit kode registrasi' : 'Buat kode registrasi baru'}
            description="Atur kode undangan khusus agar peserta hanya dapat mendaftar ke angkatan yang benar."
            action={<Button asChild variant="outline"><Link href={route('admin.registration-codes.index')}><ArrowLeft />Kembali ke daftar</Link></Button>}
        />

        {batches.length === 0 && <div role="alert" className="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Belum ada angkatan berstatus dibuka. Buat atau buka angkatan terlebih dahulu sebelum membuat kode registrasi.</div>}

        <div className="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,.75fr)]">
            <Card>
                <CardContent className="p-6 sm:p-8">
                    <div className="flex items-start gap-3 border-b pb-6">
                        <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-primary"><KeyRound className="size-5" /></span>
                        <div><h2 className="text-lg font-bold">Detail kode undangan</h2><p className="mt-1 text-sm leading-6 text-muted-foreground">Semua field dapat diperbarui selama kode masih dikelola oleh admin.</p></div>
                    </div>

                    <form className="grid gap-6 pt-6" onSubmit={submit}>
                        <label className="grid gap-2 text-sm font-semibold">
                            Angkatan tujuan <span className="font-normal text-red-600">*</span>
                            <select className="h-12 w-full rounded-lg border bg-white px-3 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-100" value={form.data.training_batch_id} onChange={(event) => form.setData('training_batch_id', event.target.value)} required disabled={batches.length === 0}>
                                <option value="">Pilih program dan angkatan</option>
                                {batches.map((batch) => <option key={batch.id} value={batch.id}>{batch.training_program.code} — {batch.training_program.name} / {batch.name}</option>)}
                            </select>
                            <span className="text-xs font-normal text-muted-foreground">Kode hanya berlaku untuk pendaftaran pada angkatan ini.</span>
                            {error('training_batch_id')}
                        </label>

                        <label className="grid gap-2 text-sm font-semibold">
                            Kode registrasi <span className="font-normal text-red-600">*</span>
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Input className="h-12 font-mono uppercase tracking-wide" value={form.data.code} onChange={(event) => form.setData('code', event.target.value.toUpperCase())} placeholder="Contoh: TIK-A1-X7K9P2" maxLength={50} autoComplete="off" required />
                                <Button type="button" variant="outline" className="h-12 shrink-0" onClick={generateCode}><RefreshCw />Buat otomatis</Button>
                            </div>
                            <span className="text-xs font-normal text-muted-foreground">Gunakan huruf, angka, tanda hubung, atau garis bawah. Maksimal 50 karakter.</span>
                            {error('code')}
                        </label>

                        <div className="grid gap-6 md:grid-cols-2">
                            <label className="grid gap-2 text-sm font-semibold">
                                Berlaku sampai
                                <Input className="h-12" type="datetime-local" value={form.data.expires_at} onChange={(event) => form.setData('expires_at', event.target.value)} />
                                <span className="text-xs font-normal text-muted-foreground">Kosongkan bila tidak memiliki tanggal kedaluwarsa.</span>
                                {error('expires_at')}
                            </label>
                            <label className="grid gap-2 text-sm font-semibold">
                                Maksimum penggunaan
                                <Input className="h-12" type="number" min="1" max="10000" value={form.data.usage_limit} onChange={(event) => form.setData('usage_limit', event.target.value)} placeholder="Contoh: 30" />
                                <span className="text-xs font-normal text-muted-foreground">Kosongkan bila jumlah penggunaan tidak dibatasi.</span>
                                {error('usage_limit')}
                            </label>
                        </div>

                        <label className="flex min-h-14 cursor-pointer items-start gap-3 rounded-xl border bg-slate-50 p-4">
                            <input className="mt-1 size-4 accent-blue-600" type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} />
                            <span><strong className="block text-sm">Aktifkan kode registrasi</strong><span className="mt-1 block text-xs leading-5 text-muted-foreground">Kode nonaktif tetap tersimpan tetapi langsung ditolak pada formulir pendaftaran peserta.</span></span>
                        </label>

                        <div className="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:justify-end">
                            <Button asChild type="button" variant="ghost"><Link href={route('admin.registration-codes.index')}>Batal</Link></Button>
                            <Button className="min-w-40" disabled={form.processing || batches.length === 0}>{form.processing ? <LoaderCircle className="animate-spin" /> : <Save />} {form.processing ? 'Menyimpan…' : 'Simpan kode'}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <div className="grid gap-4 xl:sticky xl:top-24">
                <Card className="overflow-hidden">
                    <div className="bg-slate-950 p-5 text-white"><p className="text-xs font-bold tracking-[.16em] text-blue-300">PREVIEW KODE</p><div className="mt-3 flex items-center gap-2"><code className="min-w-0 flex-1 truncate text-lg font-bold tracking-wide">{form.data.code || 'KODE-BELUM-DIISI'}</code><Button type="button" size="icon" variant="ghost" className="shrink-0 text-white hover:bg-white/10" onClick={copyCode} disabled={!form.data.code} aria-label="Salin kode registrasi"><Copy /></Button></div>{copied && <p role="status" className="mt-2 text-xs text-emerald-300">Kode berhasil disalin.</p>}</div>
                    <CardContent className="grid gap-4 p-5">
                        <div className="flex items-start gap-3"><TicketCheck className="mt-0.5 size-5 shrink-0 text-primary" /><div><p className="text-xs text-muted-foreground">Angkatan tujuan</p><strong className="text-sm">{selectedBatch ? `${selectedBatch.training_program.name} / ${selectedBatch.name}` : 'Belum dipilih'}</strong></div></div>
                        <div className="flex items-start gap-3"><CalendarClock className="mt-0.5 size-5 shrink-0 text-primary" /><div><p className="text-xs text-muted-foreground">Masa berlaku</p><strong className="text-sm">{form.data.expires_at ? new Date(form.data.expires_at).toLocaleString('id-ID') : 'Tanpa batas waktu'}</strong></div></div>
                        <div className="flex items-start gap-3"><UsersRound className="mt-0.5 size-5 shrink-0 text-primary" /><div><p className="text-xs text-muted-foreground">Kuota penggunaan</p><strong className="text-sm">{form.data.usage_limit ? `${form.data.usage_limit} pendaftaran` : 'Tanpa batas penggunaan'}</strong>{registrationCode && <p className="mt-1 text-xs text-muted-foreground">Sudah dipakai {registrationCode.usage_count} kali{remainingUses !== null ? ` · tersisa ${remainingUses}` : ''}.</p>}</div></div>
                        <div className="flex items-center justify-between border-t pt-4"><span className="text-sm font-medium">Status kode</span><Badge className={form.data.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}>{form.data.is_active ? <><CheckCircle2 className="mr-1 size-3" />Aktif</> : 'Nonaktif'}</Badge></div>
                    </CardContent>
                </Card>
                <div className="rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-950"><div className="flex gap-3"><ShieldCheck className="size-5 shrink-0 text-blue-700" /><div><strong>Kontrol pendaftaran</strong><p className="mt-1 leading-6 text-blue-800">Peserta tetap harus mengisi identitas lengkap. Kode ini hanya membuka akses ke angkatan terkait dan tidak otomatis mengaktifkan akun.</p></div></div></div>
            </div>
        </div>
    </AdminLayout>;
}
