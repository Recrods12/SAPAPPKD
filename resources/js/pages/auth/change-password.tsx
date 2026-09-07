import { Head, Link, useForm } from "@inertiajs/react";
import { ArrowLeft, Save } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
export default function ChangePassword() {
    const form = useForm({
        current_password: "",
        password: "",
        password_confirmation: "",
    });
    const field = (key: keyof typeof form.data, label: string) => (
        <label className="grid gap-2 text-sm font-medium">
            {label}
            <Input
                type="password"
                value={form.data[key]}
                onChange={(e) => form.setData(key, e.target.value)}
                required
            />
            {form.errors[key] && (
                <span className="text-xs text-red-600">{form.errors[key]}</span>
            )}
        </label>
    );
    return (
        <main className="min-h-screen bg-muted/40 p-4 sm:p-8">
            <Head title="Ubah Kata Sandi" />
            <Card className="mx-auto max-w-xl">
                <CardContent>
                    <Button asChild variant="ghost">
                        <Link href={route("dashboard")}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                    <h1 className="mt-4 text-2xl font-bold">Ubah kata sandi</h1>
                    <form
                        className="mt-6 grid gap-5"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.put(route("password.update"), {
                                onSuccess: () => form.reset(),
                            });
                        }}
                    >
                        {field("current_password", "Kata sandi saat ini")}
                        {field("password", "Kata sandi baru")}
                        {field(
                            "password_confirmation",
                            "Konfirmasi kata sandi baru",
                        )}
                        <Button disabled={form.processing}>
                            <Save />
                            Simpan perubahan
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </main>
    );
}
