# TimeSlots (experimental) 

## Beschreibung

Dies ist ein vereinfachtes PHP-basiertes Messe-Buchungssystem, das **ausschließlich zu Demonstrationszwecken** dient. Es ermöglicht Benutzern, Zeitfenster für Besprechungen zu buchen. Es enthält eine einfache Administrationsschnittstelle.

**Wichtiger Sicherheitshinweis:**

Dieses System ist *nicht* für den Produktionseinsatz konzipiert. Es mangelt an wesentlichen Sicherheitsmaßnahmen und es sollte *niemals* auf einem öffentlich zugänglichen Server bereitgestellt werden, ohne dass wesentliche Sicherheitsverbesserungen vorgenommen wurden. Betrachten Sie es als Ausgangspunkt zum Lernen, aber nicht als fertiges Produkt.

## Funktionen

*   Benutzerfreundliches Buchungsformular mit Datum- und Zeitfensterauswahl.
*   Mehrsprachige Unterstützung (Englisch und Deutsch).
*   Einfache Administrationsschnittstelle zur Anzeige aller Buchungen.
*   iCalendar-Export zum Importieren von Buchungen in Kalenderanwendungen.
*   E-Mail-Bestätigung für erfolgreiche Buchungen.

## Installation

1.  **Voraussetzungen:**
    *   Ein Webserver (z.B. Apache, Nginx)
    *   PHP 7.4 oder höher
    *   `mail()`-Funktion in Ihrer PHP-Konfiguration aktiviert (zum Senden von Bestätigungs-E-Mails).

2.  **Download:** Speichern Sie den PHP-Code in einer Datei, z.B. `index.php`.

3.  **Konfiguration:**
    *   Öffnen Sie `index.php` in einem Texteditor.
    *   **Wichtig:** Ändern Sie das `admin_password` im `$CONFIG`-Array in ein starkes, eindeutiges Passwort für die Demonstration. Auch wenn dies eine Demo ist, ist es eine gute Übung!
    *   **Wichtig:** Generieren Sie ein sicheres, zufälliges `calendar_token` mit `bin2hex(random_bytes(32))` in PHP und ersetzen Sie `'YOUR_SECURE_TOKEN_HERE'` damit.

4.  **Upload:** Laden Sie die Datei `index.php` auf Ihren Webserver hoch.

5.  **Berechtigungen:** Stellen Sie sicher, dass der Webserver Schreibrechte für das Verzeichnis hat, in dem sich `index.php` befindet, da er die Datei `bookings.json` erstellen und beschreiben muss. Dies ist weiterhin wichtig, damit die Demo wie erwartet funktioniert.

6.  **Zugriff:** Öffnen Sie das Buchungssystem in Ihrem Webbrowser, indem Sie zu der URL navigieren, unter der Sie `index.php` hochgeladen haben (z.B. `http://your-domain.com/index.php`).

## Verwendung

*   **Buchung:** Benutzer können ein Datum, ein Zeitfenster und ihre Informationen auswählen, um eine Besprechung zu buchen.
*   **Admin-Login:** Um auf die Administrationsschnittstelle zuzugreifen, geben Sie den Benutzernamen und das Passwort ein, die im `$CONFIG`-Array konfiguriert sind.
*   **Admin-Schnittstelle:** Die Admin-Schnittstelle ermöglicht es Ihnen, alle Buchungen in einer Tabelle anzuzeigen.
*   **iCalendar-Export:** In der Admin-Schnittstelle können Sie alle Buchungen als iCalendar-Datei (.ics) exportieren, die in Kalenderanwendungen wie Google Kalender, Outlook usw. importiert werden kann.

## Sicherheitsüberlegungen (WICHTIG!)

**Diese Demo ist *nicht sicher* und sollte *nicht* in einer Produktionsumgebung verwendet werden.** Es fehlen wichtige Sicherheitsfunktionen, die für den realen Einsatz erforderlich wären. Im Einzelnen:

*   **Eingabevalidierung:** Es gibt nur eine sehr begrenzte Eingabevalidierung. Ein Angreifer könnte bösartigen Code in das System einschleusen.
*   **Authentifizierung:** Die Authentifizierung ist extrem einfach und anfällig für Brute-Force-Angriffe.
*   **Sitzungsverwaltung:** Die Sitzungsverwaltung ist rudimentär und anfällig für Session-Fixierung.
*   **Datenspeicherung:** Das Speichern von Daten in einer JSON-Datei ist keine sichere oder skalierbare Lösung.
*   **Kein CSRF-Schutz:** Die Anwendung ist anfällig für Cross-Site Request Forgery (CSRF)-Angriffe.
*   **XSS-Schwachstellen:** Die Anwendung weist wahrscheinlich XSS-Schwachstellen auf.

**Wenn Sie beabsichtigen, diesen Code als Grundlage für eine echte Anwendung zu verwenden, *müssen* Sie diese Sicherheitsprobleme beheben.** Beachten Sie den Abschnitt "Sicherheitstipps" der vollständigen Dokumentation (wenn es sich um eine echte Anwendung handeln würde), um Anleitungen zu erhalten. Die Beratung durch einen Sicherheitsexperten wird dringend empfohlen.

**Zusammenfassend lässt sich sagen, dass diese Demonstration ausschließlich zu Schulungszwecken dient und *niemals* ohne wesentliche Sicherheitsverbesserungen in einer Produktionsumgebung eingesetzt werden sollte.**
