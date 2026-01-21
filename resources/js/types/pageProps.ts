import { PageProps } from '@inertiajs/core';

export interface CustomPageProps extends PageProps {
    auth: {
        user: App.Data.UserData | null;
        currentGroup?: App.Data.GroupBaseData;
        availableGroups?: App.Data.GroupData[];
    };
    userRole?: 'user' | 'group-admin' | 'system-admin';
    flashMessages?: {
        [key: string]: string;
    };
    appName?: string;
    defaultLogo?: string;
}
