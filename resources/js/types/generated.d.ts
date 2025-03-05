declare namespace App.Data {
export type AdviceEventData = {
id: number;
description: string;
comment: string;
created_at: string;
user_name: string;
initials: string;
type: string;
subject: string;
content: string;
to: string;
};
export type AdviceStatusData = {
id: number;
name: string;
result: any;
group_id: number;
created_at: string;
updated_at: string;
visible_in_group: boolean;
};
export type GroupData = {
users_count: number;
advices_count: number;
id: number;
name: string;
description: string;
logo_path: string;
parent_id: number;
accepts_transfers: boolean;
userCanActAsAdmin: boolean;
};
export type GroupMapData = {
polygon: any;
center: any;
name: string;
logo_path: string;
};
export type GroupUserData = {
id: number;
name: string;
email: string;
is_admin: boolean;
};
}
