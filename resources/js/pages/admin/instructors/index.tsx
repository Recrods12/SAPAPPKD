import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { Contact, Pencil, Plus, Search, UserX } from "lucide-react";
import { PageHeader } from "@/components/page-header";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { DataSortControls } from "@/components/data-sort-controls";
import { PaginationLinks } from "@/components/pagination-links";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AdminLayout } from "@/layouts/admin-layout";
import type { Paginated } from "@/types";
interface Instructor {
    id: number;
    name: string;
    email: string;
    account_status: string;
    instructor_profile: {
        employee_number: string | null;
        phone: string;
        is_active: boolean;
    } | null;
    instructed_classes: { id: number; name: string }[];
}
export default function Index({
    instructors,
    search: initial,
    status: initialStatus,
    sort,
    direction,
}: {
    instructors: Paginated<Instructor>;
    search: string;
    status: string;
    sort: string;
    direction: string;
}) {
    const [search, setSearch] = useState(initial);
    const [status, setStatus] = useState(initialStatus);
    return (
        <AdminLayout title="Instruktur">
            <Head title="Instruktur" />
            <PageHeader
                eyebrow="DATA MASTER"
                title="Instruktur"
                description="Kelola akun, kontak, dan penugasan instruktur."
                action={
                    <Button asChild>
                        <Link href={route("admin.instructors.create")}>
                            <Plus />
                            Tambah instruktur
                        </Link>
                    </Button>
                }
            />
            <div className="mt-6 overflow-hidden rounded-xl border bg-white">
                <form
                    className="grid gap-2 border-b p-4 sm:grid-cols-[1fr_170px_auto]"
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(
                            route("admin.instructors.index"),
                            { search, status },
                            { preserveState: true },
                        );
                    }}
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari nama atau email…"
                    />
                    <select
                        className="h-11 rounded-lg border bg-white px-3 text-sm"
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        aria-label="Filter status instruktur"
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <Button variant="outline">
                        <Search />
                        Cari
                    </Button>
                </form>
                <DataSortControls
                    routeName="admin.instructors.index"
                    sort={sort}
                    direction={direction}
                    options={[
                        { value: "created_at", label: "Terbaru" },
                        { value: "name", label: "Nama" },
                        { value: "email", label: "Email" },
                        { value: "account_status", label: "Status" },
                    ]}
                />
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[800px] text-sm">
                        <thead className="bg-muted/60 text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="px-5 py-3">Instruktur</th>
                                <th className="px-5 py-3">Kontak</th>
                                <th className="px-5 py-3">Kelas</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3 text-right">
                                    Tindakan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {instructors.data.length ? (
                                instructors.data.map((i) => (
                                    <tr key={i.id} className="border-t">
                                        <td className="px-5 py-4">
                                            <Link
                                                className="font-semibold text-primary hover:underline"
                                                href={route(
                                                    "admin.instructors.show",
                                                    i.id,
                                                )}
                                            >
                                                {i.name}
                                            </Link>
                                            <p className="text-xs text-muted-foreground">
                                                {i.instructor_profile
                                                    ?.employee_number ||
                                                    "Tanpa nomor pegawai"}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {i.email}
                                            <p className="text-xs text-muted-foreground">
                                                {i.instructor_profile?.phone}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {i.instructed_classes
                                                .map((c) => c.name)
                                                .join(", ") ||
                                                "Belum ditugaskan"}
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge>
                                                {i.account_status === "active"
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
                                                            "admin.instructors.edit",
                                                            i.id,
                                                        )}
                                                    >
                                                        <Pencil />
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-red-600"
                                                            disabled={
                                                                i
                                                                    .instructed_classes
                                                                    .length > 0
                                                            }
                                                        >
                                                            <UserX />
                                                            Nonaktifkan
                                                        </Button>
                                                    }
                                                    title="Nonaktifkan instruktur"
                                                    description={`Akun ${i.name} tidak dapat digunakan lagi untuk masuk. Lepaskan penugasan kelas terlebih dahulu jika tombol tidak aktif.`}
                                                    confirmLabel="Nonaktifkan"
                                                    destructive
                                                    onConfirm={() =>
                                                        router.delete(
                                                            route(
                                                                "admin.instructors.destroy",
                                                                i.id,
                                                            ),
                                                        )
                                                    }
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="p-16 text-center text-muted-foreground"
                                    >
                                        <Contact className="mx-auto mb-2" />
                                        Belum ada instruktur.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <PaginationLinks links={instructors.links} />
            </div>
        </AdminLayout>
    );
}
