import { Head, Link, router } from "@inertiajs/react";
import { useState } from "react";
import { Pencil, Plus, Search, UserRoundX, UsersRound } from "lucide-react";
import { PageHeader } from "@/components/page-header";
import { DataSortControls } from "@/components/data-sort-controls";
import { PaginationLinks } from "@/components/pagination-links";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AdminLayout } from "@/layouts/admin-layout";
import type { Paginated } from "@/types";
interface Participant {
    id: number;
    name: string;
    email: string;
    account_status: string;
    participant_profile: {
        participant_number: string;
        nik: string;
        phone: string;
        participant_status: string;
    } | null;
    enrollments: {
        training_batch: {
            name: string;
            training_program: { code: string; name: string };
        };
        training_class: { name: string } | null;
    }[];
}
export default function Index({
    participants,
    search: initial,
    status: initialStatus,
    sort,
    direction,
}: {
    participants: Paginated<Participant>;
    search: string;
    status: string;
    sort: string;
    direction: string;
}) {
    const [search, setSearch] = useState(initial);
    const [status, setStatus] = useState(initialStatus);
    return (
        <AdminLayout title="Data Peserta">
            <Head title="Data Peserta" />
            <PageHeader
                eyebrow="DATA MASTER"
                title="Peserta pelatihan"
                description="Kelola identitas, program, kelas, dan status peserta."
                action={
                    <Button asChild>
                        <Link href={route("admin.participants.create")}>
                            <Plus />
                            Tambah peserta
                        </Link>
                    </Button>
                }
            />
            <div className="mt-6 overflow-hidden rounded-xl border bg-white">
                <form
                    className="grid gap-2 border-b p-4 sm:grid-cols-[1fr_180px_auto]"
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(
                            route("admin.participants.index"),
                            { search, status },
                            { preserveState: true },
                        );
                    }}
                >
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Nama, email, atau nomor peserta…"
                    />
                    <select
                        className="h-11 rounded-lg border px-3"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="pending">Menunggu</option>
                        <option value="inactive">Nonaktif</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                    <Button variant="outline">
                        <Search />
                        Filter
                    </Button>
                </form>
                <DataSortControls
                    routeName="admin.participants.index"
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
                    <table className="w-full min-w-[900px] text-sm">
                        <thead className="bg-muted/60 text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="p-4">Peserta</th>
                                <th className="p-4">Identitas</th>
                                <th className="p-4">Pelatihan</th>
                                <th className="p-4">Status</th>
                                <th className="p-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {participants.data.length ? (
                                participants.data.map((p) => {
                                    const e = p.enrollments[0];
                                    return (
                                        <tr key={p.id} className="border-t">
                                            <td className="p-4">
                                                <Link
                                                    className="font-semibold text-primary hover:underline"
                                                    href={route(
                                                        "admin.participants.show",
                                                        p.id,
                                                    )}
                                                >
                                                    {p.name}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {p.email} ·{" "}
                                                    {
                                                        p.participant_profile
                                                            ?.phone
                                                    }
                                                </p>
                                            </td>
                                            <td className="p-4">
                                                {p.participant_profile
                                                    ?.participant_number || "—"}
                                                <p className="text-xs text-muted-foreground">
                                                    NIK{" "}
                                                    {p.participant_profile
                                                        ? `${p.participant_profile.nik.slice(0, 6)}••••••${p.participant_profile.nik.slice(-4)}`
                                                        : "—"}
                                                </p>
                                            </td>
                                            <td className="p-4">
                                                {e
                                                    ? `${e.training_batch.training_program.code} · ${e.training_batch.name}`
                                                    : "Belum ditempatkan"}
                                                <p className="text-xs text-muted-foreground">
                                                    {e?.training_class?.name ||
                                                        "Kelas belum ditentukan"}
                                                </p>
                                            </td>
                                            <td className="p-4">
                                                <Badge>
                                                    {p.account_status}
                                                </Badge>
                                            </td>
                                            <td className="p-4">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            href={route(
                                                                "admin.participants.edit",
                                                                p.id,
                                                            )}
                                                        >
                                                            <Pencil />
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                    <ConfirmDialog
                                                        trigger={
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                className="text-red-600"
                                                                disabled={
                                                                    p.account_status ===
                                                                    "inactive"
                                                                }
                                                                aria-label={`Nonaktifkan ${p.name}`}
                                                            >
                                                                <UserRoundX />
                                                            </Button>
                                                        }
                                                        title="Nonaktifkan peserta"
                                                        description={`Akun ${p.name} tidak akan dapat masuk atau melakukan absensi sampai diaktifkan kembali.`}
                                                        confirmLabel="Nonaktifkan"
                                                        destructive
                                                        onConfirm={() =>
                                                            router.delete(
                                                                route(
                                                                    "admin.participants.destroy",
                                                                    p.id,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="p-16 text-center text-muted-foreground"
                                    >
                                        <UsersRound className="mx-auto mb-2" />
                                        Belum ada peserta.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                <PaginationLinks links={participants.links} />
            </div>
        </AdminLayout>
    );
}
