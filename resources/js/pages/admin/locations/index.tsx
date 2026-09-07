import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { Archive, MapPin, Pencil, Plus, Search } from "lucide-react";
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
interface Location {
    id: number;
    name: string;
    address: string;
    latitude: string;
    longitude: string;
    radius_meters: number;
    max_accuracy_meters: number;
    is_active: boolean;
}
export default function Index({
    locations,
    search: initial,
    status: initialStatus,
    sort,
    direction,
}: {
    locations: Paginated<Location>;
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
            route("admin.locations.index"),
            { search, status },
            { preserveState: true, replace: true },
        );
    }
    function archive(location: Location) {
        router.delete(route("admin.locations.destroy", location.id), {
            onSuccess: () => toast.success("Lokasi berhasil diarsipkan"),
        });
    }
    return (
        <AdminLayout title="Lokasi Absensi">
            <Head title="Lokasi Absensi" />
            <PageHeader
                eyebrow="DATA MASTER"
                title="Lokasi absensi"
                description="Koordinat dan radius akan divalidasi ulang oleh server saat peserta absen."
                action={
                    <Button asChild>
                        <Link href={route("admin.locations.create")}>
                            <Plus />
                            Tambah lokasi
                        </Link>
                    </Button>
                }
            />
            <div className="mt-6 overflow-hidden rounded-xl border bg-white shadow-[var(--shadow-card)]">
                <form
                    onSubmit={filter}
                    className="grid gap-2 border-b p-4 sm:grid-cols-[1fr_170px_auto]"
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Cari nama atau alamat lokasi…"
                    />
                    <select
                        className="h-11 rounded-lg border bg-white px-3 text-sm"
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        aria-label="Filter status lokasi"
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <Button variant="outline">
                        <Search className="size-4" />
                        Cari
                    </Button>
                </form>
                <DataSortControls
                    routeName="admin.locations.index"
                    sort={sort}
                    direction={direction}
                    options={[
                        { value: "created_at", label: "Terbaru" },
                        { value: "name", label: "Nama" },
                        { value: "radius_meters", label: "Radius" },
                        { value: "max_accuracy_meters", label: "Akurasi" },
                        { value: "is_active", label: "Status" },
                    ]}
                />
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[900px] text-sm">
                        <thead className="bg-muted/60 text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="px-5 py-3">Lokasi</th>
                                <th className="px-5 py-3">Koordinat</th>
                                <th className="px-5 py-3">Radius</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3 text-right">
                                    Tindakan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {locations.data.length ? (
                                locations.data.map((l) => (
                                    <tr key={l.id} className="border-t">
                                        <td className="px-5 py-4">
                                            <Link
                                                className="font-semibold text-primary hover:underline"
                                                href={route(
                                                    "admin.locations.show",
                                                    l.id,
                                                )}
                                            >
                                                {l.name}
                                            </Link>
                                            <p className="max-w-md truncate text-xs text-muted-foreground">
                                                {l.address}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4 font-mono text-xs">
                                            {l.latitude}, {l.longitude}
                                        </td>
                                        <td className="px-5 py-4">
                                            <strong>{l.radius_meters} m</strong>
                                            <p className="text-xs text-muted-foreground">
                                                Akurasi maks.{" "}
                                                {l.max_accuracy_meters} m
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge
                                                className={
                                                    l.is_active
                                                        ? "bg-emerald-50 text-emerald-700"
                                                        : "bg-muted text-muted-foreground"
                                                }
                                            >
                                                {l.is_active
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
                                                            "admin.locations.edit",
                                                            l.id,
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
                                                            disabled={
                                                                l.is_active
                                                            }
                                                        >
                                                            <Archive className="size-3" />
                                                            Arsip
                                                        </Button>
                                                    }
                                                    title="Arsipkan lokasi"
                                                    description={`Lokasi ${l.name} akan disembunyikan dari pengaturan jadwal baru.`}
                                                    confirmLabel="Arsipkan"
                                                    destructive
                                                    onConfirm={() => archive(l)}
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
                                        <MapPin className="mx-auto mb-3 size-8" />
                                        Belum ada lokasi absensi.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <PaginationLinks links={locations.links} />
            </div>
        </AdminLayout>
    );
}
