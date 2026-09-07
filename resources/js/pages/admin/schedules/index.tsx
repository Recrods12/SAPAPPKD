import { Head, Link, router } from "@inertiajs/react";
import { DataSortControls } from "@/components/data-sort-controls";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { CalendarDays, Pencil, Plus, Trash2 } from "lucide-react";
import { PageHeader } from "@/components/page-header";
import { PaginationLinks } from "@/components/pagination-links";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AdminLayout } from "@/layouts/admin-layout";
import type { Paginated } from "@/types";
interface Schedule {
    id: number;
    schedule_date: string;
    start_time: string;
    end_time: string;
    subject: string | null;
    status: string;
    training_class: {
        name: string;
        training_batch: { name: string; training_program: { code: string } };
    };
    attendance_location: { name: string } | null;
}
export default function Index({
    schedules,
    date,
    search,
    status,
    sort,
    direction,
}: {
    schedules: Paginated<Schedule>;
    date: string;
    search: string;
    status: string;
    sort: string;
    direction: string;
}) {
    return (
        <AdminLayout title="Jadwal">
            <Head title="Jadwal" />
            <PageHeader
                eyebrow="DATA MASTER"
                title="Jadwal pelatihan"
                description="Atur sesi dan jendela waktu absensi pagi serta sore."
                action={
                    <Button asChild>
                        <Link href={route("admin.schedules.create")}>
                            <Plus />
                            Tambah jadwal
                        </Link>
                    </Button>
                }
            />
            <form
                className="mt-6 grid gap-2 rounded-xl border bg-white p-4 sm:grid-cols-[1fr_180px_170px_auto]"
                onSubmit={(e) => {
                    e.preventDefault();
                    const value = new FormData(e.currentTarget).get("date");
                    const form = new FormData(e.currentTarget);
                    router.get(route("admin.schedules.index"), {
                        date: value,
                        search: form.get("search"),
                        status: form.get("status"),
                    });
                }}
            >
                <Input
                    name="search"
                    defaultValue={search}
                    placeholder="Cari materi atau kelas…"
                    aria-label="Cari jadwal"
                />
                <Input
                    name="date"
                    type="date"
                    defaultValue={date}
                    aria-label="Filter tanggal"
                />
                <select
                    name="status"
                    defaultValue={status}
                    className="h-11 rounded-lg border bg-white px-3"
                    aria-label="Filter status jadwal"
                >
                    <option value="">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
                <Button variant="outline">Terapkan filter</Button>
            </form>
            <div className="mt-4 overflow-x-auto rounded-xl border bg-white">
                <DataSortControls
                    routeName="admin.schedules.index"
                    sort={sort}
                    direction={direction}
                    options={[
                        { value: "schedule_date", label: "Tanggal" },
                        { value: "start_time", label: "Jam mulai" },
                        { value: "end_time", label: "Jam selesai" },
                        { value: "subject", label: "Materi" },
                        { value: "status", label: "Status" },
                    ]}
                />
                <table className="w-full min-w-[850px] text-sm">
                    <thead className="bg-muted/60 text-left text-xs uppercase text-muted-foreground">
                        <tr>
                            <th className="p-4">Tanggal</th>
                            <th className="p-4">Kelas</th>
                            <th className="p-4">Materi</th>
                            <th className="p-4">Waktu</th>
                            <th className="p-4">Lokasi</th>
                            <th className="p-4">Status</th>
                            <th className="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {schedules.data.length ? (
                            schedules.data.map((s) => (
                                <tr key={s.id} className="border-t">
                                    <td className="p-4 font-semibold">
                                        <Link
                                            className="text-primary hover:underline"
                                            href={route(
                                                "admin.schedules.show",
                                                s.id,
                                            )}
                                        >
                                            {new Date(
                                                s.schedule_date,
                                            ).toLocaleDateString("id-ID")}
                                        </Link>
                                    </td>
                                    <td className="p-4">
                                        {s.training_class.name}
                                        <p className="text-xs text-muted-foreground">
                                            {
                                                s.training_class.training_batch
                                                    .training_program.code
                                            }{" "}
                                            ·{" "}
                                            {
                                                s.training_class.training_batch
                                                    .name
                                            }
                                        </p>
                                    </td>
                                    <td className="p-4">{s.subject || "—"}</td>
                                    <td className="p-4">
                                        {s.start_time.slice(0, 5)}–
                                        {s.end_time.slice(0, 5)}
                                    </td>
                                    <td className="p-4">
                                        {s.attendance_location?.name || "—"}
                                    </td>
                                    <td className="p-4">
                                        <Badge>
                                            {s.status === "active"
                                                ? "Aktif"
                                                : "Dibatalkan"}
                                        </Badge>
                                    </td>
                                    <td className="p-4">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                asChild
                                                size="icon"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={route(
                                                        "admin.schedules.edit",
                                                        s.id,
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
                                                        aria-label="Hapus jadwal"
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                }
                                                title="Hapus jadwal"
                                                description="Jadwal hanya dapat dihapus jika belum memiliki catatan absensi."
                                                confirmLabel="Hapus jadwal"
                                                destructive
                                                onConfirm={() =>
                                                    router.delete(
                                                        route(
                                                            "admin.schedules.destroy",
                                                            s.id,
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
                                    colSpan={7}
                                    className="p-16 text-center text-muted-foreground"
                                >
                                    <CalendarDays className="mx-auto mb-2" />
                                    Belum ada jadwal.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
                <PaginationLinks links={schedules.links} />
            </div>
        </AdminLayout>
    );
}
