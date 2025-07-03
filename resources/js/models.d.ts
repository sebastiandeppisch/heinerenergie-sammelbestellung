/**
 * This file is auto generated using 'php artisan typescript:generate'
 *
 * Changes to this file will be lost when the command is run again
 */

declare namespace App.Models {

    /**
     * @deprecated
     */
    export interface Advice {
        id: string;
        created_at: string | null;
        updated_at: string | null;
        firstName: string;
        lastName: string;
        street: string;
        streetNumber: string;
        zip: number;
        city: string;
        email: string;
        phone: string;
        commentary: string | null;
        advisor_id: string | null;
        long: number | null;
        lat: number | null;
        advice_status_id: number | null;
        type: number;
        placeNotes: string | null;
        houseType: number | null;
        helpType_place: boolean;
        helpType_technical: boolean;
        helpType_bureaucracy: boolean;
        helpType_other: boolean;
        landlordExists: boolean | null;
        advisor?: App.Models.User | null;
        status?: App.Models.AdviceStatus | null;
        shares?: Array<App.Models.User> | null;
        shares_count?: number | null;
        result: number;
        readonly distance?: number | null;
        readonly shares_ids: Array<any>;
        readonly can_edit: boolean;
    }

    /**
     * @deprecated
     */
    export interface AdviceStatus {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        name: string;
        advices?: App.Models.Advice | null;
    }

    /**
     * @deprecated
     */
    export interface Setting {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        name: string;
        key: string;
        value: string;
        type: string;
        permission: string;
    }
}
