import "leaflet/dist/leaflet.css";
import { Circle, CircleMarker, MapContainer, TileLayer } from "react-leaflet";

export function LocationMap({
    center,
    participant,
    radius,
}: {
    center: [number, number];
    participant?: [number, number];
    radius: number;
}) {
    return (
        <MapContainer
            center={center}
            zoom={19}
            scrollWheelZoom={false}
            className="h-56 w-full rounded-xl"
        >
            <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                maxZoom={19}
                subdomains="abc"
                url="https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png"
            />
            <Circle center={center} radius={radius} pathOptions={{ color: "#2563eb", fillOpacity: 0.12 }} />
            <CircleMarker center={center} radius={7} pathOptions={{ color: "#1d4ed8", fillColor: "#2563eb", fillOpacity: 1 }} />
            {participant && (
                <CircleMarker center={participant} radius={7} pathOptions={{ color: "#047857", fillColor: "#10b981", fillOpacity: 1 }} />
            )}
        </MapContainer>
    );
}
