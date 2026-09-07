import { useState } from "react";
export interface Position {
    latitude: number;
    longitude: number;
    accuracy: number;
    sampledAt: number;
}
export function useGeolocation() {
    const [position, setPosition] = useState<Position | null>(null);
    const [status, setStatus] = useState<
        "idle" | "loading" | "ready" | "error"
    >("idle");
    const [error, setError] = useState("");
    function request(targetAccuracy = 35): Promise<Position> {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                const message = "Perangkat tidak mendukung GPS.";
                setError(message);
                setStatus("error");
                reject(new Error(message));
                return;
            }

            let bestPosition: Position | null =
                position && Date.now() - position.sampledAt <= 60_000
                    ? position
                    : null;
            let isFinished = false;
            setError("");
            setStatus("loading");

            const finish = () => {
                if (isFinished) return;
                isFinished = true;
                navigator.geolocation.clearWatch(watchId);
                window.clearTimeout(timeoutId);

                if (bestPosition) {
                    setPosition(bestPosition);
                    setStatus("ready");
                    resolve(bestPosition);
                    return;
                }

                const message =
                    "Lokasi tidak dapat ditemukan. Coba kembali di area terbuka.";
                setError(message);
                setStatus("error");
                reject(new Error(message));
            };

            const watchId = navigator.geolocation.watchPosition(
            (result) => {
                const current = {
                    latitude: result.coords.latitude,
                    longitude: result.coords.longitude,
                    accuracy: result.coords.accuracy,
                    sampledAt: result.timestamp || Date.now(),
                };
                if (
                    !bestPosition ||
                    current.accuracy < bestPosition.accuracy
                ) {
                    bestPosition = current;
                    setPosition(current);
                }

                if (current.accuracy <= targetAccuracy) {
                    finish();
                }
            },
            (failure) => {
                if (failure.code === 1) {
                    isFinished = true;
                    window.clearTimeout(timeoutId);
                    const message =
                        "Izin lokasi ditolak. Aktifkan lokasi pada pengaturan browser.";
                    setError(message);
                    setStatus("error");
                    reject(new Error(message));
                    return;
                }

                finish();
            },
                {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0,
                },
            );
            const timeoutId = window.setTimeout(finish, 10000);
        });
    }
    return { position, status, error, request };
}
