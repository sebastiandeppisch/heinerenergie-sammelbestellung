<template>
  <h2 class="content-block">Bestellung gespeichert</h2>
    <p>Deine Bestellung wurde gespeichert und wird bald an unseren Lieferanten weitergeleitet. Eine Bestätigung wurde an Deine E-Mail Adresse gesendet.</p>

    <div style="display: flex;flex-flow:wrap;">
      <div class="dx-card" style="min-width: 350px;">
        <div class="dx-fieldset">
           <div class="dx-fieldset-header">Kontakt-Daten</div>
          <div class="dx-field">
            <div class="dx-field-label">Name</div>
            <div class="dx-field-value">{{order.firstName}} {{order.lastName}}</div>
          </div>

          <div class="dx-field">
            <div class="dx-field-label">Adresse</div>
            <div class="dx-field-value">{{order.street}} {{order.streetNumber}}<br> {{order.zip}} {{order.city}}</div>
          </div>

          <div class="dx-field">
            <div class="dx-field-label">Kontakt</div>
            <div class="dx-field-value">
              <div><i class="dx-icon-email"></i> {{order.email}} </div> 
              <div><i class="dx-icon-tel"></i> {{order.phone}}</div>
            </div>
          </div>
          <div class="dx-field">
            <div class="dx-field-label">Kommentar</div>
            <div class="dx-field-value">
              {{order.commentary}}
            </div>
          </div>
        </div>
      </div>
      <div class="dx-card" style="min-width: 350px;">
        <div class="dx-fieldset">
          <div class="dx-fieldset-header">Bestellung</div>
          <table style="width: 100%">
            <thead>
              <tr>
                <th style="text-align: left">Artikel</th>
                <th style="text-align: right">Anzahl</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="productItem in order.order_items">
                <td>{{productItem.product.name}}</td>
                <td style="text-align: right">{{productItem.quantity}}</td>
              </tr>
            </tbody>

          </table>
          <div class="dx-field">
            <div class="dx-field-label">Gesamtpreis</div>
            <div class="dx-field-value-static">
              {{formatPrice(order.price)}}
            </div>
          </div>
        </div>
      </div>
      
    </div>
</template>

<script setup lang="ts">
import {formatPrice} from './../helpers'

interface Props {
  order: App.Models.Order
}
const {order} = defineProps<Props>();
</script>
<style scoped>
</style>