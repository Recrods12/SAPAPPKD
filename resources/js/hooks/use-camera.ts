import { useEffect, useRef, useState } from "react";
export function useCamera() {
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const [status, setStatus] = useState<
        "idle" | "loading" | "ready" | "error"
    >("idle");
    const [error, setError] = useState("");
    async function start() {
        try {
            setStatus("loading");
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "user",
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
            }
            setStatus("ready");
        } catch {
            setError(
                "Kamera tidak dapat digunakan. Izinkan akses kamera pada browser.",
            );
            setStatus("error");
        }
    }
    async function capture(): Promise<File | null> {
        const video = videoRef.current;
        if (!video || !video.videoWidth) return null;
        const maxWidth = 1280;
        const scale = Math.min(1, maxWidth / video.videoWidth);
        const canvas = document.createElement("canvas");
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        canvas
            .getContext("2d")
            ?.drawImage(video, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, "image/jpeg", 0.82),
        );
        return blob
            ? new File([blob], `attendance-${Date.now()}.jpg`, {
                  type: "image/jpeg",
              })
            : null;
    }
    useEffect(
        () => () =>
            streamRef.current?.getTracks().forEach((track) => track.stop()),
        [],
    );
    return { videoRef, status, error, start, capture };
}
