import { withImageChanges } from '@/components/FormBuilder/storedImages';
import { describe, expect, it } from 'vitest';

const stored = {
    photo: [
        { name: 'a.jpg', url: '/a' },
        { name: 'b.jpg', url: '/b' },
    ],
};

describe('withImageChanges', () => {
    it('leaves forms without stored images as they are', () => {
        const data = { photo: [], title: 'Garten' };

        expect(withImageChanges(data, {})).toBe(data);
    });

    it('uploads only new files and sends the removed stored images by name', () => {
        const file = new File(['x'], 'neu.jpg', { type: 'image/jpeg' });

        expect(withImageChanges({ photo: ['b.jpg', file] }, stored)).toEqual({ photo: [file], _removed_images: { photo: ['a.jpg'] } });
    });

    it('removes all stored images when the field is emptied', () => {
        expect(withImageChanges({ photo: [] }, stored)).toEqual({ photo: [], _removed_images: { photo: ['a.jpg', 'b.jpg'] } });
    });

    it('keeps the images of a hidden field', () => {
        expect(withImageChanges({ title: 'Garten' }, stored)).toEqual({ title: 'Garten', _removed_images: {} });
    });
});
