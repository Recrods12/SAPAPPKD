import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { Archive, CalendarRange, Pencil, Plus, Search } from "lucide-react";
import { toast } from "sonner";
import { PageHeader } from "@/components/page-header";
import { DataSortControls } from "@/components/data-sort-controls";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { PaginationLinks } from "@/components/pagination-links";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AdminLayout } from "@/layouts/admin-layout";
import type { Paginated } from "@/types";

interface Batch {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    quota: number;
    status: string;
    training_classes_count: number;
    training_program: { code: string; name: string };
}
const labels: Record<string, string> = {
    draft: "Draf",
    open: "Dibuka",
    closed: "Ditutup",
    completed: "Selesai",
};

export default function Index({
    batches,
    search: initial,
    status: initialStatus,
    sort,
    direction,
}: {
    batches: Paginated<Batch>;
    search: string;
    status: string;
    sort: string;
    direction: string;
}) {
    const [search, setSearch] = useState(initial);
    const [status, setStatus] = useState(initialStatus);
    function filter(e: React.FormEvent) {
        e.preventDefault();
        router.get(
            route("admin.batches.index"),
            { search, status },
            { preserveState: true, replace: true },
        );
    }
    function archive(batch: Batch) {
        router.delete(route("admin.batches.destroy", batch.id), {
            onSuccess: () => toast.success("Angkatan berhasil diarsipkan"),
        });
    }
    return (
        <AdminLayout title="Angkatan">
            <Head title="Angkatan" />
            <PageHeader
                eyebrow="DATA MASTER"
                title="Angkatan pelatihan"
                description="Atur periode, kuota, dan status penerimaan peserta."
                action={
                    <Button asChild>
                        <Link href={route("admin.batches.create")}>
                            <Plus />
                            Tambah angkatan
                        </Link>
                    </Button>
                }
            />
            <div className="mt-6 overflow-hidden rounded-xl border bg-white shadow-[var(--shadow-card)]">
                <form
                    onSubmit={filter}
                    className="grid gap-2 border-b p-4 sm:grid-cols-[1fr_180px_auto]"
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari angkatan atau program…"
                    />
                    <select
                        className="h-11 rounded-lg border bg-white px-3 text-sm"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    >
                        <option value="">Semua status</option>
                        {Object.entries(labels).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                    <Button variant="outline">
                        <Search className="size-4" />
                        Filter
                    </Button>
                </form>
                <DataSortControls
                    routeName="admin.batches.index"
                    sort={sort}
                    direction={direction}
                    options={[
                        { value: "start_date", label: "Tanggal mulai" },
                        { value: "end_date", label: "Tanggal selesai" },
                        { value: "name", label: "Nama" },
                        { value: "quota", label: "Kuota" },
                        { value: "status", label: "Status" },
                    ]}
                />
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[840px] text-sm">
                        <thead className="bg-muted/60 text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="px-5 py-3">Angkatan</th>
                                <th className="px-5 py-3">Periode</th>
                                <th className="px-5 py-3">Kuota</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3 text-right">
                                    Tindakan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {batches.data.length ? (
                                batches.data.map((b) => (
                                    <tr key={b.id} className="border-t">
                                        <td className="px-5 py-4">
                                            <Link
                                                className="font-semibold text-primary hover:underline"
                                                href={route(
                                                    "admin.batches.show",
                                                    b.id,
                                                )}
                                            >
                                                {b.name}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                {b.training_program.code} ·{" "}
                                                {b.training_program.name}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {new Date(
                                                b.start_date,
                                            ).toLocaleDateString("id-ID")}{" "}
                                            –{" "}
                                            {new Date(
                                                b.end_date,
                                            ).toLocaleDateString("id-ID")}
                                        </td>
                                        <td className="px-5 py-4">
                                            {b.quota} peserta ·{" "}
                                            {b.training_classes_count} kelas
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge>
                                                {labels[b.status] ?? b.status}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Link
                                                        href={route(
                                                            "admin.batches.edit",
                                                            b.id,
                                                        )}
                                                    >
                                                        <Pencil className="size-3" />
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-red-600"
                                                        >
                                                            <Archive className="size-3" />
                                                            Arsip
                                                        </Button>
                                                    }
                                                    title="Arsipkan angkatan"
                                                    description={`Angkatan ${b.name} hanya dapat diarsipkan jika belum memiliki kelas atau kode registrasi.`}
                                                    confirmLabel="Arsipkan"
                                                    destructive
                                                    onConfirm={() => archive(b)}
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-5 py-16 text-center text-muted-foreground"
                                    >
                                        <CalendarRange className="mx-auto mb-3 size-8" />
                                        Belum ada angkatan pelatihan.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <PaginationLinks links={batches.links} />
            </div>
        </AdminLayout>
    );
}
