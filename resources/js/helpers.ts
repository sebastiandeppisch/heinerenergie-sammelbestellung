import { reactive } from '@vue/reactivity';
import { AxiosError } from 'axios';
import notify from 'devextreme/ui/notify';
import moment from 'moment';
import { onMounted, onUnmounted, Ref } from 'vue';

function formatPriceCell(cell: { value: number | string }): string {
    return formatPrice(parseFloat(cell.value.toString()));
}

function formatPrice(price: number): string {
    return price.toFixed(2).replace('.', ',') + ' €';
}

export interface LaravelValidationError {
    errors: {
        [key: string]: Array<string>;
    };
}

function notifyError(error: AxiosError<LaravelValidationError>): void {
    if (error.response && error.response.status === 422) {
        let validationErrors: Array<String> = [];
        for (const prop in error.response.data.errors) {
            validationErrors = validationErrors.concat(error.response.data.errors[prop] as Array<String>);
        }
        notify(validationErrors.join(','), 'error');
    } else {
        notify(error, 'error');
    }
}

function formatDateCell(row: { value: Date }): string {
    return moment(row.value).format('DD.MM.YY');
}

function useOnResize(callback: () => void) {
    return {
        onOnMounted: onMounted(() => {
            callback();
            window.addEventListener('resize', callback);
        }),
        onOnUnmounted: onUnmounted(() => {
            window.removeEventListener('resize', callback);
        }),
    };
}

class AdaptTableHeight {
    private ref: Ref;
    private r;
    constructor(ref: Ref) {
        this.ref = ref;
        this.r = reactive({
            height: 450,
        });
        window.addEventListener('resize', () => {
            this.calcHeight();
        });
    }

    calcHeight() {
        if (this.ref.value) {
            const vh = window.innerHeight;
            const dom: HTMLElement = this.ref.value;
            const footerHeight = 250;
            let height = vh - dom.offsetTop - footerHeight;
            if (height < 450) {
                height = 450;
            }
            this.r.height = height;
        }
    }

    getReactive() {
        return this.r;
    }
}

const isIframe = window.self !== window.top;

export { AdaptTableHeight, formatDateCell, formatPrice, formatPriceCell, isIframe, notifyError, useOnResize };
