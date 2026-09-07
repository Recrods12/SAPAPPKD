import { useState } from "react";

const storageKey = "sapa-ppkd-device-token";

function createToken(): string {
    return `${crypto.randomUUID()}${crypto.randomUUID()}`;
}

export function useDeviceToken(): string {
    const [token] = useState(() => {
        const existing = window.localStorage.getItem(storageKey);

        if (existing) {
            return existing;
        }

        const generated = createToken();
        window.localStorage.setItem(storageKey, generated);

        return generated;
    });

    return token;
}
