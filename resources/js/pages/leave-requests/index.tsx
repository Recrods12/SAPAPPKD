import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, FileText, LoaderCircle, Send } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

interface Item {
    id: number;
    type: "leave" | "sick";
    start_date: string;
    end_date: string;
    reason: string;
    status: string;
    has_document: boolean;
    admin_notes: string | null;
}
interface Props {
    leaveRequests: { data: Item[] };
}

export default function LeaveRequests({ leaveRequests }: Props) {
    const form = useForm({
        type: "leave",
        start_date: "",
        end_date: "",
        reason: "",
        document: null as File | null,
    });
    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(route("leave-requests.store"), {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    }
    return (
        <main className="min-h-screen bg-slate-50 pb-12">
            <Head title="Izin dan Sakit" />
            <header className="bg-slate-950 px-4 py-7 text-white">
                <div className="mx-auto max-w-3xl">
                    <Button
                        asChild
                        variant="ghost"
                        className="mb-3 -ml-3 text-white hover:text-white"
                    >
                        <Link href={route("dashboard")}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    <p className="text-sm text-blue-200">
                        Administrasi peserta
                    </p>
                    <h1 className="text-2xl font-bold">Izin dan sakit</h1>
                </div>
            </header>
            <div className="mx-auto grid max-w-3xl gap-5 p-4">
                <form
                    onSubmit={submit}
                    className="grid gap-4 rounded-2xl border bg-white p-5 shadow-sm"
                >
                    <h2 className="font-bold">Buat pengajuan</h2>
                    <label className="grid gap-1 text-sm font-medium">
                        Jenis
                        <select
                            className="h-11 rounded-md border bg-white px-3"
                            value={form.data.type}
                            onChange={(e) =>
                                form.setData("type", e.target.value)
                            }
                        >
                            <option value="leave">Izin</option>
                            <option value="sick">Sakit</option>
                        </select>
                        {form.errors.type && (
                            <span className="text-red-600">
                                {form.errors.type}
                            </span>
                        )}
                    </label>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <label className="grid gap-1 text-sm font-medium">
                            Tanggal mulai
                            <Input
                                type="date"
                                value={form.data.start_date}
                                onChange={(e) =>
                                    form.setData("start_date", e.target.value)
                                }
                            />
                            {form.errors.start_date && (
                                <span className="text-red-600">
                                    {form.errors.start_date}
                                </span>
                            )}
                        </label>
                        <label className="grid gap-1 text-sm font-medium">
                            Tanggal selesai
                            <Input
                                type="date"
                                value={form.data.end_date}
                                onChange={(e) =>
                                    form.setData("end_date", e.target.value)
                                }
                            />
                            {form.errors.end_date && (
                                <span className="text-red-600">
                                    {form.errors.end_date}
                                </span>
                            )}
                        </label>
                    </div>
                    <label className="grid gap-1 text-sm font-medium">
                        Alasan
                        <textarea
                            className="min-h-28 rounded-md border p-3"
                            value={form.data.reason}
                            onChange={(e) =>
                                form.setData("reason", e.target.value)
                            }
                        />
                        {form.errors.reason && (
                            <span className="text-red-600">
                                {form.errors.reason}
                            </span>
                        )}
                    </label>
                    <label className="grid gap-1 text-sm font-medium">
                        Dokumen pendukung (PDF/JPG/PNG, maks. 3 MB)
                        <Input
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            onChange={(e) =>
                                form.setData(
                                    "document",
                                    e.target.files?.[0] ?? null,
                                )
                            }
                        />
                        {form.errors.document && (
                            <span className="text-red-600">
                                {form.errors.document}
                            </span>
                        )}
                    </label>
                    <Button disabled={form.processing}>
                        {form.processing ? (
                            <LoaderCircle className="animate-spin" />
                        ) : (
                            <Send />
                        )}
                        Kirim pengajuan
                    </Button>
                </form>
                <section className="grid gap-3">
                    <h2 className="font-bold">Riwayat pengajuan</h2>
                    {leaveRequests.data.length === 0 && (
                        <div className="rounded-2xl border bg-white p-8 text-center text-muted-foreground">
                            Belum ada pengajuan.
                        </div>
                    )}
                    {leaveRequests.data.map((item) => (
                        <article
                            key={item.id}
                            className="rounded-2xl border bg-white p-4"
                        >
                            <div className="flex justify-between">
                                <strong>
                                    {item.type === "leave" ? "Izin" : "Sakit"}
                                </strong>
                                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">
                                    {item.status === "pending"
                                        ? "Menunggu"
                                        : item.status === "approved"
                                          ? "Disetujui"
                                          : "Ditolak"}
                                </span>
                            </div>
                            <p className="mt-2 text-sm">
                                {item.start_date} s.d. {item.end_date}
                            </p>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {item.reason}
                            </p>
                            {item.admin_notes && (
                                <p className="mt-3 rounded-lg bg-slate-50 p-3 text-sm">
                                    Catatan admin: {item.admin_notes}
                                </p>
                            )}
                            {item.has_document && (
                                <Button
                                    asChild
                                    size="sm"
                                    variant="outline"
                                    className="mt-3"
                                >
                                    <a
                                        href={route(
                                            "leave-requests.document",
                                            item.id,
                                        )}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <FileText />
                                        Lihat dokumen
                                    </a>
                                </Button>
                            )}
                        </article>
                    ))}
                </section>
            </div>
        </main>
    );
}
