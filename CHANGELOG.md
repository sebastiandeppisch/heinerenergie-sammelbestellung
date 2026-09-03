# Changelog

## [2026-09.1] - 2026-09-03

### Neue Funktionen

#### Für Administratoren

- **Beratungsgebiet aus Postleitzahlen erstellen.** Statt das Gebiet einer Gruppe von Hand auf der Karte zu zeichnen, können einfach die Postleitzahlen eingegeben werden – aneinandergrenzende Postleitzahlgebiete werden zu einem Gebiet zusammengefasst. Die Postleitzahlen bleiben gespeichert und lassen sich jederzeit ergänzen oder entfernen. Ein bestehendes Gebiet kann außerdem nachträglich Eckpunkt für Eckpunkt angepasst werden und wird in der Farbe der Gruppe dargestellt.
- **Karten-Einbettung einer Gruppe zuordnen.** Jede eingebettete Karte gehört zu einer Gruppe und wird in deren Farbe dargestellt – auch auf der öffentlich eingebundenen Seite. #58
- **Seitenverhältnis der Einbettung einstellen.** Für jede Einbettung lässt sich aus mehreren Vorgaben wählen, wie hoch der Inhalt im Verhältnis zur Breite dargestellt wird. Karte und Tabelle nutzen dasselbe Verhältnis, sodass die Höhe beim Umschalten zwischen beiden Ansichten gleich bleibt; die Tabelle füllt den Bereich jetzt vollständig aus. Die Vorschau beim Bearbeiten zeigt das gewählte Verhältnis direkt an. #58
- **Neue Gruppen sind sofort einsatzbereit.** Beim Anlegen einer Gruppe werden die Standard-Beratungsstatus automatisch angelegt und können anschließend verfeinert werden.
- **Einfachere Erstinbetriebnahme.** Das erste registrierte Benutzerkonto wird automatisch Administrator der Standardgruppe.
- **Fehlende Formular-Zuordnungen werden benannt.** Ist ein Formularziel unvollständig, steht jetzt direkt dabei, welche Zuordnungen noch fehlen, statt nur „Unvollständig“ anzuzeigen.

#### Für Berater:innen

- **Verständliche Meldung bei fehlenden Rechten.** Statt einer nackten Fehlerseite erscheint ein Hinweis, warum der Zugriff nicht möglich ist – zusammen mit der Möglichkeit, direkt in eine Rolle zu wechseln, die den Zugriff erlaubt.
- **Beratungstabelle und Karte füllen den Bildschirm.** Beide nutzen die volle Fensterhöhe, ohne dass die Seite zusätzlich scrollt, und passen sich beim Verkleinern des Fensters oder Einklappen der Seitenleiste korrekt an.
- **Klarere Anzeige des eigenen Beratungsgebiets.** Wird die Adresse im Profil geändert, weist ein Hinweis darauf hin, dass das Gebiet erst gespeichert werden muss, damit die Karte aktuell ist. Die Karte passt ihren Ausschnitt außerdem an das Beratungsgebiet an.

### Fehlerbehebungen

- Optionale Formularfelder konnten nicht leer abgeschickt werden – Formulare mit unausgefüllten optionalen Feldern wurden abgelehnt. Eine leere Adresse wird jetzt akzeptiert und als „Keine Adresse angegeben“ dargestellt.
- Beim Anlegen einer neuen Beratung ließ sich der Status nicht setzen.
- Beim Anlegen einer neuen Beratung ließen sich die Auswahlfelder nicht per Mausklick bedienen, sondern nur über die Tabulatortaste erreichen.
- Im eigenen Profil wird auf der Karte jetzt das Symbol der eigenen Gruppe als Markierung angezeigt statt des Standardsymbols.
- Lange Beratungstitel werden jetzt abgekürzt, statt aus der Ansicht zu laufen.
- Ein Beratungsgebiet lässt sich auch dann zeichnen, wenn die Karte noch geladen wird.
- Die Anwendung funktioniert wieder fehlerfrei, wenn keine Nextcloud-Verbindung eingerichtet ist.
- Schlägt das Lösen einer Nextcloud-Ordnerverknüpfung fehl, wird der Grund angezeigt, statt dass der Klick wirkungslos erscheint.
- Das Speichern einer Beratung funktioniert wieder als normale:r Benutzer:in; auch die Dashboard-Diagramme werden wieder zuverlässig aus dem Zwischenspeicher geladen.

## [2026-08.1] - 2026-08-19

### Neue Funktionen

#### Für Administratoren

- **Checklisten-Vorlagen verwalten.** Gruppenadministratoren können Checklisten-Vorlagen erstellen und pflegen, die Berater:innen in ihren Beratungen nutzen. #49
- **Nextcloud-Benutzerabgleich.** Benutzer aus einer Nextcloud-Gruppe können abgeglichen und übernommen werden, sodass bestehende Nextcloud-Konten ohne erneute Dateneingabe importiert werden. #54
- **Berater:innen deaktivieren.** Ein Benutzerkonto kann deaktiviert werden; deaktivierte Benutzer können sich nicht mehr anmelden und werden gebeten, sich an einen Administrator zu wenden. #11
- **Individuelle Farbanpassung pro Gruppe.** Jede Gruppe kann ihre eigene Primärfarbe festlegen, die in der gesamten Oberfläche angewendet wird. #57
- **Neue Dashboard-Diagramme.** Ein Diagramm zur monatlichen Anzahl der Beratungen, ein Diagramm zur Statusverteilung im Zeitverlauf sowie ein Diagramm zur aktuellen Statusverteilung sorgen für einen besseren Überblick.
- **Öffentliche Karte zum Einbetten.** Karten-Einbettungen lassen sich anlegen und benennen: Startausschnitt und Zoomstufe festlegen, auswählen welche Kategorien von Kartenpunkten angezeigt werden, das Ergebnis direkt in der Vorschau prüfen und anschließend den fertigen Einbettungs-Code per Klick kopieren, um die Karte auf der eigenen Webseite einzubinden. Die eingebettete Karte passt sich automatisch der Breite der Webseite an. #58
- **Durchsuchbare Tabellenansicht der Kartenpunkte.** Zusätzlich zur Karte kann pro Einbettung eine Tabelle aktiviert werden, zwischen der Besucher:innen umschalten können – mit Suche, Filtern je Spalte und Filter nach Kategorie. #58
- **Ortsangabe zu Kartenpunkten.** Zu jedem Kartenpunkt kann ein lesbarer Ort hinterlegt werden, der beim Bearbeiten automatisch aus den Koordinaten vorgeschlagen wird und in der Tabellenansicht erscheint. #58
- **Kartenpunkt-Kategorien für Gruppenadministratoren.** Kategorien von Kartenpunkten können jetzt auch von Administrator:innen der eigenen Gruppe verwaltet werden, nicht mehr nur von Systemadministrator:innen.

#### Für Berater:innen

- **Checklisten in Beratungen.** Vorgefertigte Checklisten können während einer Beratung genutzt und ausgefüllt werden. Einmal in einer Beratung angelegt, bleiben sie unverändert, auch wenn die Vorlage später bearbeitet wird. #49
- **Nextcloud-Dateien in Beratungen.** Nextcloud-Ordner direkt aus einer Beratung heraus durchsuchen, verknüpfen und anlegen sowie Dateien hochladen. #52 #55
- **Mail-Konto verbinden und Beratungs-E-Mails lesen und schreiben.** Das eigene Mail-Konto kann verbunden werden. Die gesamte Kommunikation mit dem/der Klient:in wird angezeigt – alle E-Mails an und von der Beratungs-E-Mail-Adresse – und E-Mails lassen sich direkt aus der Anwendung schreiben und versenden. #56
- **Karten- und Tabellenansicht für Formulareinträge.** Formulareinträge können nun als Karten oder in einer Tabelle angezeigt werden – mit Seitenblätterung für lange Listen. #53
- **Änderungen an personenbezogenen Daten werden protokolliert.** Änderungen an den personenbezogenen Daten einer Beratung werden als Ereignisse erfasst und in der Beratungs-Timeline angezeigt. #9 #51
- **Vorschau der Formularangaben in der Beratungsübersicht.** In der Beratungstabelle öffnet ein Klick auf das Auge-Symbol die zusätzlichen Informationen aus dem Formular – auch ohne Bearbeitungsrechte für die Beratung.
- **Übersichtlichere Formulareinträge.** Adressen und Koordinaten werden lesbarer dargestellt.
- **Übersichtlichere Beratungstabelle.** Die Spalte „Gruppe“ wird nur noch angezeigt, wenn tatsächlich mehrere Gruppen in Frage kommen.
- **Neueste Notizen zuerst.** In der Beratungs-Timeline stehen die aktuellsten Einträge jetzt oben; die Karte mit den Angaben aus dem Formular ist weiter nach oben gerückt.
- **Einfachere Beratungsfreigabe.** Mehrere Empfänger lassen sich beim Freigeben einer Beratung nun bequemer über eine Tag-Auswahl hinzufügen.
- **Überarbeitete Oberfläche.** Große Teile der Anwendung wurden auf ein moderneres, einheitlicheres Design umgestellt – darunter Anmeldung, Beratungstabelle, Benutzer- und Gruppenverwaltung sowie das Anlegen einer Beratung. Benachrichtigungen erscheinen jetzt als Toasts.

### Fehlerbehebungen

- Dialoge und Kartenkomponenten werden jetzt korrekt übereinander angezeigt (Ebenen-/Z-Index-Problem behoben).
- Formulare können per iframe jetzt auch auf externe Domains eingebunden werden. #62
- E-Mails und Systemtexte erscheinen jetzt auf Deutsch statt auf Englisch, z.B. die Mail zum Zurücksetzen des Passworts
- Links werden jetzt durchgängig in der Primärfarbe dargestellt und sind dadurch besser als Links erkennbar.
