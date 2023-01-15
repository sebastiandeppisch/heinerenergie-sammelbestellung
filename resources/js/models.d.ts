/**
 * This file is auto generated using 'php artisan typescript:generate'
 *
 * Changes to this file will be lost when the command is run again
 */

declare namespace App.Models {
    export interface ProductDownload {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        name: string;
        url: string;
        product_id: number;
        product?: App.Models.Product | null;
    }

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
        bulk_order_id: number;
        order_items?: Array<App.Models.OrderItem> | null;
        advisor?: App.Models.User | null;
        bulk_order?: App.Models.BulkOrder | null;
        shares?: Array<App.Models.User> | null;
        order_items_count?: number | null;
        shares_count?: number | null;
        readonly price?: number;
        readonly panels_count?: number;
        readonly street_with_number?: string;
        readonly name?: any;
        readonly archived?: boolean;
        readonly shares_ids?: Array<any>;
    }

    export interface BulkOrder {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        name: string;
        archived: boolean;
        orders?: Array<App.Models.Order> | null;
        products?: Array<App.Models.Product> | null;
        product_categories?: Array<App.Models.ProductCategory> | null;
        orders_count?: number | null;
        products_count?: number | null;
        product_categories_count?: number | null;
    }

    export interface Product {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        name: string;
        description: string | null;
        price: number;
        sku: string | null;
        panelsCount: number;
        product_category_id: number | null;
        bulk_order_id: number;
        is_supplier_product: boolean;
        order_items?: Array<App.Models.OrderItem> | null;
        product_category?: App.Models.ProductCategory | null;
        downloads?: Array<App.Models.ProductDownload> | null;
        bulk_order?: App.Models.BulkOrder | null;
        order_items_count?: number | null;
        downloads_count?: number | null;
    }

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

    export interface ProductCategory {
        id: number;
        created_at: string | null;
        updated_at: string | null;
        name: string;
        bulk_order_id: number;
        products?: Array<App.Models.Product> | null;
        bulk_order?: App.Models.BulkOrder | null;
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
        is_admin: boolean;
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
