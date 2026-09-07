import { Head, useForm } from "@inertiajs/react";
import { AlertTriangle, LoaderCircle, Save } from "lucide-react";
import { PageHeader } from "@/components/page-header";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AdminLayout } from "@/layouts/admin-layout";

interface Location {
    id: number;
    name: string;
    address: string;
}

interface Props {
    settings: Record<string, string>;
    locations: Location[];
    missingSetup: string[];
    hasLogo: boolean;
}

const missingLabels: Record<string, string> = {
    institution_address: "alamat instansi",
    default_location_id: "lokasi absensi default",
    mail_from_address: "email pengirim",
    logo_path: "logo aplikasi",
};

export default function Settings({
    settings,
    locations,
    missingSetup,
    hasLogo,
}: Props) {
    const form = useForm({
        _method: "put",
        app_name: settings.app_name ?? "SAPA PPKD",
        institution_name: settings.institution_name ?? "PPKD Jakarta Barat",
        institution_address: settings.institution_address ?? "",
        timezone: settings.timezone ?? "Asia/Jakarta",
        default_location_id: settings.default_location_id ?? "",
        default_radius_meters: Number(settings.default_radius_meters ?? 20),
        default_max_accuracy_meters: Number(
            settings.default_max_accuracy_meters ?? 35,
        ),
        default_start_time: settings.default_start_time ?? "08:00",
        default_end_time: settings.default_end_time ?? "16:00",
        default_morning_open: settings.default_morning_open ?? "06:30",
        default_morning_on_time_limit:
            settings.default_morning_on_time_limit ?? "08:00",
        default_morning_close: settings.default_morning_close ?? "09:00",
        default_afternoon_open: settings.default_afternoon_open ?? "15:30",
        default_afternoon_early_limit:
            settings.default_afternoon_early_limit ?? "16:00",
        default_afternoon_close: settings.default_afternoon_close ?? "18:00",
        max_photo_size_kb: Number(settings.max_photo_size_kb ?? 3072),
        photo_compression_quality: Number(
            settings.photo_compression_quality ?? 82,
        ),
        photo_retention_days: Number(settings.photo_retention_days ?? 365),
        mail_from_address: settings.mail_from_address ?? "",
        privacy_notice:
            settings.privacy_notice ??
            "Foto dan lokasi digunakan untuk verifikasi kehadiran peserta pelatihan.",
        logo: null as File | null,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.post(route("admin.settings.update"), {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    const error = (field: keyof typeof form.data) =>
        form.errors[field] && (
            <span className="text-sm text-red-600" role="alert">
                {form.errors[field]}
            </span>
        );
    const numberField = (
        field:
            | "default_radius_meters"
            | "default_max_accuracy_meters"
            | "max_photo_size_kb"
            | "photo_compression_quality"
            | "photo_retention_days",
        label: string,
        min: number,
        max: number,
    ) => (
        <label className="grid gap-1.5 text-sm font-semibold">
            {label}
            <Input
                type="number"
                min={min}
                max={max}
                value={form.data[field]}
                onChange={(event) =>
                    form.setData(field, Number(event.target.value))
                }
                aria-invalid={Boolean(form.errors[field])}
            />
            {error(field)}
        </label>
    );
    const timeField = (
        field:
            | "default_start_time"
            | "default_end_time"
            | "default_morning_open"
            | "default_morning_on_time_limit"
            | "default_morning_close"
            | "default_afternoon_open"
            | "default_afternoon_early_limit"
            | "default_afternoon_close",
        label: string,
    ) => (
        <label className="grid gap-1.5 text-sm font-semibold">
            {label}
            <Input
                type="time"
                value={form.data[field]}
                onChange={(event) => form.setData(field, event.target.value)}
                aria-invalid={Boolean(form.errors[field])}
            />
            {error(field)}
        </label>
    );

    return (
        <AdminLayout title="Pengaturan">
            <Head title="Pengaturan" />
            <PageHeader
                eyebrow="SUPER ADMIN"
                title="Pengaturan aplikasi"
                description="Nilai berikut menjadi sumber default operasional dan dapat diubah tanpa menyentuh source code."
            />

            {missingSetup.length > 0 && (
                <div
                    className="mt-5 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                    role="alert"
                >
                    <AlertTriangle className="mt-0.5 size-5 shrink-0" />
                    <div>
                        <strong>Konfigurasi produksi belum lengkap.</strong>
                        <p className="mt-1">
                            Lengkapi{" "}
                            {missingSetup
                                .map((key) => missingLabels[key] ?? key)
                                .join(", ")}{" "}
                            sebelum aplikasi digunakan peserta.
                        </p>
                    </div>
                </div>
            )}

            <form onSubmit={submit} className="mt-6 grid gap-6 xl:grid-cols-2">
                <section className="rounded-2xl border bg-white p-5 shadow-[var(--shadow-card)]">
                    <h2 className="text-lg font-bold">
                        Identitas dan komunikasi
                    </h2>
                    <div className="mt-4 grid gap-4">
                        <label className="grid gap-1.5 text-sm font-semibold">
                            Nama aplikasi
                            <Input
                                value={form.data.app_name}
                                onChange={(event) =>
                                    form.setData("app_name", event.target.value)
                                }
                                aria-invalid={Boolean(form.errors.app_name)}
                            />
                            {error("app_name")}
                        </label>
                        <label className="grid gap-1.5 text-sm font-semibold">
                            Nama instansi
                            <Input
                                value={form.data.institution_name}
                                onChange={(event) =>
                                    form.setData(
                                        "institution_name",
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(
                                    form.errors.institution_name,
                                )}
                            />
                            {error("institution_name")}
                        </label>
                        <label className="grid gap-1.5 text-sm font-semibold">
                            Alamat instansi
                            <textarea
                                className="min-h-24 rounded-lg border p-3 font-normal focus:outline-none focus:ring-2 focus:ring-primary"
                                value={form.data.institution_address}
                                onChange={(event) =>
                                    form.setData(
                                        "institution_address",
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(
                                    form.errors.institution_address,
                                )}
                            />
                            {error("institution_address")}
                        </label>
                        <label className="grid gap-1.5 text-sm font-semibold">
                            Zona waktu
                            <select
                                className="h-11 rounded-lg border bg-white px-3 font-normal"
                                value={form.data.timezone}
                                onChange={(event) =>
                                    form.setData("timezone", event.target.value)
                                }
                            >
                                <option value="Asia/Jakarta">
                                    Asia/Jakarta (WIB)
                                </option>
                            </select>
                            {error("timezone")}
                        </label>
                        <label className="grid gap-1.5 text-sm font-semibold">
                            Email pengirim
                            <Input
                                type="email"
                                value={form.data.mail_from_address}
                                onChange={(event) =>
                                    form.setData(
                                        "mail_from_address",
                                        event.target.value,
                                    )
                                }
                                placeholder="Isi email resmi instansi"
                                aria-invalid={Boolean(
                                    form.errors.mail_from_address,
                                )}
                            />
                            {error("mail_from_address")}
                        </label>
                    </div>
                </section>

                <section className="rounded-2xl border bg-white p-5 shadow-[var(--shadow-card)]">
                    <h2 className="text-lg font-bold">
                        Lokasi dan GPS default
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Dipakai sebagai nilai awal saat admin membuat lokasi
                        atau jadwal baru.
                    </p>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                        <label className="grid gap-1.5 text-sm font-semibold sm:col-span-2">
                            Lokasi default
                            <select
                                className="h-11 rounded-lg border bg-white px-3 font-normal"
                                value={form.data.default_location_id}
                                onChange={(event) =>
                                    form.setData(
                                        "default_location_id",
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="">Pilih lokasi aktif</option>
                                {locations.map((location) => (
                                    <option
                                        key={location.id}
                                        value={location.id}
                                    >
                                        {location.name} — {location.address}
                                    </option>
                                ))}
                            </select>
                            {error("default_location_id")}
                        </label>
                        {numberField(
                            "default_radius_meters",
                            "Radius default (meter)",
                            5,
                            5000,
                        )}
                        {numberField(
                            "default_max_accuracy_meters",
                            "Akurasi GPS maksimum (meter)",
                            5,
                            500,
                        )}
                    </div>
                </section>

                <section className="rounded-2xl border bg-white p-5 shadow-[var(--shadow-card)] xl:col-span-2">
                    <h2 className="text-lg font-bold">
                        Jadwal absensi default
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Setiap jadwal tetap menyimpan waktunya sendiri; nilai
                        ini mempercepat pembuatan jadwal baru.
                    </p>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {timeField("default_start_time", "Mulai pelatihan")}
                        {timeField("default_end_time", "Selesai pelatihan")}
                        {timeField("default_morning_open", "Buka absen pagi")}
                        {timeField(
                            "default_morning_on_time_limit",
                            "Batas tepat waktu",
                        )}
                        {timeField("default_morning_close", "Tutup absen pagi")}
                        {timeField("default_afternoon_open", "Buka absen sore")}
                        {timeField(
                            "default_afternoon_early_limit",
                            "Batas pulang cepat",
                        )}
                        {timeField(
                            "default_afternoon_close",
                            "Tutup absen sore",
                        )}
                    </div>
                </section>

                <section className="rounded-2xl border bg-white p-5 shadow-[var(--shadow-card)]">
                    <h2 className="text-lg font-bold">Foto dan penyimpanan</h2>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2">
                        {numberField(
                            "max_photo_size_kb",
                            "Ukuran foto maksimum (KB)",
                            256,
                            5120,
                        )}
                        {numberField(
                            "photo_compression_quality",
                            "Kualitas JPEG (%)",
                            40,
                            95,
                        )}
                        {numberField(
                            "photo_retention_days",
                            "Masa simpan foto (hari)",
                            30,
                            3650,
                        )}
                        <label className="grid gap-2 text-sm font-semibold">
                            Logo aplikasi
                            <span className="text-xs font-normal text-muted-foreground">
                                PNG/JPG/WebP, 128–2000 px, maksimal 2 MB.{" "}
                                {hasLogo
                                    ? "Logo saat ini akan dipertahankan jika kosong."
                                    : "Belum ada logo."}
                            </span>
                            {hasLogo && (
                                <img
                                    src={route("brand.logo")}
                                    alt="Logo aplikasi saat ini"
                                    className="h-16 w-16 rounded-xl border object-contain"
                                />
                            )}
                            <Input
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                onChange={(event) =>
                                    form.setData(
                                        "logo",
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            {error("logo")}
                        </label>
                    </div>
                </section>

                <section className="rounded-2xl border bg-white p-5 shadow-[var(--shadow-card)]">
                    <h2 className="text-lg font-bold">Privasi peserta</h2>
                    <label className="mt-4 grid gap-1.5 text-sm font-semibold">
                        Penjelasan privasi
                        <textarea
                            className="min-h-44 rounded-lg border p-3 font-normal focus:outline-none focus:ring-2 focus:ring-primary"
                            value={form.data.privacy_notice}
                            onChange={(event) =>
                                form.setData(
                                    "privacy_notice",
                                    event.target.value,
                                )
                            }
                            aria-invalid={Boolean(form.errors.privacy_notice)}
                        />
                        {error("privacy_notice")}
                    </label>
                </section>

                <div className="sticky bottom-4 flex justify-end rounded-2xl border bg-white/95 p-4 shadow-lg backdrop-blur xl:col-span-2">
                    <Button size="lg" disabled={form.processing}>
                        {form.processing ? (
                            <LoaderCircle className="animate-spin" />
                        ) : (
                            <Save />
                        )}
                        {form.processing
                            ? "Menyimpan…"
                            : "Simpan seluruh pengaturan"}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
