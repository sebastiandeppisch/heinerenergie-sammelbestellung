declare namespace App.Data {
export type AdviceEventData = {
id: string;
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
id: string;
name: string;
result: any;
group_id: string;
visible_in_group: boolean;
};
export type AdviceStatusNamesData = {
id: string;
name: string;
result: any;
};
export type DataProtectedAdviceData = {
id: string;
firstName: string;
lastName: string;
street: string;
streetNumber: string;
zip: string;
city: string;
email: string;
phone: string;
commentary: string;
advisor_id: string;
advice_status_id: string;
lng: number;
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
group_id: string;
};
export type GroupData = {
users_count: number;
advices_count: number;
id: string;
name: string;
description: string;
logo_path: string;
parent_id: string;
accepts_transfers: boolean;
userCanActAsAdmin: boolean;
};
export type GroupMapData = {
polygon: App.ValueObjects.Polygon;
center: App.ValueObjects.Coordinate;
name: string;
logo_path: string;
};
export type GroupUserData = {
id: string;
name: string;
email: string;
is_admin: boolean;
};
}
declare namespace App.Data.Pages {
export type GroupsIndexData = {
groups: Array<App.Data.GroupData>;
canCreateRootGroup: boolean;
selectedGroup: App.Data.GroupData;
polygon: App.ValueObjects.Polygon;
canEditGroup: boolean;
canCreateGroups: boolean;
};
}
declare namespace App.ValueObjects {
export type Coordinate = {
lat: number;
lng: number;
};
export type Polygon = {
coordinates: Array<App.ValueObjects.Coordinate>;
};
}
