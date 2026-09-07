import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { AdminLayout } from "@/layouts/admin-layout";
interface Batch {
    id: number;
    name: string;
    training_program: { code: string; name: string };
    training_classes: { id: number; name: string }[];
}
interface Participant {
    id: number;
    name: string;
    email: string;
    account_status: string;
    participant_profile: {
        nik: string;
        participant_number: string;
        gender: string;
        birth_place: string;
        birth_date: string;
        address: string;
        phone: string;
        participant_status: string;
    };
    enrollments: {
        training_batch_id: number;
        training_class_id: number | null;
    }[];
}
export default function Form({
    participant,
    batches,
}: {
    participant: Participant | null;
    batches: Batch[];
}) {
    const enrollment = participant?.enrollments[0];
    const form = useForm({
        name: participant?.name ?? "",
        email: participant?.email ?? "",
        password: "",
        password_confirmation: "",
        nik: participant?.participant_profile.nik ?? "",
        participant_number:
            participant?.participant_profile.participant_number ?? "",
        gender: participant?.participant_profile.gender ?? "male",
        birth_place: participant?.participant_profile.birth_place ?? "",
        birth_date:
            participant?.participant_profile.birth_date?.slice(0, 10) ?? "",
        address: participant?.participant_profile.address ?? "",
        phone: participant?.participant_profile.phone ?? "",
        training_batch_id: String(enrollment?.training_batch_id ?? ""),
        training_class_id: String(enrollment?.training_class_id ?? ""),
        account_status: participant?.account_status ?? "active",
        participant_status:
            participant?.participant_profile.participant_status ?? "active",
    });
    const selected = batches.find(
        (b) => String(b.id) === form.data.training_batch_id,
    );
    const err = (k: keyof typeof form.data) =>
        form.errors[k] && (
            <span role="alert" className="text-xs text-red-600">
                {form.errors[k]}
            </span>
        );
    function submit(e: React.FormEvent) {
        e.preventDefault();
        participant
            ? form.put(route("admin.participants.update", participant.id), {
                  onError: () => window.scrollTo({ top: 0, behavior: "smooth" }),
              })
            : form.post(route("admin.participants.store"), {
                  onError: () => window.scrollTo({ top: 0, behavior: "smooth" }),
              });
    }
    return (
        <AdminLayout title={participant ? "Edit Peserta" : "Tambah Peserta"}>
            <Head title="Data Peserta" />
            <Button asChild variant="ghost">
                <Link href={route("admin.participants.index")}>
                    <ArrowLeft />
                    Kembali
                </Link>
            </Button>
            <Card className="mx-auto mt-4 max-w-4xl">
                <CardContent>
                    <h1 className="text-2xl font-bold">
                        {participant ? "Edit data peserta" : "Tambah peserta"}
                    </h1>
                    {Object.keys(form.errors).length > 0 && (
                        <div role="alert" className="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <strong>Data belum dapat disimpan.</strong>
                            <p className="mt-1">
                                Periksa kembali field yang ditandai merah di bawah.
                            </p>
                        </div>
                    )}
                    <form
                        onSubmit={submit}
                        className="mt-6 grid gap-5 sm:grid-cols-2"
                    >
                        <label className="grid gap-2 text-sm font-medium">
                            Nama lengkap
                            <Input
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData("name", e.target.value)
                                }
                                required
                            />
                            {err("name")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Email
                            <Input
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData("email", e.target.value)
                                }
                                required
                            />
                            {err("email")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            NIK
                            <Input
                                inputMode="numeric"
                                value={form.data.nik}
                                onChange={(e) =>
                                    form.setData("nik", e.target.value)
                                }
                                required
                            />
                            {err("nik")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Nomor peserta
                            <Input
                                value={form.data.participant_number}
                                onChange={(e) =>
                                    form.setData(
                                        "participant_number",
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            {err("participant_number")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Jenis kelamin
                            <select
                                className="h-11 rounded-lg border px-3"
                                value={form.data.gender}
                                onChange={(e) =>
                                    form.setData("gender", e.target.value)
                                }
                            >
                                <option value="male">Laki-laki</option>
                                <option value="female">Perempuan</option>
                            </select>
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Tanggal lahir
                            <Input
                                type="date"
                                value={form.data.birth_date}
                                onChange={(e) =>
                                    form.setData("birth_date", e.target.value)
                                }
                                required
                            />
                            {err("birth_date")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Tempat lahir
                            <Input
                                value={form.data.birth_place}
                                onChange={(e) =>
                                    form.setData("birth_place", e.target.value)
                                }
                                required
                            />
                            {err("birth_place")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Telepon
                            <Input
                                value={form.data.phone}
                                onChange={(e) =>
                                    form.setData("phone", e.target.value)
                                }
                                required
                            />
                            {err("phone")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium sm:col-span-2">
                            Alamat
                            <textarea
                                className="min-h-24 rounded-lg border p-3"
                                value={form.data.address}
                                onChange={(e) =>
                                    form.setData("address", e.target.value)
                                }
                                required
                            />
                            {err("address")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Angkatan
                            <select
                                className="h-11 rounded-lg border px-3"
                                value={form.data.training_batch_id}
                                onChange={(e) => {
                                    form.setData(
                                        "training_batch_id",
                                        e.target.value,
                                    );
                                    form.setData("training_class_id", "");
                                }}
                                required
                            >
                                <option value="">Pilih angkatan</option>
                                {batches.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.training_program.code} · {b.name}
                                    </option>
                                ))}
                            </select>
                            {err("training_batch_id")}
                        </label>
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
                            >
                                <option value="">Belum ditentukan</option>
                                {selected?.training_classes.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                            {err("training_class_id")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Kata sandi {participant && "(opsional)"}
                            <Input
                                type="password"
                                value={form.data.password}
                                onChange={(e) =>
                                    form.setData("password", e.target.value)
                                }
                                required={!participant}
                            />
                            {err("password")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Konfirmasi kata sandi
                            <Input
                                type="password"
                                value={form.data.password_confirmation}
                                onChange={(e) =>
                                    form.setData(
                                        "password_confirmation",
                                        e.target.value,
                                    )
                                }
                                required={!participant}
                            />
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Status akun
                            <select
                                className="h-11 rounded-lg border px-3"
                                value={form.data.account_status}
                                onChange={(e) =>
                                    form.setData(
                                        "account_status",
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Status peserta
                            <select
                                className="h-11 rounded-lg border px-3"
                                value={form.data.participant_status}
                                onChange={(e) =>
                                    form.setData(
                                        "participant_status",
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                                <option value="completed">Lulus</option>
                                <option value="withdrawn">
                                    Mengundurkan diri
                                </option>
                            </select>
                        </label>
                        <div className="flex justify-end sm:col-span-2">
                            <Button disabled={form.processing}>
                                <Save />
                                Simpan peserta
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
