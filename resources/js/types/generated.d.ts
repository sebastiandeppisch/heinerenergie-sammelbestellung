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
visible_in_group: boolean;
};
export type AdviceStatusNamesData = {
id: number;
name: string;
result: any;
};
export type DataProtectedAdviceData = {
id: number;
firstName: string;
lastName: string;
street: string;
streetNumber: string;
zip: string;
city: string;
email: string;
phone: string;
commentary: string;
advisor_id: number;
advice_status_id: number;
long: number;
lat: number;
type: any;
created_at: any;
updated_at: any;
distance: number;
shares_ids: Array<any>;
placeNotes: string;
houseType: any;
landlordExists: boolean;
helpType_place: string;
helpType_technical: string;
helpType_bureaucracy: string;
helpType_other: string;
result: any;
can_edit: boolean;
group_id: number;
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
