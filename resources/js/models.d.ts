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
        commentary: string | null;
        advisor_id: number;
        checked: boolean;
        order_items?: Array<App.Models.OrderItem> | null;
        advisor?: App.Models.User | null;
        order_items_count?: number | null;
        readonly price?: number;
        readonly panels_count?: number;
        readonly street_with_number?: string;
        readonly name?: any;
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
        product_category_id: number | null;
        product_category?: App.Models.ProductCategory | null;
    }

    export interface Setting {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        name: string;
        key: string;
        value: string;
        type: string;
    }

    export interface ProductCategory {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        name: string;
        products?: Array<App.Models.Product> | null;
        products_count?: number | null;
    }

    export interface User {
        id: number;
        email: string;
        email_verified_at: string | null;
        password: string;
        remember_token: string | null;
        created_at: string | null;
        updated_at: string | null;
        first_name: string;
        last_name: string;
        orders?: Array<App.Models.Order> | null;
        orders_count?: number | null;
        readonly name?: any;
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
