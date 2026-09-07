import { Head, Link, useForm } from "@inertiajs/react";
import {
    ArrowLeft,
    Crosshair,
    LoaderCircle,
    MapPin,
    Save,
    Search,
} from "lucide-react";
import { useState } from "react";
import { LocationPickerMap } from "@/components/location-picker-map";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { AdminLayout } from "@/layouts/admin-layout";
interface Location {
    id: number;
    name: string;
    address: string;
    latitude: string;
    longitude: string;
    radius_meters: number;
    max_accuracy_meters: number;
    is_active: boolean;
}
interface MapSearchResult {
    place_id: number;
    lat: string;
    lon: string;
    display_name: string;
}
export default function Form({
    location,
    defaults,
}: {
    location: Location | null;
    defaults: Record<string, string>;
}) {
    const [mapSearch, setMapSearch] = useState("");
    const [mapResults, setMapResults] = useState<MapSearchResult[]>([]);
    const [mapMessage, setMapMessage] = useState("");
    const [isLocating, setIsLocating] = useState(false);
    const [isSearching, setIsSearching] = useState(false);
    const form = useForm({
        name: location?.name ?? "",
        address: location?.address ?? "",
        latitude: location?.latitude ?? "",
        longitude: location?.longitude ?? "",
        radius_meters: String(
            location?.radius_meters ?? defaults.default_radius_meters ?? 20,
        ),
        max_accuracy_meters: String(
            location?.max_accuracy_meters ??
                defaults.default_max_accuracy_meters ??
                35,
        ),
        is_active: location?.is_active ?? false,
    });
    const error = (key: keyof typeof form.data) =>
        form.errors[key] && (
            <span className="text-xs text-red-600">{form.errors[key]}</span>
        );
    function submit(e: React.FormEvent) {
        e.preventDefault();
        location
            ? form.put(route("admin.locations.update", location.id))
            : form.post(route("admin.locations.store"));
    }

    const latitude = Number(form.data.latitude);
    const longitude = Number(form.data.longitude);
    const mapCenter: [number, number] =
        Number.isFinite(latitude) && Number.isFinite(longitude)
            ? [latitude, longitude]
            : [-6.1754, 106.8272];

    function selectCoordinates([nextLatitude, nextLongitude]: [
        number,
        number,
    ]) {
        form.setData((data) => ({
            ...data,
            latitude: nextLatitude.toFixed(7),
            longitude: nextLongitude.toFixed(7),
        }));
        setMapMessage("Titik lokasi berhasil diperbarui.");
    }

    function useCurrentLocation() {
        if (!navigator.geolocation) {
            setMapMessage("Perangkat ini tidak mendukung pengambilan lokasi.");
            return;
        }

        setIsLocating(true);
        setMapMessage("Mencari lokasi perangkat...");
        navigator.geolocation.getCurrentPosition(
            (position) => {
                selectCoordinates([
                    position.coords.latitude,
                    position.coords.longitude,
                ]);
                setMapMessage(
                    `Lokasi perangkat digunakan (akurasi ±${Math.round(position.coords.accuracy)} meter).`,
                );
                setIsLocating(false);
            },
            () => {
                setMapMessage(
                    "Lokasi tidak ditemukan. Pastikan izin lokasi browser sudah aktif.",
                );
                setIsLocating(false);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
        );
    }

    async function searchLocation() {
        const query = mapSearch.trim();

        if (!query) {
            setMapMessage("Masukkan nama tempat atau alamat terlebih dahulu.");
            return;
        }

        setIsSearching(true);
        setMapResults([]);
        setMapMessage("Mencari lokasi...");

        try {
            const normalizedQuery = query.toLocaleLowerCase("id-ID");
            const effectiveQuery =
                normalizedQuery.includes("ppkd") &&
                normalizedQuery.includes("jakarta barat")
                    ? "Jalan Kamal Raya No 2 Tegal Alur Kalideres Jakarta Barat 11820"
                    : query;
            const params = new URLSearchParams({
                q: effectiveQuery,
                format: "jsonv2",
                limit: "5",
                countrycodes: "id",
                "accept-language": "id",
            });
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?${params}`,
                { headers: { Accept: "application/json" } },
            );

            if (!response.ok) {
                throw new Error("Pencarian lokasi gagal.");
            }

            const results = (await response.json()) as MapSearchResult[];
            const result = results[0];

            if (!result) {
                setMapMessage("Lokasi tidak ditemukan. Coba alamat yang lebih lengkap.");
                return;
            }

            setMapResults(results);
            chooseSearchResult(result);
        } catch {
            setMapMessage(
                "Pencarian belum dapat digunakan. Pilih titik langsung pada peta.",
            );
        } finally {
            setIsSearching(false);
        }
    }

    function chooseSearchResult(result: MapSearchResult) {
        form.setData((data) => ({
            ...data,
            address: result.display_name,
            latitude: Number(result.lat).toFixed(7),
            longitude: Number(result.lon).toFixed(7),
        }));
        setMapMessage(`Lokasi ditemukan: ${result.display_name}`);
    }
    return (
        <AdminLayout title={location ? "Edit Lokasi" : "Tambah Lokasi"}>
            <Head title={location ? "Edit Lokasi" : "Tambah Lokasi"} />
            <Button asChild variant="ghost" className="mb-4">
                <Link href={route("admin.locations.index")}>
                    <ArrowLeft />
                    Kembali
                </Link>
            </Button>
            <Card className="mx-auto max-w-3xl">
                <CardContent>
                    <h1 className="text-2xl font-bold">
                        {location
                            ? "Edit lokasi absensi"
                            : "Tambah lokasi absensi"}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Gunakan koordinat akurat dari titik presensi. Radius
                        awal mengikuti pengaturan aplikasi (
                        {defaults.default_radius_meters ?? 20} meter).
                    </p>
                    <form
                        onSubmit={submit}
                        className="mt-6 grid gap-5 sm:grid-cols-2"
                    >
                        <label className="grid gap-2 text-sm font-medium sm:col-span-2">
                            Nama lokasi
                            <Input
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData("name", e.target.value)
                                }
                                required
                            />
                            {error("name")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium sm:col-span-2">
                            Alamat
                            <textarea
                                className="min-h-24 rounded-lg border bg-white p-3"
                                value={form.data.address}
                                onChange={(e) =>
                                    form.setData("address", e.target.value)
                                }
                                required
                            />
                            {error("address")}
                        </label>
                        <section className="grid gap-3 rounded-xl border bg-slate-50 p-3 sm:col-span-2 sm:p-4">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h2 className="flex items-center gap-2 font-semibold">
                                        <MapPin className="size-4 text-blue-600" />
                                        Pilih titik pada peta
                                    </h2>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Klik peta atau geser penanda biru. Lingkaran menunjukkan radius presensi.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={useCurrentLocation}
                                    disabled={isLocating}
                                >
                                    {isLocating ? (
                                        <LoaderCircle className="animate-spin" />
                                    ) : (
                                        <Crosshair />
                                    )}
                                    Gunakan lokasi saya
                                </Button>
                            </div>
                            <div className="flex gap-2">
                                <Input
                                    value={mapSearch}
                                    onChange={(event) =>
                                        setMapSearch(event.target.value)
                                    }
                                    onKeyDown={(event) => {
                                        if (event.key === "Enter") {
                                            event.preventDefault();
                                            void searchLocation();
                                        }
                                    }}
                                    placeholder="Cari gedung atau alamat..."
                                    aria-label="Cari lokasi pada peta"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => void searchLocation()}
                                    disabled={isSearching}
                                >
                                    {isSearching ? (
                                        <LoaderCircle className="animate-spin" />
                                    ) : (
                                        <Search />
                                    )}
                                    <span className="hidden sm:inline">Cari</span>
                                </Button>
                            </div>
                            {mapResults.length > 0 && (
                                <div className="grid gap-2 rounded-lg border bg-white p-2">
                                    <p className="px-2 text-xs font-semibold text-slate-700">
                                        Pilih hasil yang paling sesuai:
                                    </p>
                                    {mapResults.map((result) => (
                                        <button
                                            key={result.place_id}
                                            type="button"
                                            className="flex items-start gap-2 rounded-md px-2 py-2 text-left text-xs leading-relaxed text-slate-600 transition hover:bg-blue-50 hover:text-blue-800"
                                            onClick={() =>
                                                chooseSearchResult(result)
                                            }
                                        >
                                            <MapPin className="mt-0.5 size-4 shrink-0 text-blue-600" />
                                            {result.display_name}
                                        </button>
                                    ))}
                                </div>
                            )}
                            <LocationPickerMap
                                center={mapCenter}
                                radius={Number(form.data.radius_meters) || 20}
                                onChange={selectCoordinates}
                            />
                            <p
                                className="min-h-5 text-xs text-muted-foreground"
                                aria-live="polite"
                            >
                                {mapMessage ||
                                    "Peta menggunakan data OpenStreetMap. Pastikan penanda berada tepat di titik presensi."}
                            </p>
                        </section>
                        <label className="grid gap-2 text-sm font-medium">
                            Latitude
                            <Input
                                type="number"
                                step="0.0000001"
                                min="-90"
                                max="90"
                                value={form.data.latitude}
                                onChange={(e) =>
                                    form.setData("latitude", e.target.value)
                                }
                                placeholder="-6.1234567"
                                required
                            />
                            {error("latitude")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Longitude
                            <Input
                                type="number"
                                step="0.0000001"
                                min="-180"
                                max="180"
                                value={form.data.longitude}
                                onChange={(e) =>
                                    form.setData("longitude", e.target.value)
                                }
                                placeholder="106.1234567"
                                required
                            />
                            {error("longitude")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Radius (meter)
                            <Input
                                type="number"
                                min="5"
                                max="5000"
                                value={form.data.radius_meters}
                                onChange={(e) =>
                                    form.setData(
                                        "radius_meters",
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            {error("radius_meters")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium">
                            Akurasi GPS maksimum (meter)
                            <Input
                                type="number"
                                min="5"
                                max="500"
                                value={form.data.max_accuracy_meters}
                                onChange={(e) =>
                                    form.setData(
                                        "max_accuracy_meters",
                                        e.target.value,
                                    )
                                }
                                required
                            />
                            {error("max_accuracy_meters")}
                        </label>
                        <label className="grid gap-2 text-sm font-medium sm:col-span-2">
                            Status
                            <select
                                className="h-11 rounded-lg border bg-white px-3"
                                value={form.data.is_active ? "1" : "0"}
                                onChange={(e) =>
                                    form.setData(
                                        "is_active",
                                        e.target.value === "1",
                                    )
                                }
                            >
                                <option value="0">Nonaktif</option>
                                <option value="1">Aktif</option>
                            </select>
                            {error("is_active")}
                        </label>
                        <div className="flex justify-end sm:col-span-2">
                            <Button disabled={form.processing}>
                                {form.processing ? (
                                    <LoaderCircle className="animate-spin" />
                                ) : (
                                    <Save />
                                )}
                                Simpan lokasi
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
