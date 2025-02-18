declare namespace App.Data {
export type GroupData = {
users_count: number;
advices_count: number;
id: number;
name: string;
description: string;
logo_path: string;
parent_id: number;
accepts_transfers: boolean;
};
export type GroupUserData = {
id: number;
name: string;
email: string;
is_admin: boolean;
};
}
