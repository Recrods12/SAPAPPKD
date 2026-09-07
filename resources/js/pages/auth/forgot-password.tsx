import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, LoaderCircle, Mail } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AuthLayout } from "@/layouts/auth-layout";
export default function ForgotPassword() {
    const form = useForm({ email: "" });
    return (
        <AuthLayout
            title="Lupa kata sandi"
            description="Masukkan email akun. Kami akan mengirimkan tautan pemulihan jika email terdaftar."
        >
            <Head title="Lupa Kata Sandi" />
            <form
                className="mt-8 grid gap-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(route("password.email"));
                }}
            >
                <label className="grid gap-2 text-sm font-medium">
                    Email
                    <Input
                        type="email"
                        autoComplete="email"
                        value={form.data.email}
                        onChange={(e) => form.setData("email", e.target.value)}
                        required
                        autoFocus
                    />
                    {form.errors.email && (
                        <span role="alert" className="text-xs text-red-600">
                            {form.errors.email}
                        </span>
                    )}
                </label>
                <Button disabled={form.processing}>
                    {form.processing ? (
                        <LoaderCircle className="animate-spin" />
                    ) : (
                        <Mail />
                    )}
                    Kirim tautan reset
                </Button>
                <Button asChild variant="ghost">
                    <Link href={route("login")}>
                        <ArrowLeft />
                        Kembali ke login
                    </Link>
                </Button>
            </form>
        </AuthLayout>
    );
}
