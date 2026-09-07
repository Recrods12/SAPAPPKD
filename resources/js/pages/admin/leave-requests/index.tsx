import { Head, Link, router } from "@inertiajs/react";
import { Check, FileText, X } from "lucide-react";
import { PageHeader } from "@/components/page-header";
import { ReasonDialog } from "@/components/reason-dialog";
import { Button } from "@/components/ui/button";
import { AdminLayout } from "@/layouts/admin-layout";

interface Item {
    id: number;
    type: string;
    start_date: string;
    end_date: string;
    reason: string;
    status: string;
    has_document: boolean;
    admin_notes: string | null;
    user: {
        name: string;
        participant_profile?: { participant_number: string } | null;
    };
}
interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}

export default function AdminLeaveRequests({
    leaveRequests,
    filters,
}: {
    leaveRequests: { data: Item[]; links: PageLink[] };
    filters: { status?: string };
}) {
    function process(
        item: Item,
        status: "approved" | "rejected",
        notes: string,
    ) {
        router.patch(
            route("admin.leave-requests.update", item.id),
            { status, admin_notes: notes },
            { preserveScroll: true },
        );
    }
    const labels: Record<string, string> = {
        pending: "Menunggu",
        approved: "Disetujui",
        rejected: "Ditolak",
    };

    return (
        <AdminLayout title="Izin dan Sakit">
            <Head title="Izin dan Sakit" />
            <PageHeader
                eyebrow="PERSETUJUAN"
                title="Pengajuan izin dan sakit"
                description="Periksa dokumen dan proses pengajuan peserta."
            />
            <div className="mt-5 flex flex-wrap gap-2">
                {["", "pending", "approved", "rejected"].map((value) => (
                    <Button
                        key={value}
                        size="sm"
                        variant={
                            (filters.status ?? "") === value
                                ? "default"
                                : "outline"
                        }
                        onClick={() =>
                            router.get(
                                route("admin.leave-requests.index"),
                                value ? { status: value } : {},
                            )
                        }
                    >
                        {value === "" ? "Semua" : labels[value]}
                    </Button>
                ))}
            </div>
            <div className="mt-5 overflow-x-auto rounded-2xl border bg-white">
                <table className="w-full min-w-[850px] text-sm">
                    <thead className="bg-muted/60 text-left">
                        <tr>
                            <th className="p-4">Peserta</th>
                            <th className="p-4">Pengajuan</th>
                            <th className="p-4">Periode</th>
                            <th className="p-4">Alasan</th>
                            <th className="p-4">Status / Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {leaveRequests.data.map((item) => (
                            <tr key={item.id} className="border-t align-top">
                                <td className="p-4 font-semibold">
                                    {item.user.name}
                                    <small className="block text-muted-foreground">
                                        {
                                            item.user.participant_profile
                                                ?.participant_number
                                        }
                                    </small>
                                </td>
                                <td className="p-4">
                                    {item.type === "leave" ? "Izin" : "Sakit"}
                                    {item.has_document && (
                                        <Link
                                            className="mt-2 flex items-center gap-1 text-primary"
                                            href={route(
                                                "leave-requests.document",
                                                item.id,
                                            )}
                                            target="_blank"
                                        >
                                            <FileText className="size-4" />
                                            Dokumen
                                        </Link>
                                    )}
                                </td>
                                <td className="p-4">
                                    {item.start_date}
                                    <br />
                                    {item.end_date}
                                </td>
                                <td className="max-w-xs p-4">{item.reason}</td>
                                <td className="p-4">
                                    <span className="font-semibold">
                                        {labels[item.status] ?? item.status}
                                    </span>
                                    {item.status === "pending" && (
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            <ReasonDialog
                                                trigger={
                                                    <Button size="sm">
                                                        <Check />
                                                        Setujui
                                                    </Button>
                                                }
                                                title="Setujui pengajuan"
                                                description={`Setujui pengajuan ${item.user.name} setelah dokumen dan periode diperiksa.`}
                                                label="Catatan persetujuan"
                                                confirmLabel="Setujui"
                                                onConfirm={(notes) =>
                                                    process(
                                                        item,
                                                        "approved",
                                                        notes,
                                                    )
                                                }
                                            />
                                            <ReasonDialog
                                                trigger={
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="text-red-600"
                                                    >
                                                        <X />
                                                        Tolak
                                                    </Button>
                                                }
                                                title="Tolak pengajuan"
                                                description={`Penolakan pengajuan ${item.user.name} harus disertai alasan yang jelas.`}
                                                label="Alasan penolakan"
                                                confirmLabel="Tolak pengajuan"
                                                destructive
                                                onConfirm={(notes) =>
                                                    process(
                                                        item,
                                                        "rejected",
                                                        notes,
                                                    )
                                                }
                                            />
                                        </div>
                                    )}
                                    {item.admin_notes && (
                                        <small className="mt-2 block">
                                            {item.admin_notes}
                                        </small>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {leaveRequests.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="p-12 text-center text-muted-foreground"
                                >
                                    Tidak ada pengajuan.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
                {leaveRequests.links.length > 3 && (
                    <nav
                        className="flex flex-wrap gap-2 border-t p-4"
                        aria-label="Paginasi pengajuan"
                    >
                        {leaveRequests.links.map((link, index) => (
                            <Button
                                key={index}
                                asChild={Boolean(link.url)}
                                size="sm"
                                variant={link.active ? "default" : "outline"}
                                disabled={!link.url}
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
                    </nav>
                )}
            </div>
        </AdminLayout>
    );
}
