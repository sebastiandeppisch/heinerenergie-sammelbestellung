import type { Mock } from 'vitest';
import { vi } from 'vitest';
import { reactive } from 'vue';

type RequestOptions = {
    onSuccess?: (response: unknown) => void;
    onError?: (errors: Record<string, string>) => void;
    [option: string]: unknown;
};

type RequestMethod = Mock<(url: string, options?: RequestOptions) => unknown>;

/**
 * Stands in for what useForm and useHttp return, limited to what the components use. Requests are
 * recorded instead of sent, and each test decides how they end.
 */
export type FakeRequest = {
    [field: string]: unknown;
    processing: boolean;
    errors: Record<string, string>;
    response: unknown;
    transform: (callback: (data: object) => object) => FakeRequest;
    clearErrors: () => FakeRequest;
    post: RequestMethod;
    put: RequestMethod;
    delete: RequestMethod;
    /** The data the request sends, after the transform callback. */
    sentData: () => object;
};

function createFakeRequest(initialData: object): FakeRequest {
    let transformCallback = (data: object): object => data;

    const request: FakeRequest = reactive({
        ...initialData,
        processing: false,
        errors: {},
        response: null,
        transform: (callback: (data: object) => object) => {
            transformCallback = callback;
            return request;
        },
        clearErrors: () => {
            request.errors = {};
            return request;
        },
        post: vi.fn<(url: string, options?: RequestOptions) => unknown>(),
        put: vi.fn<(url: string, options?: RequestOptions) => unknown>(),
        delete: vi.fn<(url: string, options?: RequestOptions) => unknown>(),
        sentData: () => transformCallback({ ...initialData }),
    });

    return request;
}

/** Every useForm and useHttp call of the mounted components, in the order they were made. */
export const fakeInertia = {
    forms: [] as Array<FakeRequest>,
    httpRequests: [] as Array<FakeRequest>,
};

export function resetFakeInertia(): void {
    fakeInertia.forms.length = 0;
    fakeInertia.httpRequests.length = 0;
}

export function created(requests: Array<FakeRequest>, index = 0): FakeRequest {
    const request = requests[index];

    if (!request) {
        throw new Error(`No fake request number ${index} was created.`);
    }

    return request;
}

/** Replaces useForm and useHttp and keeps the rest of @inertiajs/vue3. Use it in a vi.mock factory. */
export function withFakeForms(original: object): object {
    const remember = (requests: Array<FakeRequest>, data: object) => {
        const request = createFakeRequest(data);
        requests.push(request);

        return request;
    };

    return {
        ...original,
        useForm: (data: object = {}) => remember(fakeInertia.forms, data),
        useHttp: (data: object = {}) => remember(fakeInertia.httpRequests, data),
    };
}

type Outcome = { response: unknown } | { errors: Record<string, string> } | { failure: Error };

/**
 * Ends the next POST of a fake useHttp the way the real one does: a response on success, errors
 * without throwing for a validation failure, and a thrown error for anything else.
 */
export function answerNextPost(request: FakeRequest, outcome: Outcome): void {
    request.post.mockImplementationOnce(async (_url, options) => {
        if ('response' in outcome) {
            request.response = outcome.response;
            options?.onSuccess?.(outcome.response);

            return outcome.response;
        }

        if ('errors' in outcome) {
            request.errors = outcome.errors;
            options?.onError?.(outcome.errors);

            return undefined;
        }

        throw outcome.failure;
    });
}

/** Route names become paths, so tests can tell which route a request went to. */
export const fakeZiggy = {
    route: (name: string, parameter?: string | number): string => (parameter === undefined ? `/${name}` : `/${name}/${parameter}`),
};
