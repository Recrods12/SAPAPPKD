import { Head, Link, useForm } from "@inertiajs/react";
import { Eye, EyeOff, LoaderCircle, LogIn } from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AuthLayout } from "@/layouts/auth-layout";

export default function Login() {
    const [show, setShow] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        login: "",
        password: "",
        remember: false,
    });

    function submit(event: React.FormEvent) {
        event.preventDefault();
        post(route("login"));
    }

    return (
        <AuthLayout
            title="Selamat datang kembali"
            description="Masuk menggunakan akun peserta atau petugas yang sudah aktif."
        >
            <Head title="Masuk" />
            <form onSubmit={submit} className="mt-8 grid gap-5">
                <label className="grid gap-2 text-sm font-medium">
                    ID admin atau email
                    <Input
                        type="text"
                        value={data.login}
                        onChange={(event) =>
                            setData("login", event.target.value)
                        }
                        autoComplete="username"
                        placeholder="admin atau nama@email.com"
                        required
                        autoFocus
                        aria-invalid={!!errors.login}
                    />
                    {errors.login && (
                        <span role="alert" className="text-xs text-red-600">
                            {errors.login}
                        </span>
                    )}
                </label>
                <label className="grid gap-2 text-sm font-medium">
                    Kata sandi
                    <div className="relative">
                        <Input
                            type={show ? "text" : "password"}
                            value={data.password}
                            onChange={(event) =>
                                setData("password", event.target.value)
                            }
                            autoComplete="current-password"
                            required
                            className="pr-11"
                        />
                        <button
                            type="button"
                            aria-label={
                                show
                                    ? "Sembunyikan kata sandi"
                                    : "Tampilkan kata sandi"
                            }
                            className="absolute right-1 top-1 grid size-9 place-items-center text-muted-foreground"
                            onClick={() => setShow(!show)}
                        >
                            {show ? (
                                <EyeOff className="size-4" />
                            ) : (
                                <Eye className="size-4" />
                            )}
                        </button>
                    </div>
                </label>
                <div className="flex items-center justify-between gap-3">
                    <label className="flex items-center gap-2 text-sm text-muted-foreground">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(event) =>
                                setData("remember", event.target.checked)
                            }
                            className="size-4 rounded"
                        />
                        Ingat saya
                    </label>
                    <Link
                        href={route("password.request")}
                        className="text-sm font-semibold text-primary"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>
                <Button size="lg" disabled={processing}>
                    {processing ? (
                        <LoaderCircle className="size-4 animate-spin" />
                    ) : (
                        <LogIn className="size-4" />
                    )}
                    {processing ? "Memproses…" : "Masuk"}
                </Button>
                <p className="text-center text-sm text-muted-foreground">
                    Belum memiliki akun?{" "}
                    <Link
                        href={route("register")}
                        className="font-semibold text-primary"
                    >
                        Daftar peserta
                    </Link>
                </p>
            </form>
        </AuthLayout>
    );
}
