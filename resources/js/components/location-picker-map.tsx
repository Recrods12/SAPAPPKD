import "leaflet/dist/leaflet.css";
import L, { type LeafletMouseEvent, type Marker as LeafletMarker } from "leaflet";
import { useEffect, useMemo } from "react";
import {
    Circle,
    MapContainer,
    Marker,
    TileLayer,
    useMap,
    useMapEvents,
} from "react-leaflet";

type Coordinates = [number, number];

function MapController({ center }: { center: Coordinates }) {
    const map = useMap();

    useEffect(() => {
        map.flyTo(center, Math.max(map.getZoom(), 17));
    }, [center, map]);

    return null;
}

function SelectableMarker({
    position,
    onChange,
}: {
    position: Coordinates;
    onChange: (coordinates: Coordinates) => void;
}) {
    const icon = useMemo(
        () =>
            L.divIcon({
                className: "",
                html: '<span class="block h-6 w-6 rounded-full border-4 border-white bg-blue-600 shadow-lg ring-2 ring-blue-600"></span>',
                iconAnchor: [12, 12],
                iconSize: [24, 24],
            }),
        [],
    );

    useMapEvents({
        click(event: LeafletMouseEvent) {
            onChange([event.latlng.lat, event.latlng.lng]);
        },
    });

    return (
        <Marker
            draggable
            eventHandlers={{
                dragend(event) {
                    const marker = event.target as LeafletMarker;
                    const coordinates = marker.getLatLng();
                    onChange([coordinates.lat, coordinates.lng]);
                },
            }}
            icon={icon}
            position={position}
        />
    );
}

export function LocationPickerMap({
    center,
    radius,
    onChange,
}: {
    center: Coordinates;
    radius: number;
    onChange: (coordinates: Coordinates) => void;
}) {
    return (
        <MapContainer
            center={center}
            zoom={17}
            scrollWheelZoom
            className="h-80 w-full rounded-xl sm:h-96"
        >
            <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                maxZoom={19}
                subdomains="abc"
                url="https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png"
            />
            <MapController center={center} />
            <Circle
                center={center}
                radius={radius}
                pathOptions={{
                    color: "#2563eb",
                    fillColor: "#3b82f6",
                    fillOpacity: 0.14,
                    weight: 2,
                }}
            />
            <SelectableMarker position={center} onChange={onChange} />
        </MapContainer>
    );
}
