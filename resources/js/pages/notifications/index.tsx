import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Bell, CheckCheck, ExternalLink } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Notification { id: string; data: { title?: string; message?: string; status?: string; admin_notes?: string; url?: string }; read_at: string | null; created_at: string }
interface PageLink { url: string | null; label: string; active: boolean }
interface Props { notifications: { data: Notification[]; links: PageLink[] } }

export default function Notifications({ notifications }: Props) {
    const page = usePage<{ auth: { user: { roles: string[] } } }>();
    const roles = page.props.auth.user.roles;
    const backRoute = roles.includes('super-admin') || roles.includes('admin-ppkd') ? route('admin.dashboard') : roles.includes('instructor') ? route('instructor.dashboard') : route('dashboard');

    return <main className="min-h-screen bg-slate-50">
        <Head title="Notifikasi" />
        <header className="bg-slate-950 p-5 text-white"><div className="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3"><div><Button asChild variant="ghost" className="-ml-3 text-white hover:bg-white/10 hover:text-white"><Link href={backRoute}><ArrowLeft />Kembali</Link></Button><h1 className="mt-2 text-2xl font-bold">Notifikasi</h1></div><Button variant="outline" disabled={notifications.data.every((item) => item.read_at)} onClick={() => router.patch(route('notifications.read-all'), {}, { preserveScroll: true })}><CheckCheck />Baca semua</Button></div></header>
        <section className="mx-auto grid max-w-3xl gap-3 p-4" aria-live="polite">
            {notifications.data.map((item) => <article key={item.id} className={`w-full rounded-xl border p-4 text-left ${item.read_at ? 'bg-white' : 'border-blue-200 bg-blue-50'}`}><div className="flex gap-3"><Bell className="mt-1 size-5 shrink-0 text-primary" aria-hidden="true"/><div className="min-w-0 flex-1"><strong>{item.data.title || 'Pemberitahuan'}</strong><p className="mt-1 text-sm text-muted-foreground">{item.data.message || item.data.admin_notes || 'Status pengajuan Anda telah diperbarui.'}</p><time className="mt-2 block text-xs" dateTime={item.created_at}>{new Date(item.created_at).toLocaleString('id-ID')}</time><div className="mt-3 flex flex-wrap gap-2">{!item.read_at && <Button size="sm" variant="outline" onClick={() => router.patch(route('notifications.read', item.id), {}, { preserveScroll: true })}>Tandai dibaca</Button>}{item.data.url && <Button asChild size="sm"><Link href={item.data.url}><ExternalLink />Buka detail</Link></Button>}</div></div></div></article>)}
            {notifications.data.length === 0 && <div className="rounded-xl border bg-white p-10 text-center text-muted-foreground">Belum ada notifikasi.</div>}
            {notifications.links.length > 3 && <nav className="flex flex-wrap gap-2" aria-label="Paginasi notifikasi">{notifications.links.map((link, index) => <Button key={index} asChild={Boolean(link.url)} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url}>{link.url ? <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} /> : <span dangerouslySetInnerHTML={{ __html: link.label }} />}</Button>)}</nav>}
        </section>
    </main>;
}
