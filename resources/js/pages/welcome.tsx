import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'motion/react';
import { ArrowRight, Camera, Clock3, MapPin, ShieldCheck } from 'lucide-react';
import { Brand } from '@/components/brand';
import { Button } from '@/components/ui/button';
import { fadeUp, staggerContainer, staggerItem } from '@/lib/motion';
import type { SharedProps } from '@/types';

const features = [
    ['Lokasi terverifikasi', 'Jarak dihitung ulang oleh server dari titik absensi resmi.', MapPin],
    ['Foto langsung', 'Bukti kehadiran diambil melalui kamera perangkat.', Camera],
    ['Waktu server WIB', 'Pencatatan waktu tidak bergantung pada jam ponsel.', Clock3],
] as const;

export default function Welcome() {
    const { auth } = usePage<SharedProps>().props;
    const dashboardRoute = auth.user?.roles.some((role) => ['super-admin', 'admin-ppkd'].includes(role))
        ? route('admin.dashboard')
        : auth.user?.roles.includes('instructor')
            ? route('instructor.dashboard')
            : route('dashboard');

    return <>
        <Head title="Presensi Peserta Pelatihan" />
        <div className="bg-white">
            <section className="relative overflow-hidden bg-slate-950 text-white">
                <div className="sapa-grid absolute inset-0 opacity-40" />
                <nav className="relative mx-auto flex h-20 max-w-7xl items-center justify-between gap-2 px-4 sm:px-5 lg:px-8" aria-label="Navigasi utama">
                    <div className="min-w-0 flex-1"><Brand inverse compact /></div>
                    <div className="flex shrink-0 items-center gap-1 sm:gap-2">
                        {auth.user ? <Button asChild size="sm" className="sm:h-11 sm:px-4"><Link href={dashboardRoute}>Dashboard</Link></Button> : <>
                            <Button asChild variant="ghost" size="sm" className="text-white sm:h-11 sm:px-4"><Link href={route('login')}>Masuk</Link></Button>
                            <Button asChild variant="secondary" size="sm" className="sm:h-11 sm:px-4"><Link href={route('register')}><span className="sm:hidden">Daftar</span><span className="hidden sm:inline">Daftar peserta</span></Link></Button>
                        </>}
                    </div>
                </nav>

                <div className="relative mx-auto grid min-h-[650px] max-w-7xl items-center gap-14 px-5 py-14 sm:py-16 lg:grid-cols-2 lg:px-8">
                    <motion.div initial="hidden" animate="visible" variants={fadeUp}>
                        <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold"><span className="size-2 rounded-full bg-emerald-400" />SISTEM PRESENSI PESERTA</span>
                        <h1 className="mt-7 text-4xl font-bold leading-[1.08] tracking-tight sm:text-6xl">Kehadiran tercatat.<br /><span className="text-blue-300">Pelatihan lebih terarah.</span></h1>
                        <p className="mt-6 max-w-xl text-base leading-7 text-blue-100/70 sm:text-lg sm:leading-8">SAPA PPKD membantu peserta mencatat absensi pagi dan sore melalui foto langsung, lokasi GPS, dan waktu server.</p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Button asChild size="lg" variant="secondary"><Link href={route('login')}>Mulai presensi <ArrowRight className="size-4" /></Link></Button>
                            <Button asChild size="lg" variant="outline" className="border-white/20 bg-white/5 text-white hover:bg-white/10"><a href="#fitur">Pelajari fitur</a></Button>
                        </div>
                    </motion.div>

                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: .3, delay: .1 }} className="mx-auto w-full max-w-md rounded-[2rem] border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div className="rounded-[1.5rem] bg-white p-5 text-slate-900 shadow-2xl">
                            <div className="flex items-center justify-between"><div><p className="text-xs text-muted-foreground">Selamat pagi,</p><strong>Peserta PPKD</strong></div><span className="grid size-10 place-items-center rounded-full bg-blue-100 font-bold text-primary">PP</span></div>
                            <div className="mt-5 rounded-2xl bg-slate-950 p-5 text-white"><p className="text-xs text-blue-200">Waktu server</p><strong className="mt-1 block text-2xl">08:02:14 WIB</strong></div>
                            <div className="mt-4 grid place-items-center rounded-2xl bg-blue-50 py-10"><span className="grid size-16 place-items-center rounded-full bg-primary text-white shadow-lg"><MapPin /></span><span className="mt-3 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Dalam area absensi</span></div>
                            <div className="mt-4 grid grid-cols-2 gap-3"><div className="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs"><span className="text-emerald-700">Absen pagi</span><strong className="mt-1 block">Sudah hadir ✓</strong></div><div className="rounded-xl border p-3 text-xs"><span className="text-muted-foreground">Absen sore</span><strong className="mt-1 block">Belum tersedia</strong></div></div>
                        </div>
                    </motion.div>
                </div>
            </section>

            <section id="fitur" className="mx-auto max-w-7xl px-5 py-20 lg:px-8">
                <div className="max-w-2xl"><p className="text-xs font-bold tracking-[.16em] text-primary">FITUR UTAMA</p><h2 className="mt-3 text-3xl font-bold sm:text-4xl">Presensi yang mudah dan dapat dipertanggungjawabkan.</h2></div>
                <motion.div className="mt-10 grid gap-5 md:grid-cols-3" initial="hidden" whileInView="visible" viewport={{ once: true, amount: .2 }} variants={staggerContainer}>
                    {features.map(([title, description, Icon]) => <motion.article key={title} variants={staggerItem} className="rounded-xl border bg-white p-6 shadow-[var(--shadow-card)]"><span className="grid size-11 place-items-center rounded-xl bg-blue-50 text-primary"><Icon /></span><h3 className="mt-5 text-lg font-bold">{title}</h3><p className="mt-2 text-sm leading-6 text-muted-foreground">{description}</p></motion.article>)}
                </motion.div>
            </section>
            <section className="bg-blue-50 px-5 py-16 text-center"><ShieldCheck className="mx-auto size-10 text-primary" /><h2 className="mt-4 text-3xl font-bold">Siap mencatat kehadiran?</h2><p className="mt-3 text-muted-foreground">Pastikan akun sudah diverifikasi dan GPS perangkat aktif.</p><Button asChild className="mt-7" size="lg"><Link href={route('login')}>Masuk ke SAPA PPKD</Link></Button></section>
            <footer className="bg-slate-950 px-5 py-10 text-blue-100/60"><div className="mx-auto flex max-w-7xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><Brand inverse /><p className="text-xs">© {new Date().getFullYear()} PPKD Jakarta Barat</p></div></footer>
        </div>
    </>;
}
