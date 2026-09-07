import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { AdminLayout } from "@/layouts/admin-layout";
interface ClassItem {
    id: number;
    name: string;
    training_batch: { name: string; training_program: { code: string } };
}
interface Location {
    id: number;
    name: string;
}
interface Schedule {
    id: number;
    [key: string]: unknown;
}
const timeFields = [
    ["start_time", "Mulai pelatihan"],
    ["end_time", "Selesai pelatihan"],
    ["morning_open", "Buka absen pagi"],
    ["morning_on_time_limit", "Batas tepat waktu"],
    ["morning_close", "Tutup absen pagi"],
    ["afternoon_open", "Buka absen sore"],
    ["afternoon_early_limit", "Batas pulang awal"],
    ["afternoon_close", "Tutup absen sore"],
] as const;
export default function Form({
    schedule,
    classes,
    locations,
    defaults,
}: {
    schedule: Schedule | null;
    classes: ClassItem[];
    locations: Location[];
    defaults: Record<string, string>;
}) {
    const initial = (key: string, fallback = "") =>
        String(schedule?.[key] ?? fallback);
    const time = (key: string, fallback: string) =>
        initial(key, fallback).slice(0, 5);
    const form = useForm({
        training_class_id: initial("training_class_id"),
        attendance_location_id: initial(
            "attendance_location_id",
            defaults.default_location_id,
        ),
        schedule_date: initial("schedule_date").slice(0, 10),
        start_time: time("start_time", defaults.default_start_time ?? "08:00"),
        end_time: time("end_time", defaults.default_end_time ?? "16:00"),
        morning_open: time(
            "morning_open",
            defaults.default_morning_open ?? "06:30",
        ),
        morning_on_time_limit: time(
            "morning_on_time_limit",
            defaults.default_morning_on_time_limit ?? "08:00",
        ),
        morning_close: time(
            "morning_close",
            defaults.default_morning_close ?? "09:00",
        ),
        afternoon_open: time(
            "afternoon_open",
            defaults.default_afternoon_open ?? "15:30",
        ),
        afternoon_early_limit: time(
            "afternoon_early_limit",
            defaults.default_afternoon_early_limit ?? "16:00",
        ),
        afternoon_close: time(
            "afternoon_close",
            defaults.default_afternoon_close ?? "18:00",
        ),
        subject: initial("subject"),
        notes: initial("notes"),
        status: initial("status", "active"),
    });
    const err = (k: keyof typeof form.data) =>
        form.errors[k] && (
            <span className="text-xs text-red-600">{form.errors[k]}</span>
        );
    return (
        <AdminLayout title="Jadwal">
            <Head title="Jadwal" />
            <Button asChild variant="ghost">
                <Link href={route("admin.schedules.index")}>
                    <ArrowLeft />
                    Kembali
                </Link>
            </Button>
            <Card className="mx-auto mt-4 max-w-4xl">
                <CardContent>
                    <h1 className="text-2xl font-bold">
                        {schedule ? "Edit jadwal" : "Tambah jadwal pelatihan"}
                    </h1>
                    <form
                        className="mt-6 grid gap-5 sm:grid-cols-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            schedule
                                ? form.put(
                                      route(
                                          "admin.schedules.update",
                                          schedule.id,
                                      ),
                                  )
                                : form.post(route("admin.schedules.store"));
                        }}
                    >
                        <label className="grid gap-2 text-sm font-medium">
                            Kelas
                            <select
                                className="h-11 rounded-lg border px-3"
                                value={form.data.training_class_id}
                                onChange={(e) =>
                                    form.setData(
                                        "training_class_id",
                                        e.target.value,
                                    )
                                }
                                required
                            >
                                <option value="">Pilih kelas</option>
                                {classes.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.training_batch.training_program.code}{" "}
                                        · {c.training_batch.name} · {c.name}
                                    </option>
                                ))}
                            </select>
                            {err("training_class_id")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Lokasi
                            <select
                                className="h-11 rounded-lg border px-3"
                                value={form.data.attendance_location_id}
                                onChange={(e) =>
                                    form.setData(
                                        "attendance_location_id",
                                        e.target.value,
                                    )
                                }
                                required
                            >
                                <option value="">Pilih lokasi aktif</option>
                                {locations.map((l) => (
                                    <option key={l.id} value={l.id}>
                                        {l.name}
                                    </option>
                                ))}
                            </select>
                            {err("attendance_location_id")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Tanggal
                            <Input
                                type="date"
                                value={form.data.schedule_date}
                                onChange={(e) =>
                                    form.setData(
                                        "schedule_date",
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            {err("schedule_date")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Materi
                            <Input
                                value={form.data.subject}
                                onChange={(e) =>
                                    form.setData("subject", e.target.value)
                                }
                            />
                            {err("subject")}
                        </label>
                        {timeFields.map(([key, label]) => (
                            <label
                                key={key}
                                className="grid gap-2 text-sm font-medium"
                            >
                                {label}
                                <Input
                                    type="time"
                                    value={form.data[key]}
                                    onChange={(e) =>
                                        form.setData(key, e.target.value)
                                    }
                                    required
                                />
                                {err(key)}
                            </label>
                        ))}
                        <label className="grid gap-2 text-sm font-medium sm:col-span-2">
                            Catatan
                            <textarea
                                className="min-h-20 rounded-lg border p-3"
                                value={form.data.notes}
                                onChange={(e) =>
                                    form.setData("notes", e.target.value)
                                }
                            />
                        </label>
                        <div className="flex justify-end sm:col-span-2">
                            <Button disabled={form.processing}>
                                <Save />
                                Simpan jadwal
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
