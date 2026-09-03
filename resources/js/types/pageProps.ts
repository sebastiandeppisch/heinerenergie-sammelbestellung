import { PageProps } from '@inertiajs/core';

export interface CustomPageProps extends PageProps {
    auth: {
        user: App.Data.UserData | null;
        currentGroup?: App.Data.GroupBaseData;
        availableGroups?: App.Data.GroupData[];
    };
    theme: {
        primaryHue: number | null;
        primaryLightness: number | null;
        primaryChroma: number | null;
    };
    userRole?: 'user' | 'group-admin' | 'system-admin';
    flashMessages?: {
        [key: string]: string;
    };
    authorizationError?: {
        message: string;
        intendedUrl: string | null;
    } | null;
    appName?: string;
    defaultLogo?: string;
    version?: string;
}
