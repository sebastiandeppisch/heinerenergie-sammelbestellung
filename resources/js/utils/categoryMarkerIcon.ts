import markerIconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import markerIconUrl from 'leaflet/dist/images/marker-icon.png';
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png';

type PointTuple = [number, number];

export type MarkerIconProps = {
    iconUrl: string;
    iconRetinaUrl?: string;
    iconSize: PointTuple;
    iconAnchor?: PointTuple;
    popupAnchor?: PointTuple;
    shadowUrl?: string;
    shadowSize?: PointTuple;
};

/**
 * Props for an `<LIcon>` that is always rendered, so markers never lose their icon.
 *
 * Rendering the icon conditionally (`<LIcon v-if>`) leaves a comment vnode in the marker slot.
 * vue-leaflet 0.10 only recognises comments as `Symbol(Comment)`, but current Vue names them
 * `Symbol(v-cmt)`, so the marker treats the slot as custom HTML and shows an empty div icon.
 * Categories without an image therefore get Leaflet's default marker instead.
 */
export function categoryMarkerIcon(imagePath: string | null | undefined): MarkerIconProps {
    if (imagePath) {
        return { iconUrl: imagePath, iconSize: [50, 50] };
    }

    return {
        iconUrl: markerIconUrl,
        iconRetinaUrl: markerIconRetinaUrl,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: markerShadowUrl,
        shadowSize: [41, 41],
    };
}
