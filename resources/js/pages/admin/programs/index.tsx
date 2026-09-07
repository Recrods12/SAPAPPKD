import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { Plus, Search, Pencil, Archive } from "lucide-react";
import { toast } from "sonner";
import { AdminLayout } from "@/layouts/admin-layout";
import { DataSortControls } from "@/components/data-sort-controls";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { PageHeader } from "@/components/page-header";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import type { Paginated } from "@/types";
interface Program {
    id: number;
    code: string;
    name: string;
    description?: string;
    duration_days?: number;
    is_active: boolean;
}
export default function Programs({
    programs,
    search: initial,
    status: initialStatus,
    sort,
    direction,
}: {
    programs: Paginated<Program>;
    search: string;
    status: string;
    sort: string;
    direction: string;
}) {
    const [search, setSearch] = useState(initial);
    const [status, setStatus] = useState(initialStatus);
    function submit(e: React.FormEvent) {
        e.preventDefault();
        router.get(
            route("admin.programs.index"),
            { search, status },
            { preserveState: true, replace: true },
        );
    }
    function archive(program: Program) {
        router.delete(route("admin.programs.destroy", program.id), {
            onSuccess: () => toast.success("Program berhasil diarsipkan"),
        });
    }
    return (
        <AdminLayout title="Program Pelatihan">
            <Head title="Program Pelatihan" />
            <PageHeader
                eyebrow="DATA MASTER"
                title="Program pelatihan"
                description="Kelola bidang pelatihan yang tersedia."
                action={
                    <Button asChild>
                        <Link href={route("admin.programs.create")}>
                            <Plus />
                            Tambah program
                        </Link>
                    </Button>
                }
            />
            <div className="mt-6 overflow-hidden rounded-xl border bg-white shadow-[var(--shadow-card)]">
                <form
                    onSubmit={submit}
                    className="grid gap-2 border-b p-4 sm:grid-cols-[1fr_170px_auto]"
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari kode atau program…"
                    />
                    <select
                        className="h-11 rounded-lg border bg-white px-3"
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        aria-label="Filter status program"
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <Button variant="outline" aria-label="Cari">
                        <Search className="size-4" />
                    </Button>
                </form>
                <DataSortControls
                    routeName="admin.programs.index"
                    sort={sort}
                    direction={direction}
                    options={[
                        { value: "created_at", label: "Terbaru" },
                        { value: "code", label: "Kode" },
                        { value: "name", label: "Nama" },
                        { value: "duration_days", label: "Durasi" },
                        { value: "is_active", label: "Status" },
                    ]}
                />
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[720px] text-sm">
                        <thead className="bg-muted/60 text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th className="px-5 py-3">Kode</th>
                                <th className="px-5 py-3">Program</th>
                                <th className="px-5 py-3">Durasi</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3 text-right">
                                    Tindakan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {programs.data.length ? (
                                programs.data.map((p) => (
                                    <tr key={p.id} className="border-t">
                                        <td className="px-5 py-4 font-bold text-primary">
                                            {p.code}
                                        </td>
                                        <td className="px-5 py-4">
                                            <Link
                                                className="font-semibold text-primary hover:underline"
                                                href={route(
                                                    "admin.programs.show",
                                                    p.id,
                                                )}
                                            >
                                                {p.name}
                                            </Link>
                                            <p className="max-w-sm truncate text-xs text-muted-foreground">
                                                {p.description ||
                                                    "Belum ada deskripsi"}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {p.duration_days
                                                ? `${p.duration_days} hari`
                                                : "—"}
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge
                                                className={
                                                    p.is_active
                                                        ? "bg-emerald-50 text-emerald-700"
                                                        : "bg-muted text-muted-foreground"
                                                }
                                            >
                                                {p.is_active
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
                                                            "admin.programs.edit",
                                                            p.id,
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
                                                    title="Arsipkan program"
                                                    description={`Program ${p.name} hanya dapat diarsipkan jika belum digunakan oleh angkatan.`}
                                                    confirmLabel="Arsipkan"
                                                    destructive
                                                    onConfirm={() => archive(p)}
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
                                        Belum ada program pelatihan.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {programs.last_page > 1 && (
                    <div className="flex flex-wrap gap-2 border-t p-4">
                        {programs.links.map((link, i) => (
                            <Button
                                key={i}
                                asChild={!!link.url}
                                disabled={!link.url}
                                variant={link.active ? "default" : "outline"}
                                size="sm"
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
