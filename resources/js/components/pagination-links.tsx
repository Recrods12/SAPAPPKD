import { Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import type { PaginationLink } from "@/types";

export function PaginationLinks({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav
            aria-label="Navigasi halaman"
            className="flex flex-wrap gap-2 border-t p-4"
        >
            {links.map((link, index) => (
                <Button
                    key={`${link.label}-${index}`}
                    asChild={Boolean(link.url)}
                    disabled={!link.url}
                    variant={link.active ? "default" : "outline"}
                    size="sm"
                >
                    {link.url ? (
                        <Link
                            href={link.url}
                            preserveScroll
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )}
                </Button>
            ))}
        </nav>
    );
}
