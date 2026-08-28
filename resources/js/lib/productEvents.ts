import { store as storeProductEvent } from '@/actions/App/Http/Controllers/ProductEventController';

/** Event names the server accepts from the browser. */
export type ClientProductEventName =
    | 'badge_snippet_copied'
    | 'card_link_copied'
    | 'manifest_link_copied'
    | 'share_text_copied'
    | 'share_intent_clicked'
    | 'collection_project_clicked';

export interface ProductEventPayload {
    projectId?: number;
    collectionId?: number;
    network?: 'x' | 'linkedin' | 'reddit';
}

function readXsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match === null ? '' : decodeURIComponent(match[1]);
}

/**
 * Records roadmap evidence (asset copies, share intent clicks) as a
 * fire-and-forget beacon. Failure is silent: evidence must never disturb
 * the builder's UX.
 */
export function recordProductEvent(
    name: ClientProductEventName,
    payload: ProductEventPayload = {},
): void {
    const { url, method } = storeProductEvent();

    void fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': readXsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            name,
            project_id: payload.projectId,
            collection_id: payload.collectionId,
            network: payload.network,
        }),
        keepalive: true,
    }).catch(() => {
        // Intentionally swallowed.
    });
}
