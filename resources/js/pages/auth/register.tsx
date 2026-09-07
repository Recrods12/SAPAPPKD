import { useEffect, useState } from "react";
import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { AnimatePresence, motion } from "motion/react";
import { ArrowLeft, ArrowRight, Check, LoaderCircle } from "lucide-react";
import { AuthLayout } from "@/layouts/auth-layout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import type { SharedProps } from "@/types";

const steps = ["Identitas", "Pelatihan", "Kontak & akun", "Pemeriksaan"];
const fieldsByStep = [
    [
        "name",
        "nik",
        "participant_number",
        "gender",
        "birth_place",
        "birth_date",
    ],
    ["training_program_id", "training_batch_id", "training_class_id", "registration_code"],
    ["phone", "email", "username", "address", "password", "password_confirmation", "profile_photo"],
    ["privacy_consent"],
];

interface TrainingClassOption { id:number; name:string }
interface BatchOption { id:number; name:string; training_classes:TrainingClassOption[] }
interface ProgramOption { id:number; name:string; training_batches:BatchOption[] }

export default function Register({programs}:{programs:ProgramOption[]}) {
    const [step, setStep] = useState(0);
    const { app } = usePage<SharedProps>().props;
    const form = useForm({
        name: "",
        nik: "",
        participant_number: "",
        gender: "male",
        birth_place: "",
        birth_date: "",
        address: "",
        phone: "",
        email: "",
        username: "",
        training_program_id: "",
        training_batch_id: "",
        training_class_id: "",
        registration_code: "",
        password: "",
        password_confirmation: "",
        privacy_consent: false,
        profile_photo: null as File|null,
    });
    const batches=programs.find(program=>String(program.id)===form.data.training_program_id)?.training_batches??[];
    const classes=batches.find(batch=>String(batch.id)===form.data.training_batch_id)?.training_classes??[];

    useEffect(() => {
        const errorKeys = Object.keys(form.errors);
        if (!errorKeys.length) return;
        const errorStep = fieldsByStep.findIndex((fields) =>
            fields.some((field) => errorKeys.includes(field)),
        );
        if (errorStep >= 0) setStep(errorStep);
    }, [form.errors]);

    function field(name: keyof typeof form.data, label: string, type = "text") {
        return (
            <label className="grid gap-2 text-sm font-medium">
                {label}
                <Input
                    type={type}
                    value={String(form.data[name])}
                    onChange={(event) =>
                        form.setData(name, event.target.value as never)
                    }
                    required
                    aria-invalid={Boolean(form.errors[name])}
                />
                {form.errors[name] && (
                    <span role="alert" className="text-xs text-red-600">
                        {form.errors[name]}
                    </span>
                )}
            </label>
        );
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();
        if (step < 3) {
            setStep((current) => current + 1);
            return;
        }
        form.post(route("register"), { preserveScroll: true, forceFormData: true });
    }

    return (
        <AuthLayout
            title="Daftar sebagai peserta"
            description="Lengkapi data secara bertahap. Informasi Anda digunakan untuk administrasi dan presensi pelatihan."
        >
            <Head title="Registrasi" />
            <div
                className="mt-7 grid grid-cols-4 gap-2"
                aria-label="Tahap registrasi"
            >
                {steps.map((label, index) => (
                    <div key={label}>
                        <div
                            className={`h-1.5 rounded-full ${index <= step ? "bg-primary" : "bg-muted"}`}
                        />
                        <span
                            className={`mt-2 hidden text-[11px] sm:block ${index === step ? "font-semibold text-primary" : "text-muted-foreground"}`}
                        >
                            {label}
                        </span>
                    </div>
                ))}
            </div>
            <form onSubmit={submit} className="mt-7">
                <AnimatePresence mode="wait" initial={false}>
                    <motion.div
                        key={step}
                        initial={{ opacity: 0, x: 12 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: -12 }}
                        transition={{ duration: 0.18 }}
                        className="grid gap-4"
                    >
                        {step === 0 && (
                            <>
                                {field("name", "Nama lengkap")}
                                {field("nik", "NIK")}
                                {field("participant_number", "Nomor peserta")}
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <label className="grid gap-2 text-sm font-medium">
                                        Jenis kelamin
                                        <select
                                            className="h-11 rounded-lg border bg-white px-3 text-sm"
                                            value={form.data.gender}
                                            onChange={(event) =>
                                                form.setData(
                                                    "gender",
                                                    event.target.value,
                                                )
                                            }
                                        >
                                            <option value="male">
                                                Laki-laki
                                            </option>
                                            <option value="female">
                                                Perempuan
                                            </option>
                                        </select>
                                    </label>
                                    {field(
                                        "birth_date",
                                        "Tanggal lahir",
                                        "date",
                                    )}
                                </div>
                                {field("birth_place", "Tempat lahir")}
                            </>
                        )}
                        {step === 1 && (
                            <>
                                <label className="grid gap-2 text-sm font-medium">Program pelatihan<select className="h-11 rounded-lg border bg-white px-3" value={form.data.training_program_id} onChange={event=>{form.setData(data=>({...data,training_program_id:event.target.value,training_batch_id:"",training_class_id:""}))}} required><option value="">Pilih program</option>{programs.map(program=><option key={program.id} value={program.id}>{program.name}</option>)}</select>{form.errors.training_program_id&&<span className="text-xs text-red-600">{form.errors.training_program_id}</span>}</label>
                                <label className="grid gap-2 text-sm font-medium">Angkatan<select className="h-11 rounded-lg border bg-white px-3" value={form.data.training_batch_id} onChange={event=>{form.setData(data=>({...data,training_batch_id:event.target.value,training_class_id:""}))}} required><option value="">Pilih angkatan</option>{batches.map(batch=><option key={batch.id} value={batch.id}>{batch.name}</option>)}</select>{form.errors.training_batch_id&&<span className="text-xs text-red-600">{form.errors.training_batch_id}</span>}</label>
                                <label className="grid gap-2 text-sm font-medium">Kelas<select className="h-11 rounded-lg border bg-white px-3" value={form.data.training_class_id} onChange={event=>form.setData("training_class_id",event.target.value)} required><option value="">Pilih kelas</option>{classes.map(item=><option key={item.id} value={item.id}>{item.name}</option>)}</select>{form.errors.training_class_id&&<span className="text-xs text-red-600">{form.errors.training_class_id}</span>}</label>
                                {field(
                                    "registration_code",
                                    "Kode registrasi / undangan",
                                )}
                            </>
                        )}
                        {step === 2 && (
                            <>
                                {field("phone", "Nomor WhatsApp")}
                                {field("email", "Email", "email")}
                                {field("username", "Username")}
                                <label className="grid gap-2 text-sm font-medium">
                                    Alamat
                                    <textarea
                                        className="min-h-24 rounded-lg border bg-white p-3 text-sm"
                                        value={form.data.address}
                                        onChange={(event) =>
                                            form.setData(
                                                "address",
                                                event.target.value,
                                            )
                                        }
                                        required
                                    />
                                    {form.errors.address && (
                                        <span
                                            role="alert"
                                            className="text-xs text-red-600"
                                        >
                                            {form.errors.address}
                                        </span>
                                    )}
                                </label>
                                {field("password", "Kata sandi", "password")}
                                <p className="-mt-3 text-xs leading-5 text-muted-foreground">
                                    Minimal 6 karakter. Boleh menggunakan huruf saja, angka saja, atau gabungan karakter apa pun.
                                </p>
                                {field(
                                    "password_confirmation",
                                    "Konfirmasi kata sandi",
                                    "password",
                                )}
                                <label className="grid gap-2 text-sm font-medium">Foto profil (opsional)<Input type="file" accept="image/jpeg,image/png,image/webp" onChange={event=>form.setData("profile_photo",event.target.files?.[0]??null)}/>{form.errors.profile_photo&&<span className="text-xs text-red-600">{form.errors.profile_photo}</span>}</label>
                            </>
                        )}
                        {step === 3 && (
                            <>
                                <div className="rounded-xl border bg-muted/40 p-4 text-sm">
                                    <h3 className="font-semibold">
                                        Periksa kembali data
                                    </h3>
                                    <dl className="mt-3 grid gap-2 text-muted-foreground">
                                        <div className="flex justify-between gap-4">
                                            <dt>Nama</dt>
                                            <dd className="text-right font-medium text-foreground">
                                                {form.data.name}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between gap-4"><dt>Program / angkatan / kelas</dt><dd className="text-right font-medium text-foreground">{programs.find(p=>String(p.id)===form.data.training_program_id)?.name} / {batches.find(b=>String(b.id)===form.data.training_batch_id)?.name} / {classes.find(c=>String(c.id)===form.data.training_class_id)?.name}</dd></div>
                                        <div className="flex justify-between gap-4">
                                            <dt>Nomor peserta</dt>
                                            <dd className="text-right font-medium text-foreground">
                                                {form.data.participant_number}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between gap-4">
                                            <dt>Email</dt>
                                            <dd className="text-right font-medium text-foreground">
                                                {form.data.email}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between gap-4">
                                            <dt>Kode registrasi</dt>
                                            <dd className="text-right font-medium text-foreground">
                                                {form.data.registration_code}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                                <div className="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-950"><strong className="block">Penggunaan foto dan lokasi</strong>{app.privacyNotice}</div>
                                <label className="flex items-start gap-3 text-sm leading-6">
                                    <input
                                        className="mt-1 size-4"
                                        type="checkbox"
                                        checked={form.data.privacy_consent}
                                        onChange={(event) =>
                                            form.setData(
                                                "privacy_consent",
                                                event.target.checked,
                                            )
                                        }
                                        required
                                    />
                                    <span>
                                        Saya menyetujui penggunaan data foto dan
                                        lokasi untuk verifikasi kehadiran serta
                                        telah membaca penjelasan privasi.
                                    </span>
                                </label>
                            </>
                        )}
                    </motion.div>
                </AnimatePresence>
                <div className="mt-7 flex justify-between gap-3">
                    {step > 0 ? (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setStep((current) => current - 1)}
                        >
                            <ArrowLeft className="size-4" />
                            Kembali
                        </Button>
                    ) : (
                        <Button asChild type="button" variant="ghost">
                            <Link href={route("login")}>Sudah punya akun</Link>
                        </Button>
                    )}
                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? (
                            <LoaderCircle className="size-4 animate-spin" />
                        ) : step === 3 ? (
                            <Check className="size-4" />
                        ) : (
                            <ArrowRight className="size-4" />
                        )}
                        {step === 3 ? "Kirim pendaftaran" : "Lanjut"}
                    </Button>
                </div>
            </form>
        </AuthLayout>
    );
}
