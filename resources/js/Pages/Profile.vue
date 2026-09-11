<script setup lang="ts">
import PinLocationMap from '@/components/PinLocationMap.vue';
import { LaravelValidationError, notifyError } from '@/helpers';
import { Button } from '@/shadcn/components/ui/button';
import { Input } from '@/shadcn/components/ui/input';
import { Label } from '@/shadcn/components/ui/label';
import AdvisorMap from '@/views/AdvisorMap.vue';
import axios, { AxiosError } from 'axios';
import { MapPin, Save } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { route } from 'ziggy-js';
import { user as userData } from '../authHelper';

type Coordinate = App.ValueObjects.Coordinate;

const props = defineProps<{
    advisorMarker: string;
}>();

const user = ref(userData.value);

// Computed properties to convert null to undefined for Input components
const street = computed({
    get: () => user.value.street ?? undefined,
    set: (value) => (user.value.street = value || null),
});

const streetNumber = computed({
    get: () => user.value.street_number ?? undefined,
    set: (value) => (user.value.street_number = value || null),
});

const zip = computed({
    get: () => user.value.zip ?? undefined,
    set: (value) => (user.value.zip = value || null),
});

const city = computed({
    get: () => user.value.city ?? undefined,
    set: (value) => (user.value.city = value || null),
});

const adviceRadius = computed({
    get: () => user.value.advice_radius ?? undefined,
    set: (value) => (user.value.advice_radius = typeof value === 'string' ? Number(value) || null : value || null),
});

type Address = {
    street: string | null;
    street_number: string | null;
    zip: string | null;
    city: string | null;
};

function currentAddress(): Address {
    return {
        street: user.value.street,
        street_number: user.value.street_number,
        zip: user.value.zip,
        city: user.value.city,
    };
}

const savedAddress = ref<Address>(currentAddress());

/**
 * The coordinates belong to the address they were resolved from, so they go
 * stale the moment somebody edits one of the address inputs.
 */
const areCoordinatesDirty = computed<boolean>(() => {
    const address = currentAddress();

    return (Object.keys(address) as (keyof Address)[]).some((field) => address[field] !== savedAddress.value[field]);
});

/**
 * The pin, either resolved through the geocoding endpoint or dropped by hand.
 * Saving no longer geocodes on the server, so an outage of OpenStreetMap can
 * never stop somebody from saving their address.
 */
const coordinate = computed<Coordinate | null>({
    get: () => (user.value.lat !== null && user.value.long !== null ? { lat: user.value.lat, lng: user.value.long } : null),
    set: (value) => {
        user.value.lat = value?.lat ?? null;
        user.value.long = value?.lng ?? null;
    },
});

const isGeocoding = ref(false);
const geocodingMessage = ref<string | null>(null);

const hasAddress = computed<boolean>(() => Boolean(user.value.street && user.value.zip && user.value.city));

async function locateAddress() {
    isGeocoding.value = true;
    geocodingMessage.value = null;

    try {
        const { data } = await axios.post<{ coordinate: Coordinate | null }>(route('api.geocode.address'), currentAddress());

        if (data.coordinate) {
            coordinate.value = data.coordinate;
            geocodingMessage.value = null;
        } else {
            geocodingMessage.value = 'Zu dieser Adresse ist keine Position bekannt. Setze den Punkt bitte selbst auf der Karte.';
        }
    } catch (error) {
        notifyError(error as AxiosError<LaravelValidationError>);
        geocodingMessage.value = 'Die Position konnte nicht ermittelt werden. Du kannst sie selbst auf der Karte setzen.';
    } finally {
        isGeocoding.value = false;
    }
}

function saveAddress() {
    axios
        .post('/api/profile/address', {
            ...currentAddress(),
            advice_radius: user.value.advice_radius,
            // The API stores lng, the user payload calls the same value long.
            lat: user.value.lat,
            lng: user.value.long,
        })
        .then((response) => {
            user.value = response.data;
            savedAddress.value = currentAddress();
            toast.success('Adresse gespeichert');
        })
        .catch(notifyError);
}
</script>

<template>
    <div ref="outer">
        <h2 class="content-block">Profil {{ user.name }}</h2>
        <div style="margin: 30px 40px 30px 40px">
            <div class="flex-row">
                <div class="flex-cell rounded-xl border bg-card text-card-foreground shadow-sm" style="padding: 30px; max-width: 400px">
                    <div class="flex-row">
                        <div class="flex-cell">
                            <span class="label">Beratungsgebiet</span>
                            <br />Trage die Adresse ein, von der aus Du Beraten möchtest, und wie weit Du dafür fahren würdest. Wenn Du Deine genaue
                            Adresse nicht angeben möchtest, kannst Du die Position unten auch von Hand auf der Karte setzen.
                            <div class="flex-row">
                                <div class="flex-cell" style="margin: 10px">
                                    <Label for="street">Straße</Label>
                                    <Input id="street" v-model="street" />
                                </div>
                                <div style="margin: 10px">
                                    <Label for="street_number">Nr.</Label>
                                    <Input id="street_number" v-model="streetNumber" style="width: 100px" />
                                </div>
                            </div>

                            <div class="flex-row">
                                <div style="margin: 10px">
                                    <Label for="zip">PLZ</Label>
                                    <Input id="zip" inputmode="numeric" style="width: 100px" v-model="zip" />
                                </div>
                                <div class="flex-cell" style="margin: 10px">
                                    <Label for="city">Stadt</Label>
                                    <Input id="city" v-model="city" />
                                </div>
                            </div>

                            <div class="flex-row">
                                <div class="flex-cell" style="margin: 10px">
                                    <Label for="advice_radius">Beratungsgebiet (m)</Label>
                                    <Input id="advice_radius" type="number" v-model="adviceRadius" />
                                </div>
                            </div>

                            <div style="margin: 10px">
                                <span class="label" style="font-size: 16px">Position</span>
                                <p class="text-sm text-gray-600" style="margin-bottom: 8px">
                                    Ermittle die Position aus Deiner Adresse oder setze sie selbst auf der Karte. Erst danach speichern.
                                </p>

                                <Button variant="outline" class="w-full" :disabled="!hasAddress || isGeocoding" @click="locateAddress">
                                    <MapPin class="h-4 w-4" />
                                    {{ isGeocoding ? 'Ermittle Position...' : 'Position aus Adresse ermitteln' }}
                                </Button>

                                <p v-if="geocodingMessage" class="text-sm text-amber-600" style="margin-top: 8px">{{ geocodingMessage }}</p>

                                <PinLocationMap v-model="coordinate" style="margin-top: 12px" />

                                <p v-if="!coordinate" class="text-sm text-amber-600" style="margin-top: 8px">
                                    Ohne Position wirst Du nicht über neue Beratungen in Deiner Nähe benachrichtigt, und die Koordination sieht nicht,
                                    wie weit Du fahren würdest.
                                </p>
                            </div>

                            <Button variant="default" @click="saveAddress" class="w-full">
                                <Save class="h-4 w-4" />
                                Beratungsgebiet speichern
                            </Button>

                            <AdvisorMap v-if="!areCoordinatesDirty" :advisor="user" style="padding-top: 30px" :advisor-marker="props.advisorMarker" />
                            <div v-else style="padding-top: 30px">
                                <i>Speichere Dein Beratungsgebiet, damit die Karte aktualisiert wird.</i>
                            </div>
                        </div>
                        <div class="flex-cell" style="display: none"></div>
                    </div>
                </div>
                <div class="flex-cell rounded-xl border bg-card text-card-foreground shadow-sm" style="padding: 30px; display: none"></div>
                <!--  <div class="bg-card text-card-foreground flex-cell rounded-xl border shadow-sm" style="padding:30px;">
          Test
        </div>-->
            </div>
        </div>
    </div>
</template>
<style scoped>
.flex-row {
    flex-direction: row;
    display: flex;
    width: 100%;
}
.flex-cell {
    /*padding: 30px;*/
    flex: 1;
}

.label {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 16px;
}
</style>
