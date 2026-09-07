import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { Archive, Pencil, Plus, School, Search } from "lucide-react";
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
    training_program: { code: string; name: string };
}
interface ClassItem {
    id: number;
    name: string;
    room: string | null;
    capacity: number;
    is_active: boolean;
    enrollments_count: number;
    training_batch: Batch;
    instructors: { id: number; name: string }[];
}
export default function Index({
    classes,
    batches,
    search: initial,
    batchId: initialBatch,
    status: initialStatus,
    sort,
    direction,
}: {
    classes: Paginated<ClassItem>;
    batches: Batch[];
    search: string;
    batchId: number;
    status: string;
    sort: string;
    direction: string;
}) {
    const [search, setSearch] = useState(initial);
    const [batch, setBatch] = useState(String(initialBatch || ""));
    const [status, setStatus] = useState(initialStatus);
    function submit(e: React.FormEvent) {
        e.preventDefault();
        router.get(
            route("admin.classes.index"),
            { search, batch, status },
            { preserveState: true, replace: true },
        );
    }
    function archive(item: ClassItem) {
        router.delete(route("admin.classes.destroy", item.id), {
            onSuccess: () => toast.success("Kelas berhasil diarsipkan"),
        });
    }
    return (
        <AdminLayout title="Kelas Pelatihan">
            <Head title="Kelas Pelatihan" />
            <PageHeader
                eyebrow="DATA MASTER"
                title="Kelas pelatihan"
                description="Kelola pembagian ruangan, kapasitas, dan instruktur kelas."
                action={
                    <Button asChild>
                        <Link href={route("admin.classes.create")}>
                            <Plus />
                            Tambah kelas
                        </Link>
                    </Button>
                }
            />
            <div className="mt-6 overflow-hidden rounded-xl border bg-white shadow-[var(--shadow-card)]">
                <form
                    onSubmit={submit}
                    className="grid gap-2 border-b p-4 sm:grid-cols-[1fr_220px_160px_auto]"
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari kelas atau ruangan…"
                    />
                    <select
                        className="h-11 rounded-lg border bg-white px-3 text-sm"
                        value={batch}
                        onChange={(e) => setBatch(e.target.value)}
                    >
                        <option value="">Semua angkatan</option>
                        {batches.map((b) => (
                            <option key={b.id} value={b.id}>
                                {b.training_program.code} · {b.name}
                            </option>
                        ))}
                    </select>
                    <select
                        className="h-11 rounded-lg border bg-white px-3 text-sm"
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        aria-label="Filter status kelas"
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <Button variant="outline">
                        <Search className="size-4" />
                        Filter
                    </Button>
                </form>
                <DataSortControls
                    routeName="admin.classes.index"
                    sort={sort}
                    direction={direction}
                    options={[
                        { value: "created_at", label: "Terbaru" },
                        { value: "name", label: "Nama" },
                        { value: "room", label: "Ruangan" },
                        { value: "capacity", label: "Kapasitas" },
                        { value: "is_active", label: "Status" },
                    ]}
                />
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[900px] text-sm">
                        <thead className="bg-muted/60 text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="px-5 py-3">Kelas</th>
                                <th className="px-5 py-3">Angkatan</th>
                                <th className="px-5 py-3">Instruktur</th>
                                <th className="px-5 py-3">Kapasitas</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3 text-right">
                                    Tindakan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {classes.data.length ? (
                                classes.data.map((item) => (
                                    <tr key={item.id} className="border-t">
                                        <td className="px-5 py-4">
                                            <Link
                                                className="font-semibold text-primary hover:underline"
                                                href={route(
                                                    "admin.classes.show",
                                                    item.id,
                                                )}
                                            >
                                                {item.name}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                {item.room ||
                                                    "Ruangan belum ditentukan"}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {
                                                item.training_batch
                                                    .training_program.code
                                            }
                                            <p className="text-xs text-muted-foreground">
                                                {item.training_batch.name}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {item.instructors
                                                .map((i) => i.name)
                                                .join(", ") ||
                                                "Belum ditugaskan"}
                                        </td>
                                        <td className="px-5 py-4">
                                            {item.enrollments_count}/
                                            {item.capacity}
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge
                                                className={
                                                    item.is_active
                                                        ? "bg-emerald-50 text-emerald-700"
                                                        : "bg-muted text-muted-foreground"
                                                }
                                            >
                                                {item.is_active
                                                    ? "Aktif"
                                                    : "Nonaktif"}
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
                                                            "admin.classes.edit",
                                                            item.id,
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
                                                    title="Arsipkan kelas"
                                                    description={`Kelas ${item.name} hanya dapat diarsipkan jika belum memiliki peserta atau jadwal.`}
                                                    confirmLabel="Arsipkan"
                                                    destructive
                                                    onConfirm={() =>
                                                        archive(item)
                                                    }
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-5 py-16 text-center text-muted-foreground"
                                    >
                                        <School className="mx-auto mb-3 size-8" />
                                        Belum ada kelas pelatihan.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <PaginationLinks links={classes.links} />
            </div>
        </AdminLayout>
    );
}
