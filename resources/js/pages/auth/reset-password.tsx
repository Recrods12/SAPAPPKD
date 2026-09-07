import { Head, useForm } from "@inertiajs/react";
import { LoaderCircle, LockKeyhole } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AuthLayout } from "@/layouts/auth-layout";
export default function ResetPassword({
    email,
    token,
}: {
    email: string;
    token: string;
}) {
    const form = useForm({
        email,
        token,
        password: "",
        password_confirmation: "",
    });
    const field = (
        key: "password" | "password_confirmation",
        label: string,
    ) => (
        <label className="grid gap-2 text-sm font-medium">
            {label}
            <Input
                type="password"
                value={form.data[key]}
                onChange={(e) => form.setData(key, e.target.value)}
                required
                autoComplete="new-password"
            />
            {form.errors[key] && (
                <span role="alert" className="text-xs text-red-600">
                    {form.errors[key]}
                </span>
            )}
        </label>
    );
    return (
        <AuthLayout
            title="Buat kata sandi baru"
            description="Gunakan kata sandi yang kuat dan tidak digunakan pada layanan lain."
        >
            <Head title="Reset Kata Sandi" />
            <form
                className="mt-8 grid gap-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(route("password.store"));
                }}
            >
                <label className="grid gap-2 text-sm font-medium">
                    Email
                    <Input type="email" value={form.data.email} readOnly />
                    {form.errors.email && (
                        <span role="alert" className="text-xs text-red-600">
                            {form.errors.email}
                        </span>
                    )}
                </label>
                {field("password", "Kata sandi baru")}
                {field("password_confirmation", "Konfirmasi kata sandi")}
                <Button disabled={form.processing}>
                    {form.processing ? (
                        <LoaderCircle className="animate-spin" />
                    ) : (
                        <LockKeyhole />
                    )}
                    Simpan kata sandi
                </Button>
            </form>
        </AuthLayout>
    );
}
