/**
 * This file is auto generated using 'php artisan typescript:generate'
 *
 * Changes to this file will be lost when the command is run again
 */

declare namespace App.Models {
    export interface Order {
        id: number;
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
        order_items?: Array<App.Models.OrderItem> | null;
        order_items_count?: number | null;
        readonly price?: number;
    }

    export interface Product {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        name: string;
        description: string | null;
        url: string | null;
        price: number;
        sku: string | null;
        panelsCount: number;
    }

    export interface User {
        id: number;
        name: string;
        email: string;
        email_verified_at: string | null;
        password: string;
        remember_token: string | null;
        created_at: string | null;
        updated_at: string | null;
    }

    export interface OrderItem {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        quantity: number;
        order_id: number;
        product_id: number;
        order?: App.Models.Order | null;
        product?: App.Models.Product | null;
    }

}
