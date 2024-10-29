export default [
  {
    text: "Dashboard",
    path: "/dashboard",
    icon: "info",
    admin: false
  },
  {
    text: "Neue Bestellung",
    path: "/neworder",
    icon: "cart",
    admin: false
  },
  {
    text: "Beratungen",
    icon: "group",
    admin: false,
    items: [
      {
        text: "Tabelle",
        path: "/advices",
        icon: "tableproperties"
      },
      {
        text: "Karte",
        path: "/advicesmap",
        icon: "map"
      }
    ]
  },
  {
    text: "Bestellungen",
    path: "/orders",
    icon: "textdocument",
    admin: false
  },
  {
    text: "Artikel",
    path: "/products",
    icon: "box",
    admin: true
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
  }
];
