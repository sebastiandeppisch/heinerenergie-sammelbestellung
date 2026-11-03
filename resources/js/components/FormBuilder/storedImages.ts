import type { InjectionKey, Ref } from 'vue';

/** Images a field already holds, keyed by the id of the form field. Follow-up forms show them so they can be kept or removed. */
export const storedImagesKey: InjectionKey<Ref<Record<string, App.Data.ImageData[]>>> = Symbol('storedImages');

/**
 * Image fields hold the file names of kept images and new files. Only new files are uploaded, the stored images
 * the person removed are sent by name.
 */
export function withImageChanges(data: Record<string, any>, storedImages: Record<string, App.Data.ImageData[]>): Record<string, any> {
    if (Object.keys(storedImages).length === 0) {
        return data;
    }

    const removed: Record<string, string[]> = {};
    const result = { ...data };

    for (const [fieldId, images] of Object.entries(storedImages)) {
        if (!(fieldId in result)) {
            continue;
        }

        const entries: Array<File | string> = Array.isArray(result[fieldId]) ? result[fieldId] : [];
        removed[fieldId] = images.map((image) => image.name).filter((name) => !entries.includes(name));
        result[fieldId] = entries.filter((entry) => entry instanceof File);
    }

    return { ...result, _removed_images: removed };
}
