import { Head, Link, router } from "@inertiajs/react";
import { Check, UserCheck, X } from "lucide-react";
import { toast } from "sonner";
import { AdminLayout } from "@/layouts/admin-layout";
import { PageHeader } from "@/components/page-header";
import { ConfirmDialog } from "@/components/confirm-dialog";
import { PaginationLinks } from "@/components/pagination-links";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import type { Paginated } from "@/types";
interface Participant {
    id: number;
    name: string;
    email: string;
    account_status: string;
    participant_profile?: { participant_number: string; nik: string } | null;
    enrollments: Array<{
        training_batch?: { name: string; training_program?: { name: string } };
    }>;
}
const tabs = { pending: "Menunggu", active: "Aktif", rejected: "Ditolak" };
export default function Verifications({
    participants,
    status,
}: {
    participants: Paginated<Participant>;
    status: keyof typeof tabs;
}) {
    function decide(id: number, next: "active" | "rejected") {
        router.patch(
            route("admin.verifications.update", id),
            { status: next },
            {
                preserveScroll: true,
                onSuccess: () =>
                    toast.success(
                        next === "active"
                            ? "Akun peserta diaktifkan"
                            : "Pendaftaran ditolak",
                    ),
            },
        );
    }
    return (
        <AdminLayout title="Verifikasi Registrasi">
            <Head title="Verifikasi Registrasi" />
            <PageHeader
                eyebrow="PESERTA"
                title="Verifikasi registrasi"
                description="Periksa identitas dan program sebelum mengaktifkan akun."
            />
            <div className="mt-6 flex gap-2 overflow-x-auto">
                {Object.entries(tabs).map(([value, label]) => (
                    <Button
                        key={value}
                        asChild
                        variant={status === value ? "default" : "outline"}
                        size="sm"
                    >
                        <Link
                            href={route("admin.verifications.index", {
                                status: value,
                            })}
                        >
                            {label}
                        </Link>
                    </Button>
                ))}
            </div>
            <div className="mt-5 overflow-hidden rounded-xl border bg-white shadow-[var(--shadow-card)]">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[820px] text-sm">
                        <thead className="bg-muted/60 text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th className="px-5 py-3">Peserta</th>
                                <th className="px-5 py-3">Identitas</th>
                                <th className="px-5 py-3">Program</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3 text-right">
                                    Keputusan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {participants.data.length ? (
                                participants.data.map((p) => (
                                    <tr key={p.id} className="border-t">
                                        <td className="px-5 py-4">
                                            <strong>{p.name}</strong>
                                            <p className="text-xs text-muted-foreground">
                                                {p.email}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {p.participant_profile
                                                ?.participant_number ?? "—"}
                                            <p className="text-xs text-muted-foreground">
                                                {p.participant_profile
                                                    ? `NIK ${p.participant_profile.nik.slice(0, 6)}••••••${p.participant_profile.nik.slice(-4)}`
                                                    : "NIK —"}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            {p.enrollments[0]?.training_batch
                                                ?.training_program?.name ?? "—"}
                                            <p className="text-xs text-muted-foreground">
                                                {p.enrollments[0]
                                                    ?.training_batch?.name ??
                                                    ""}
                                            </p>
                                        </td>
                                        <td className="px-5 py-4">
                                            <Badge>
                                                {tabs[
                                                    p.account_status as keyof typeof tabs
                                                ] ?? p.account_status}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-4">
                                            <div className="flex justify-end gap-2">
                                                {p.account_status ===
                                                "pending" ? (
                                                    <>
                                                        <Button
                                                            size="sm"
                                                            onClick={() =>
                                                                decide(
                                                                    p.id,
                                                                    "active",
                                                                )
                                                            }
                                                        >
                                                            <Check className="size-3" />
                                                            Aktifkan
                                                        </Button>
                                                        <ConfirmDialog
                                                            trigger={
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    className="text-red-600"
                                                                >
                                                                    <X className="size-3" />
                                                                    Tolak
                                                                </Button>
                                                            }
                                                            title="Tolak pendaftaran"
                                                            description={`Pendaftaran ${p.name} akan ditolak dan akun tidak dapat digunakan untuk absensi.`}
                                                            confirmLabel="Tolak pendaftaran"
                                                            destructive
                                                            onConfirm={() =>
                                                                decide(
                                                                    p.id,
                                                                    "rejected",
                                                                )
                                                            }
                                                        />
                                                    </>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        Sudah diproses
                                                    </span>
                                                )}
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
                                        <UserCheck className="mx-auto mb-3 size-8 opacity-40" />
                                        Tidak ada peserta dengan status ini.
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
