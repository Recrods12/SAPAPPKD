import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { CalendarOff, Pencil, Plus, Search, Trash2 } from "lucide-react";
import { PageHeader } from "@/components/page-header";
import { DataSortControls } from "@/components/data-sort-controls";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { PaginationLinks } from "@/components/pagination-links";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AdminLayout } from "@/layouts/admin-layout";
import type { Paginated } from "@/types";
interface Holiday {
    id: number;
    holiday_date: string;
    name: string;
    description: string | null;
    is_active: boolean;
}
export default function Index({
    holidays,
    search: initial,
    status: initialStatus,
    sort,
    direction,
}: {
    holidays: Paginated<Holiday>;
    search: string;
    status: string;
    sort: string;
    direction: string;
}) {
    const [search, setSearch] = useState(initial);
    const [status, setStatus] = useState(initialStatus);
    return (
        <AdminLayout title="Hari Libur">
            <Head title="Hari Libur" />
            <PageHeader
                eyebrow="DATA MASTER"
                title="Hari libur"
                description="Tanggal aktif akan meniadakan kewajiban absensi."
                action={
                    <Button asChild>
                        <Link href={route("admin.holidays.create")}>
                            <Plus />
                            Tambah hari libur
                        </Link>
                    </Button>
                }
            />
            <div className="mt-6 overflow-hidden rounded-xl border bg-white">
                <form
                    className="grid gap-2 border-b p-4 sm:grid-cols-[1fr_170px_auto]"
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(route("admin.holidays.index"), {
                            search,
                            status,
                        });
                    }}
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari hari libur…"
                    />
                    <select
                        className="h-11 rounded-lg border bg-white px-3"
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        aria-label="Filter status hari libur"
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
                    routeName="admin.holidays.index"
                    sort={sort}
                    direction={direction}
                    options={[
                        { value: "holiday_date", label: "Tanggal" },
                        { value: "name", label: "Nama" },
                        { value: "is_active", label: "Status" },
                    ]}
                />
                <div className="divide-y">
                    {holidays.data.length ? (
                        holidays.data.map((h) => (
                            <div
                                key={h.id}
                                className="grid gap-3 p-5 sm:grid-cols-[160px_1fr_auto] sm:items-center"
                            >
                                <strong>
                                    {new Date(
                                        h.holiday_date,
                                    ).toLocaleDateString("id-ID", {
                                        dateStyle: "long",
                                    })}
                                </strong>
                                <div>
                                    <Link
                                        className="font-semibold text-primary hover:underline"
                                        href={route(
                                            "admin.holidays.show",
                                            h.id,
                                        )}
                                    >
                                        {h.name}
                                    </Link>
                                    <p className="text-sm text-muted-foreground">
                                        {h.description || "Tanpa keterangan"}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge>
                                        {h.is_active ? "Aktif" : "Nonaktif"}
                                    </Badge>
                                    <Button
                                        asChild
                                        size="icon"
                                        variant="outline"
                                    >
                                        <Link
                                            href={route(
                                                "admin.holidays.edit",
                                                h.id,
                                            )}
                                        >
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <ConfirmDialog
                                        trigger={
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                className="text-red-600"
                                                aria-label={`Hapus ${h.name}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        }
                                        title="Hapus hari libur"
                                        description={`Tanggal ${h.name} akan kembali dianggap sebagai hari pelatihan.`}
                                        confirmLabel="Hapus"
                                        destructive
                                        onConfirm={() =>
                                            router.delete(
                                                route(
                                                    "admin.holidays.destroy",
                                                    h.id,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        ))
                    ) : (
                        <p className="p-16 text-center text-muted-foreground">
                            <CalendarOff className="mx-auto mb-2" />
                            Belum ada hari libur.
                        </p>
                    )}
                </div>
                <PaginationLinks links={holidays.links} />
            </div>
        </AdminLayout>
    );
}
