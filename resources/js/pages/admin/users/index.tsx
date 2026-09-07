import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { AdminLayout } from '@/layouts/admin-layout';

interface ManagedUser { id: number; name: string; username: string | null; email: string; account_status: string; roles: { name: string }[] }
interface PaginatedUsers { data: ManagedUser[]; links: { url: string | null; label: string; active: boolean }[] }

function AccessEditor({ user }: { user: ManagedUser }) {
    const [role, setRole] = useState(user.roles[0]?.name ?? 'participant');
    const [accountStatus, setAccountStatus] = useState(user.account_status);
    const [processing, setProcessing] = useState(false);
    function submit(event: FormEvent) {
        event.preventDefault();
        router.patch(route('admin.users.update', user.id), { role, account_status: accountStatus }, { preserveScroll: true, onStart: () => setProcessing(true), onFinish: () => setProcessing(false) });
    }
    return <form onSubmit={submit} className="flex min-w-64 items-center justify-end gap-2">
        <label className="sr-only" htmlFor={`role-${user.id}`}>Peran {user.name}</label>
        <select id={`role-${user.id}`} value={role} onChange={(event) => setRole(event.target.value)} className="h-10 rounded-lg border bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary"><option value="admin-ppkd">Admin PPKD</option><option value="instructor">Instruktur</option><option value="participant">Peserta</option></select>
        <label className="sr-only" htmlFor={`status-${user.id}`}>Status {user.name}</label>
        <select id={`status-${user.id}`} value={accountStatus} onChange={(event) => setAccountStatus(event.target.value)} className="h-10 rounded-lg border bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>
        <Button size="sm" variant="outline" disabled={processing}>{processing ? 'Menyimpan…' : 'Simpan'}</Button>
    </form>;
}

export default function Users({ users }: { users: PaginatedUsers }) {
    const form = useForm({ name: '', username: '', email: '', password: '', password_confirmation: '', role: 'admin-ppkd' });
    function createAdmin(event: FormEvent) { event.preventDefault(); form.post(route('admin.users.store'), { preserveScroll: true, onSuccess: () => form.reset() }); }
    const fields = [['name', 'Nama lengkap', 'text', 'Nama admin'], ['username', 'Username', 'text', 'contoh: admin-pelatihan'], ['email', 'Email', 'email', 'nama@ppkd.go.id'], ['password', 'Kata sandi', 'password', 'Minimal 8 karakter'], ['password_confirmation', 'Konfirmasi kata sandi', 'password', 'Ulangi kata sandi']] as const;
    return <AdminLayout title="Manajemen Pengguna"><Head title="Manajemen Pengguna"/><PageHeader eyebrow="SUPER ADMIN" title="Manajemen pengguna" description="Buat akun Admin PPKD serta atur peran dan status akun dengan jejak audit."/>
        <form onSubmit={createAdmin} className="mt-6 rounded-2xl border bg-white p-5 shadow-sm" aria-labelledby="create-admin-title"><h2 id="create-admin-title" className="text-lg font-bold">Tambah Admin PPKD</h2><p className="mt-1 text-sm text-muted-foreground">Akun langsung aktif dan dapat masuk menggunakan username atau email.</p><div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">{fields.map(([key,label,type,placeholder])=><div key={key}><label htmlFor={key} className="mb-1.5 block text-sm font-semibold">{label}</label><Input id={key} type={type} value={form.data[key]} placeholder={placeholder} autoComplete={key.includes('password')?'new-password':undefined} onChange={(event)=>form.setData(key,event.target.value)} aria-invalid={Boolean(form.errors[key])}/>{form.errors[key]&&<p className="mt-1 text-sm text-destructive" role="alert">{form.errors[key]}</p>}</div>)}</div><Button className="mt-4" disabled={form.processing}>{form.processing?'Membuat akun…':'Buat akun admin'}</Button></form>
        <div className="mt-6 overflow-x-auto rounded-2xl border bg-white shadow-sm"><table className="w-full min-w-[820px] text-sm"><thead className="bg-muted"><tr><th className="p-4 text-left">Nama</th><th className="p-4 text-left">Username / Email</th><th className="p-4 text-left">Peran</th><th className="p-4 text-left">Status</th><th className="p-4 text-right">Pengaturan akses</th></tr></thead><tbody>{users.data.map(user=><tr key={user.id} className="border-t"><td className="p-4 font-semibold">{user.name}</td><td className="p-4"><span className="block">{user.username??'—'}</span><span className="text-muted-foreground">{user.email}</span></td><td className="p-4">{user.roles[0]?.name??'Tanpa peran'}</td><td className="p-4">{user.account_status}</td><td className="p-4"><AccessEditor user={user}/></td></tr>)}</tbody></table>{users.data.length===0&&<p className="p-8 text-center text-sm text-muted-foreground">Belum ada pengguna.</p>}</div>
        {users.links.length>3&&<nav className="mt-4 flex flex-wrap gap-2" aria-label="Paginasi pengguna">{users.links.map((link,index)=><Button key={index} type="button" size="sm" variant={link.active?'default':'outline'} disabled={!link.url} onClick={()=>link.url&&router.get(link.url)} dangerouslySetInnerHTML={{__html:link.label}}/>)}</nav>}
    </AdminLayout>;
}
