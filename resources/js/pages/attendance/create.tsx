import { Head, useForm } from "@inertiajs/react";
import {
    Camera,
    CheckCircle2,
    Clock3,
    LoaderCircle,
    LocateFixed,
    MapPin,
    Send,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { motion } from "motion/react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { LocationMap } from "@/components/location-map";
import { ParticipantQuickNav } from "@/components/participant-quick-nav";
import { useCamera } from "@/hooks/use-camera";
import { useDeviceToken } from "@/hooks/use-device-token";
import { useGeolocation } from "@/hooks/use-geolocation";
interface Schedule {
    id: number;
    subject: string | null;
    start_time: string;
    end_time: string;
    morning_open: string;
    morning_close: string;
    afternoon_open: string;
    afternoon_close: string;
    attendance_location: {
        name: string;
        address: string;
        latitude: string;
        longitude: string;
        radius_meters: number;
        max_accuracy_meters: number;
    } | null;
}
interface Attendance {
    id: number;
    type: "morning" | "afternoon";
    status: string;
    recorded_at: string;
    distance_meters: string;
}
function distance(a: number, b: number, c: number, d: number) {
    const r = 6371000,
        p1 = (a * Math.PI) / 180,
        p2 = (c * Math.PI) / 180,
        dp = ((c - a) * Math.PI) / 180,
        dl = ((d - b) * Math.PI) / 180,
        x =
            Math.sin(dp / 2) ** 2 +
            Math.cos(p1) * Math.cos(p2) * Math.sin(dl / 2) ** 2;
    return (
        Math.round(r * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x)) * 10) / 10
    );
}
export default function AttendancePage({
    serverTime,
    participant,
    schedule,
    attendances,
}: {
    serverTime: string;
    participant: {name:string;number:string|null;program:string|null;batch:string|null;class:string|null};
    schedule: Schedule | null;
    attendances: Attendance[];
}) {
    const [now, setNow] = useState(new Date(serverTime));
    const geo = useGeolocation();
    const camera = useCamera();
    const deviceToken = useDeviceToken();
    const [online, setOnline] = useState(navigator.onLine);
    const [photo, setPhoto] = useState<File | null>(null);
    const [preview, setPreview] = useState("");
    const form = useForm({
        training_schedule_id: schedule?.id ?? 0,
        type: "morning" as "morning" | "afternoon",
        latitude: 0,
        longitude: 0,
        accuracy_meters: 0,
        photo: null as File | null,
        device_token: deviceToken,
    });
    useEffect(() => {
        const timer = setInterval(
            () => setNow((value) => new Date(value.getTime() + 1000)),
            1000,
        );
        return () => clearInterval(timer);
    }, []);
    useEffect(
        () => () => {
            if (preview) URL.revokeObjectURL(preview);
        },
        [preview],
    );
    useEffect(() => {
        const update = () => setOnline(navigator.onLine);
        window.addEventListener("online", update);
        window.addEventListener("offline", update);
        return () => {
            window.removeEventListener("online", update);
            window.removeEventListener("offline", update);
        };
    }, []);
    const location = schedule?.attendance_location;
    const measured = useMemo(
        () =>
            geo.position && location
                ? distance(
                      geo.position.latitude,
                      geo.position.longitude,
                      Number(location.latitude),
                      Number(location.longitude),
                  )
                : null,
        [geo.position, location],
    );
    const done = attendances.some((a) => a.type === form.data.type);
    const validGps =
        !!geo.position &&
        !!location &&
        geo.position.accuracy <= location.max_accuracy_meters &&
        (measured ?? Infinity) <= location.radius_meters;
    async function take() {
        const file = await camera.capture();
        if (file) {
            setPhoto(file);
            setPreview(URL.createObjectURL(file));
            form.setData("photo", file);
        }
    }
    async function submit() {
        if (!photo || !online) return;
        let current;
        try {
            current = await geo.request(location?.max_accuracy_meters ?? 35);
        } catch {
            return;
        }
        form.transform((data) => ({
            ...data,
            latitude: current.latitude,
            longitude: current.longitude,
            accuracy_meters: current.accuracy,
            photo,
        }));
        form.post(route("attendance.store"), {
            forceFormData: true,
            preserveScroll: true,
        });
    }
    return (
        <main className="min-h-screen bg-slate-50 pb-12">
            <Head title="Absensi" />
            <header className="bg-slate-950 px-4 py-6 text-white">
                <div className="mx-auto grid max-w-3xl gap-4 sm:grid-cols-[1fr_auto] sm:items-start">
                    <div>
                        <p className="text-sm text-blue-200">Absensi peserta</p>
                        <h1 className="text-2xl font-bold">
                            {participant.name}
                        </h1>
                        <p className="mt-1 text-xs text-blue-100">{participant.number} · {[participant.program,participant.batch,participant.class].filter(Boolean).join(" · ")}</p>
                    </div>
                    <div className="grid justify-items-end gap-2 text-right">
                        <ParticipantQuickNav />
                        <p className="flex items-center gap-2 font-mono text-lg">
                            <Clock3 className="size-4" />
                            {now.toLocaleTimeString("id-ID")}
                        </p>
                        <p className="text-xs text-blue-200">
                            Waktu server WIB
                        </p>
                    </div>
                </div>
            </header>
            <div className="mx-auto grid max-w-3xl gap-4 p-4">
                {!online && <div role="alert" className="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Perangkat sedang offline. Hubungkan internet sebelum mengirim absensi.</div>}
                {!window.isSecureContext && <div role="alert" className="rounded-xl bg-red-50 p-4 text-sm text-red-700">Kamera dan GPS memerlukan koneksi HTTPS yang aman.</div>}
                {!schedule ? (
                    <Card>
                        <CardContent className="py-12 text-center">
                            <MapPin className="mx-auto mb-3 size-8" />
                            <p className="font-semibold">
                                Tidak ada jadwal aktif hari ini.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <div className="rounded-xl border bg-white p-4"><p className="text-xs font-semibold uppercase tracking-wide text-primary">Jadwal hari ini</p><h2 className="mt-1 font-bold">{schedule.subject||"Pelatihan"}</h2><p className="mt-1 text-sm text-muted-foreground">{schedule.start_time}–{schedule.end_time}</p></div>
                        <div className="grid grid-cols-2 gap-2">
                            <Button
                                variant={
                                    form.data.type === "morning"
                                        ? "default"
                                        : "outline"
                                }
                                onClick={() => form.setData("type", "morning")}
                            >
                                Absen pagi
                            </Button>
                            <Button
                                variant={
                                    form.data.type === "afternoon"
                                        ? "default"
                                        : "outline"
                                }
                                onClick={() =>
                                    form.setData("type", "afternoon")
                                }
                            >
                                Absen sore
                            </Button>
                        </div>
                        <div className="grid grid-cols-2 gap-3 text-sm"><div className="rounded-xl border bg-white p-3"><span className="text-muted-foreground">Status pagi</span><strong className="block">{attendances.some(a=>a.type==="morning")?"Sudah tercatat":"Belum tercatat"}</strong></div><div className="rounded-xl border bg-white p-3"><span className="text-muted-foreground">Status sore</span><strong className="block">{attendances.some(a=>a.type==="afternoon")?"Sudah tercatat":"Belum tercatat"}</strong></div></div>
                        {done && (
                            <motion.div
                                initial={{ opacity: 0, scale: 0.97 }}
                                animate={{ opacity: 1, scale: 1 }}
                                className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"
                            >
                                <CheckCircle2 className="mb-2" />
                                Absensi sesi ini sudah tercatat.
                            </motion.div>
                        )}
                        <Card>
                            <CardContent>
                                <h2 className="font-bold">
                                    1. Verifikasi lokasi
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {location?.name} · radius{" "}
                                    {location?.radius_meters} meter
                                </p>
                                <Button
                                    className="mt-4 w-full"
                                    variant="outline"
                                    onClick={() =>
                                        void geo.request(
                                            location?.max_accuracy_meters ?? 35,
                                        )
                                    }
                                    disabled={geo.status === "loading"}
                                >
                                    {geo.status === "loading" ? (
                                        <LoaderCircle className="animate-spin" />
                                    ) : (
                                        <LocateFixed />
                                    )}
                                    Ambil lokasi GPS
                                </Button>
                                {geo.position && (
                                    <div className="mt-4">
                                        <LocationMap
                                            center={[Number(location!.latitude), Number(location!.longitude)]}
                                            participant={[geo.position.latitude, geo.position.longitude]}
                                            radius={location!.radius_meters}
                                        />
                                    </div>
                                )}
                                {geo.position && (
                                    <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
                                        <div className="rounded-lg bg-muted p-3">
                                            Akurasi
                                            <strong className="block">
                                                {geo.position.accuracy.toFixed(
                                                    1,
                                                )}{" "}
                                                m
                                            </strong>
                                        </div>
                                        <div className="rounded-lg bg-muted p-3">
                                            Jarak
                                            <strong className="block">
                                                {measured} m
                                            </strong>
                                        </div>
                                    </div>
                                )}
                                <p
                                    role="status"
                                    className={`mt-3 text-sm ${validGps ? "text-emerald-700" : "text-red-600"}`}
                                >
                                    {validGps
                                        ? "Lokasi memenuhi syarat."
                                        : geo.error ||
                                          (geo.status === "loading"
                                              ? "Mencari sampel GPS yang lebih akurat..."
                                              : !geo.position
                                                ? "Ambil lokasi untuk memeriksa radius."
                                                : geo.position.accuracy >
                                                    (location?.max_accuracy_meters ??
                                                        35)
                                                  ? `Akurasi GPS ±${geo.position.accuracy.toFixed(1)} meter belum memenuhi batas maksimal ${location?.max_accuracy_meters ?? 35} meter.`
                                                  : `Jarak ${measured ?? 0} meter berada di luar radius ${location?.radius_meters ?? 0} meter.`)}
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent>
                                <h2 className="font-bold">2. Foto langsung</h2>
                                <div className="mt-4 aspect-[4/3] overflow-hidden rounded-xl bg-slate-950">
                                    {preview ? (
                                        <img
                                            src={preview}
                                            alt="Pratinjau foto absensi"
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <video
                                            ref={camera.videoRef}
                                            playsInline
                                            muted
                                            className="size-full object-cover"
                                        />
                                    )}
                                </div>
                                <div className="mt-3 grid grid-cols-2 gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={camera.start}
                                        disabled={camera.status === "loading"}
                                    >
                                        <Camera />
                                        Buka kamera
                                    </Button>
                                    <Button
                                        onClick={take}
                                        disabled={camera.status !== "ready"}
                                    >
                                        <Camera />
                                        Ambil foto
                                    </Button>
                                </div>
                                {preview && <Button className="mt-2 w-full" variant="ghost" onClick={() => { setPhoto(null); setPreview(""); form.setData("photo", null); camera.start(); }}>Ambil ulang foto</Button>}
                                {camera.error && (
                                    <p className="mt-2 text-sm text-red-600">
                                        {camera.error}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                        {Object.values(form.errors).length > 0 && (
                            <div
                                role="alert"
                                className="rounded-xl bg-red-50 p-4 text-sm text-red-700"
                            >
                                {Object.values(form.errors)[0]}
                            </div>
                        )}
                        <Button
                            size="lg"
                            className="h-14"
                            disabled={
                                done || !validGps || !photo || !online || !window.isSecureContext || form.processing || geo.status === "loading"
                            }
                            onClick={submit}
                        >
                            {form.processing ? (
                                <LoaderCircle className="animate-spin" />
                            ) : (
                                <Send />
                            )}
                            {form.processing
                                ? "Mengirim absensi…"
                                : "Kirim absensi"}
                        </Button>
                        {(!validGps || !photo) && !done && (
                            <p className="text-center text-xs text-muted-foreground">
                                Pastikan GPS memenuhi syarat dan foto sudah
                                diambil.
                            </p>
                        )}
                    </>
                )}
            </div>
        </main>
    );
}
