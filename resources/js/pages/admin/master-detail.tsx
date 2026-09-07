import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, Pencil } from "lucide-react";
import { PageHeader } from "@/components/page-header";
import { Button } from "@/components/ui/button";
import { AdminLayout } from "@/layouts/admin-layout";

interface DetailField {
    label: string;
    value: string | number | boolean | null;
}

interface DetailSection {
    title: string;
    fields: DetailField[];
}

interface MasterDetailProps {
    title: string;
    eyebrow: string;
    description: string;
    backUrl: string;
    editUrl: string;
    sections: DetailSection[];
}

function displayValue(value: DetailField["value"]): string {
    if (typeof value === "boolean") {
        return value ? "Ya" : "Tidak";
    }

    if (value === null || value === "") {
        return "—";
    }

    return String(value);
}

export default function MasterDetail({
    title,
    eyebrow,
    description,
    backUrl,
    editUrl,
    sections,
}: MasterDetailProps) {
    return (
        <AdminLayout title={title}>
            <Head title={title} />
            <Button asChild variant="ghost" className="mb-4 -ml-3">
                <Link href={backUrl}>
                    <ArrowLeft />
                    Kembali
                </Link>
            </Button>
            <PageHeader
                eyebrow={eyebrow}
                title={title}
                description={description}
                action={
                    <Button asChild>
                        <Link href={editUrl}>
                            <Pencil />
                            Edit data
                        </Link>
                    </Button>
                }
            />
            <div className="mt-6 grid gap-5 xl:grid-cols-2">
                {sections.map((section) => (
                    <section
                        key={section.title}
                        className="rounded-2xl border bg-white p-5 shadow-[var(--shadow-card)]"
                    >
                        <h2 className="font-bold text-foreground">
                            {section.title}
                        </h2>
                        <dl className="mt-4 divide-y">
                            {section.fields.map((field) => (
                                <div
                                    key={field.label}
                                    className="grid gap-1 py-3 sm:grid-cols-[11rem_1fr] sm:gap-4"
                                >
                                    <dt className="text-sm text-muted-foreground">
                                        {field.label}
                                    </dt>
                                    <dd className="break-words text-sm font-medium text-foreground">
                                        {displayValue(field.value)}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                ))}
            </div>
        </AdminLayout>
    );
}
