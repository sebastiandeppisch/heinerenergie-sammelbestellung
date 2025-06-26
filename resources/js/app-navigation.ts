import { route } from "ziggy-js"

export default [
  {
    text: "Dashboard",
    path: "/dashboard",
    icon: "info",
    admin: false
  },
  {
    text: "Beratungen - Tabelle",
    icon: "tableproperties",
    path: "/advices",
    admin: false
  },
  {
    text: "Beratungen - Karte",
    icon: "map",
    path: "/advicesmap",
    admin: false
  },
  {
    text: "Initiativen",
    path: "/groups",
    icon: "group",
    admin: false
  },
  {
    text: "Berater*innen",
    path: "/users",
    icon: "user",
    admin: true
  },
  {
    text: "Einstellungen",
    path: "/settings",
    icon: "preferences",
    admin: true
  },
  {
    text: "Formulare",
    path: "/form-definitions",
    icon: "fields",
    admin: true
  },
  {
    text: "Formulareinträge",
    path: "/form-submissions",
    icon: "message",
    admin: true
  },
  {
    text: 'Kartenpunkte - Tabelle',
    icon: "tableproperties",
    path: '/mappoints',
  },
  {
    text: 'Kartenpunkte - Karte',
    icon: "map",
    path: '/mappoints-map'
  }
];
