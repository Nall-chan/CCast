[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Module Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FCCast%2Frefs%2Fheads%2Fmain%2Flibrary.json&query=%24.version&label=Modul%20Version&color=blue)](https://community.symcon.de/t/modul-chromecast-google-cast/141614)
[![Symcon Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FCCast%2Frefs%2Fheads%2Fmain%2Flibrary.json&query=%24.compatibility.version&suffix=%3E&label=Symcon%20Version&color=green)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v80-v81-q3-2025/)  
[![License](https://img.shields.io/badge/License-Custom--NC--SA-green.svg)](#10-lizenz)
[![Check Style](https://github.com/Nall-chan/CCast/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/CCast/actions)
[![Run Tests](https://github.com/Nall-chan/CCast/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/CCast/actions)  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](#2-spenden)
[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](#2-spenden)

# Chrome Cast   <!-- omit in toc -->  

Einbinden eines Chrome Cast Gerätes in Symcon.  

## Inhaltsverzeichnis   <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-software-installation)
- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
- [5. Statusvariablen](#5-statusvariablen)
  - [Profile](#profile)
- [6. Visualisierung](#6-visualisierung)
  - [Kachel Visualisierung](#kachel-visualisierung)
  - [WebFront Visualisierung](#webfront-visualisierung)
- [7. PHP-Befehlsreferenz](#7-php-befehlsreferenz)
  - [Allgemeine Befehle](#allgemeine-befehle)
  - [Streaming von Inhalten](#streaming-von-inhalten)
  - [Steuerung der Medienwiedergabe](#steuerung-der-medienwiedergabe)
- [8. Aktionen](#8-aktionen)
- [9. Anhang](#9-anhang)
  - [1. Changelog](#1-changelog)
  - [2. Spenden](#2-spenden)
- [10. Lizenz](#10-lizenz)

## 1. Funktionsumfang

- Abbilden vom Status in Symcon (aktive App, Lautstärke, Wiedergabestatus, Titel, Künstler, Sammlung, Cover, Dauer, Position)
- Steuerung von Lautstärke und Medien, auch bei Wiedergaben, welche von anderen Geräten gecastet wurden (z.B. YouTube Music, Spotify)
- Wiederholung, Zufallswiedergabe und Wiedergabeliste für die Media-Kachel
- Wiedergabe von Medien und Wiedergabelisten per Default Media Receiver
- Wiedergabe von YouTube und YouTube Music (ohne Google-Konto)
- Sprachausgabe von Texten und Anzeige von Webseiten
- Aktionen für Ablaufpläne und Ereignisse

## 2. Voraussetzungen

- Symcon ab Version 8.1

## 3. Software-Installation

- Über den Module Store das 'Chrome Cast'-Modul installieren.

## 4. Einrichten der Instanzen in IP-Symcon

> [!TIP]
> Es wird empfohlen neue Instanzen über das [Discovery-Modul](../Chrome%20Cast%20Discovery/README.md) zu erstellen.

Unter 'Instanz hinzufügen' kann das 'Chrome Cast'-Modul mithilfe des Schnellfilters gefunden werden.  

- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

![Konfiguration](imgs/config.png)  

| Eigenschaft                                        | Name                | Typ     | Standardwert | Beschreibung                                                                                                   |
| -------------------------------------------------- | ------------------- | ------- | ------------ | -------------------------------------------------------------------------------------------------------------- |
| Aktiv                                              | `Open`              | boolean | `false`      | Verbindung zum Gerät aufbauen                                                                                  |
| Erweiterte Power-On Überwachung: Prüfe alle        | `Watchdog`          | boolean | `true`       | Prüft zyklisch, ob das Gerät erreichbar ist, und verbindet dann automatisch                                    |
| Erweiterte Power-On Überwachung: Intervall         | `Interval`          | integer | `5`          | Intervall der Prüfung in Sekunden (mindestens 5)                                                               |
| Erweiterte Power-On Überwachung: Bedingung         | `ConditionType`     | integer | `0`          | Art der Prüfung: `0` = Netzwerk Ping, `1` = Erweiterte Bedingung                                               |
| Erweiterte Power-On Überwachung: Erweiterte Bedingung | `WatchdogCondition` | string  | `''`         | Bedingung als JSON-String (Format wie bei [`IPS_IsConditionPassing`](https://www.symcon.de/en/service/documentation/command-reference/management-events/ips-isconditionpassing/)); am einfachsten über das Formular erstellen, nur bei `ConditionType` = `1` |
| Breite vom Media Bild                              | `MediaSizeWidth`    | integer | `512`        | Breite in Pixel des Medienbildes (Cover), 60 bis 3000                                                          |
| Breite vom App Icon                                | `AppIconSizeWidth`  | integer | `90`         | Breite in Pixel des App Icons, 60 bis 512                                                                      |
| Variable für Dauer in Sekunden anlegen             | `enableRawDuration` | boolean | `true`       | Legt die Statusvariable `Dauer in Sekunden` an                                                                 |
| Variable für Position in Sekunden anlegen          | `enableRawPosition` | boolean | `true`       | Legt die Statusvariable `Position in Sekunden` an                                                              |
| Variable für Wiedergabeliste anlegen               | `enablePlaylist`    | boolean | `true`       | Legt die Statusvariable `Wiedergabeliste` an                                                                   |

Die Eigenschaften können auch per Skript geändert werden. Die Änderungen werden erst mit `IPS_ApplyChanges` übernommen:  

```php
IPS_SetProperty(12345, 'Interval', 30);
IPS_SetProperty(12345, 'enableRawPosition', false);
IPS_ApplyChanges(12345);
```

Unter den Aktionen des Formulars stehen folgende Schaltflächen zur Verfügung:  

| Schaltfläche                 | Funktion                                                                                         |
| ---------------------------- | ------------------------------------------------------------------------------------------------ |
| Verfügbare Apps abfragen     | Aktuell ohne Funktion (`CCAST_GetAppAvailability`, die Antwort wird noch nicht ausgewertet)       |
| Zustandsabfrage              | Fragt den Status des Gerätes ab (`CCAST_RequestState`)                                           |
| Medienstatus abfragen        | Fragt den Status der Medienwiedergabe ab (`CCAST_RequestMediaState`)                             |
| App beenden                  | Beendet die laufende Cast App (`CCAST_CloseApp`)                                                 |
| Gelernte Apps zurücksetzen   | Entfernt alle automatisch gelernten Apps aus dem App-Profil (mit Rückfrage), siehe [Profile](#profile) |

Die Adresse des Gerätes wird nicht hier, sondern im übergeordneten `Client Socket` eingetragen: Eigenschaften `Host` (string) und `Port` (integer, bei ChromeCast-Geräten üblicherweise `8009`). Bei Anlage über die Discovery werden beide Werte automatisch gesetzt.  

> [!IMPORTANT]
> `Open` sowie die SSL-Einstellungen des `Client Socket` werden von dieser Instanz verwaltet und sollten dort nicht verändert werden.

## 5. Statusvariablen

Die Statusvariablen/Kategorien werden automatisch angelegt.

> [!WARNING]
> Das Löschen einzelner Statusvariablen kann zu Fehlfunktionen führen.

Zusätzlich legt die Instanz zwei Medienobjekte an: `App Icon` (Symbol der aktiven App) und `Media` (Cover der aktuellen Wiedergabe; ist kein Cover vorhanden oder kann es nicht geladen werden, das App-Symbol bzw. ein Ersatzbild).  

| Name                 | Typ     | Beschreibung                                            |
| -------------------- | ------- | ------------------------------------------------------- |
| Aktive App           | string  | Aktuelle App                                            |
| Lautstärke           | integer | Lautstärke in %                                         |
| Stumm                | bool    | Stummschaltung                                          |
| Wiedergabestatus     | integer | Status bei Medienwiedergabe                             |
| Wiederholung         | integer | Wiederholung der Wiedergabeliste (`~Repeat`)            |
| Zufallswiedergabe    | bool    | Zufallswiedergabe der Wiedergabeliste (`~Shuffle`)      |
| Dauer in Sekunden    | integer | Dauer der aktuellen Wiedergabe in Sekunden (optional)   |
| Dauer                | string  | Dauer der aktuellen Wiedergabe als Text                 |
| Position in Sekunden | integer | Position der aktuellen Wiedergabe in Sekunden (optional)|
| Position             | string  | Position der aktuellen Wiedergabe als Text              |
| Fortschritt          | float   | Aktueller Fortschritt der aktuellen Wiedergabe          |
| Titel                | string  | Titel der aktuellen Wiedergabe (ohne Titel der Dateiname der Quelle) |
| Künstler             | string  | Künstler der aktuelle Wiedergabe                        |
| Sammlung             | string  | Sammlung, Album, Playlist o.ä. der aktuellen Wiedergabe |
| Wiedergabeliste      | string  | Einträge der Wiedergabeliste (`~Playlist`, optional)    |

### Profile

| Name                      | Typ    | Genutzt durch                                  |
| ------------------------- | ------ | ---------------------------------------------- |
| CCast.AppId.`<InstanzID>` | string | Enthält die bekannten Apps sowie alle vom Gerät gemeldeten, automatisch gelernten Apps |
| CCast.DurationSeconds     | integer | Dauer in Sekunden                             |
| CCast.Duration            | string  | Dauer                                         |
| CCast.Position            | string  | Position                                      |
| CCast.Collection          | string  | Sammlung                                      |

Der Wiedergabestatus nutzt die Systemprofile `~Playback…`: Unterstützt die App Pause, werden nur Play/Pause angeboten (`~PlaybackNoStop` bzw. `~PlaybackPreviousNextNoStop`), sonst zusätzlich Stop. Zurück/Weiter erscheinen nur, wenn die App sie unterstützt.  

`Wiederholung` nutzt das Systemprofil `~Repeat` (`0` = Aus, `1` = Kontext/ganze Liste, `2` = Lied) und `Zufallswiedergabe` das Systemprofil `~Shuffle`. Beide sind nur bedienbar, wenn eine Wiedergabeliste aktiv ist (z.B. per `CCAST_LoadMediaQueue`) oder die App die Funktion meldet. Ohne Wiedergabe werden sie auf `Aus` gesetzt.  
Das Einschalten der Zufallswiedergabe mischt die Liste sofort und kann dabei zu einem anderen Titel springen; das Ausschalten stellt die ursprüngliche Reihenfolge wieder her.  
Bei Spotify werden Änderungen an Wiederholung und Zufallswiedergabe erst mit dem nächsten Titelwechsel übernommen und angezeigt.  

> [!NOTE]
> **Spotify:** Eine Wiedergabe, die aus der Spotify-App oder dem Webplayer auf das Gerät gecastet wurde, kann über die Statusvariablen gesteuert werden (Play/Pause, Zurück/Weiter, Fortschritt, Lautstärke, Wiederholung, Zufallswiedergabe), Titel, Künstler, Album, Cover und Dauer werden angezeigt (getestet mit einem kostenlosen Konto).  
> Das Starten von Spotify-Inhalten durch das Modul ist nicht möglich, Spotify benötigt dafür den Zugang eines angemeldeten Nutzers.

`Wiedergabeliste` nutzt das Systemprofil `~Playlist` und wird in der Media-Kachel als Liste angezeigt. Ein Eintrag kann dort angewählt werden, die Wiedergabe springt dann auf diesen Eintrag.  
Der Inhalt ist ein JSON-String im Format `{"current": 1, "entries": [{"artist": "…", "song": "…", "duration": 425}]}`. `current` ist der Index des aktuellen Eintrags (beginnend mit 0, `-1` = keiner), `duration` die Dauer in Sekunden (`0`, solange das Gerät den Eintrag noch nicht geladen hat). Fehlt ein Titel, wird der Dateiname der Quelle angezeigt.  
Per Skript wird ein Eintrag mit `RequestAction` und einem JSON-String mit dem gewünschten Index in `current` angewählt:  

```php
RequestAction(IPS_GetObjectIDByIdent('playlist', 12345), json_encode(['current' => 2]));
```

> [!NOTE]
> Die Wiedergabeliste wird nur bei Apps gefüllt, welche eine Cast-Wiedergabeliste melden (z.B. der Default Media Receiver mit `CCAST_LoadMediaQueue`). YouTube Music überträgt seine Liste nicht, die Variable bleibt dort leer.

Meldet das Gerät eine App, welche noch nicht im Profil enthalten ist, wird diese automatisch aufgenommen (Name vom Gerät bzw. aus der öffentlichen App-Konfiguration von Google).  
Die gelernten Apps bleiben dauerhaft erhalten und können über die Schaltfläche `Gelernte Apps zurücksetzen` im Konfigurationsformular entfernt werden.  

## 6. Visualisierung

### Kachel Visualisierung

Die Instanz wird in der Kachel-Visualisierung als Media-Kachel dargestellt: Cover, Titel und Künstler, Lautstärke, Zurück/Play/Pause/Weiter, Zufallswiedergabe und Fortschritt.  

![Kachel](imgs/tile_instance.png)  

Maximiert zeigt die Media-Kachel zusätzlich Stummschaltung, Wiederholung und die Wiedergabeliste. Ein Eintrag der Liste kann direkt angewählt werden.  

![Media-Kachel maximiert](imgs/tile_player.png)  

Über das Symbol oben rechts lässt sich auf die Darstellung `Liste` mit allen Statusvariablen umschalten. `App Icon` und `Media` erscheinen dort als Symbol zum Öffnen, `Wiedergabeliste` als Text.  

![Kachel Liste](imgs/tile_list.png)  

### WebFront Visualisierung

Im WebFront werden alle Statusvariablen sowie App Icon und Cover angezeigt. Die Wiedergabeliste erscheint als Text.  

![WebFront](imgs/webfront.png)  

## 7. PHP-Befehlsreferenz

Alle Befehle liefern `true` bei Erfolg. Im Fehlerfall (z.B. Gerät nicht verbunden, keine Antwort innerhalb von 10 Sekunden, Fehlermeldung vom Gerät wie `LOAD_FAILED`, keine Wiedergabe aktiv) liefern sie `false` und erzeugen eine Meldung vom Typ `E_USER_NOTICE` mit dem Grund, welche im Skript ausgegeben bzw. im Log protokolliert wird.  
Bei der Bedienung von Statusvariablen über die Visualisierung wird der Grund dagegen direkt in der Visualisierung angezeigt.  

### Allgemeine Befehle  

```php
bool CCAST_SetVolume(integer $InstanzID, float $Level);
```

Setzt die Lautstärke auf den Wert von `Level` (0.0 bis 1.0).  
Die bisherige Funktion `CCAST_SetVolumen` ist weiterhin als veralteter Alias vorhanden.  

Beispiel:  

```php
CCAST_SetVolume(12345, 0.5);
```

Lautstärke auf 50% setzen.  

---  

```php
bool CCAST_SetMute(integer $InstanzID, bool $Mute);
```

Setzt die Stummschaltung.  

Beispiel:  

```php
CCAST_SetMute(12345, true);
```

Gerät stumm schalten.  

---  

```php
bool CCAST_LaunchApp(integer $InstanzID, string $AppId);
```

Startet eine Cast App.  

Kleine Auswahl von AppIds:  

| App                  | AppId    |
| -------------------- | -------- |
| Audible              | 25456794 |
| Backdrop             | E8C28D3C |
| CastBridge           | 46C1A819 |
| ChromeMirroring      | 0F5096E8 |
| DefaultMediaReceiver | CC1AD845 |
| DisneyPlus           | C3DE6BC2 |
| GooglePhotos         | 96084372 |
| GooglePodcast        | 3DFCDBD1 |
| Netflix              | CA5E8412 |
| ScreenMirroring      | 674A0243 |
| Spotify              | CC32E753 |
| YouTube              | 233637DE |
| YouTubeMusic         | 2DB7CC49 |

Beispiel:  

```php
CCAST_LaunchApp(12345, 'CC1AD845');
```

Default Media Render starten.  

---  

```php
bool CCAST_GetAppAvailability(integer $InstanzID);
```

> [!NOTE]
> Aktuell nicht verfügbar.

Beispiel:  

```php
CCAST_GetAppAvailability(12345);
```

---  

```php
bool CCAST_CloseApp(integer $InstanzID);
```

Beendet die aktuelle Cast App (`STOP` an den Receiver), das Gerät wechselt in den Ruhemodus.  
Wird z.B. bei Android TV Geräten eine native App ausgeführt, so hat der Befehl darauf keinen Einfluss.  
Läuft keine App, liefert die Funktion `false` mit einer Fehlermeldung.  

Beispiel:  

```php
CCAST_CloseApp(12345);
```

---  

```php
bool CCAST_RequestState(integer $InstanzID);
```

Frage den aktuellen Status ab.  

Beispiel:  

```php
CCAST_RequestState(12345);
```

---  

```php
bool CCAST_RequestIdleState(integer $InstanzID);
```

Fragt den aktuellen Ruhemodus ab.  

Beispiel:  

```php
CCAST_RequestIdleState(12345);
```

### Streaming von Inhalten

```php
bool CCAST_PlayText(integer $InstanzID, string $Text, bool $CloseApp);
```

Startet eine Sprachausgabe mit dem in `Text` übergebenen Inhalt auf dem Gerät.  
Der Parameter `CloseApp` sollte `true` sein, wenn keine weiteren Ausgaben oder Medien geladen werden.  
Der Text darf maximal 200 Zeichen lang sein, die Sprache entspricht der Systemsprache von Symcon.  

> [!NOTE]
> Die Sprachausgabe nutzt einen nicht offiziellen Dienst von Google Translate. Dieser kann jederzeit ohne Vorankündigung eingeschränkt werden oder entfallen.

Beispiel:  

```php
CCAST_PlayText(12345, 'Achtung! Es folgt eine Durchsage.', false);
```

---  

```php
bool CCAST_DisplayWebsite(integer $InstanzID, string $Url, bool $DisableInput, bool $AutoReload);
```

Veranlasst das Gerät die in `Url` übergebene Website aufzurufen und darzustellen.  
Über `DisableInput` auf `true` wird eine Eingabe unterbunden.  
Der Parameter `AutoReload` sollte ein neu laden ermöglichen.  

Beispiel:  

```php
CCAST_DisplayWebsite(12345, 'https://community.symcon.de', false, true);
```

---  

```php
bool CCAST_LoadMediaURL(integer $InstanzID, string $Url, string $contentType, bool $isLive);
```

Startet den Default Media Receiver, sofern nicht schon gestartet, und lädt die in `Url` übergebene Quelle.  

> [!IMPORTANT]
> Die Quelle muss ohne weitere Authentifizierung vom Gerät aus erreichbar sein.  
> Der Default Media Receiver unterstützt keine Anmeldeverfahren. Auch ein übergeben von Anmeldedaten in der Url wird nicht funktionieren.

Der Parameter `isLive` muss für Live-Streams `true` sein. Für normale Dateien wird `false` empfohlen.  
Der `contentType` sollte passend zur Quelle gewählt werden und entspricht den MIME-Typen.  
Wird ein leere String bei `contentType` übergeben, so wird versucht den richtigen Typ automatisch zu ermitteln.  

Auswahl von unterstützen und getesteten contentType`s:  

| contentType | Datei / Format |
| ----------- | -------------- |
| audio/mp3   | MP3            |
| audio/mpeg  | MP3            |
| video/mp4   | MP4            |
| image/png   | PNG Bild       |
| image/jpeg  | JPG Bild       |

Beispiel Live-Stream einer Kamera:  

```php
CCAST_LoadMediaURL(12345, 'http://meineKamera/video.mp4', 'video/mp4', true);
```

Beispiel Wiedergabe einer MP3 Datei:  

```php
CCAST_LoadMediaURL(12345, 'http://meinSymcon:3777/user/Alarmton.mp3', 'audio/mp3', false);
```

Beispiel Anzeige eines Bildes:  

```php
CCAST_LoadMediaURL(12345, 'https://upload.wikimedia.org/wikipedia/commons/a/ad/Reflection_nebula_IC_349_near_Merope.jpg', 'image/jpeg', false);
```

---  

```php
bool CCAST_LoadMediaId(integer $InstanzID, string $contentId, string $contentType, bool $isLive);
```

Identisch zu `CCAST_LoadMediaURL`, jedoch wird hier eine `contentId` für die entsprechende Quelle erwartet.  
Der Default Media Receiver nutzt die `contentId` als Adresse der Quelle, hier kann also ebenfalls eine URL übergeben werden.  

Beispiel:  

```php
CCAST_LoadMediaId(12345, 'http://meinSymcon:3777/user/Alarmton.mp3', 'audio/mpeg', false);
```

---  

```php
bool CCAST_PlayYouTube(integer $InstanzID, string $VideoId, string $ListId = '');
```

Startet die YouTube App auf dem Chromecast, sofern nicht schon gestartet, und spielt das angegebene Video bzw. die Playlist ab.  

```php
bool CCAST_PlayYouTubeMusic(integer $InstanzID, string $VideoId, string $ListId = '');
```

Startet die YouTube Music App auf dem Chromecast, sofern nicht schon gestartet, und spielt den angegebenen Titel bzw. die Playlist ab.  

> [!IMPORTANT]
> Beide Funktionen erwarten **keine URL**, sondern nur die IDs aus der Adresse eines Videos, Titels oder einer Playlist.

Es ist kein Google-Konto und kein API-Key notwendig. Die Wiedergabe erfolgt ohne Nutzerbezug (keine eigenen Mixe, keine Likes).  

**Wo finde ich die IDs?**  
Video bzw. Titel im Browser oder per `Teilen` -> `Link kopieren` öffnen und die Adresse betrachten:  

| Adresse (Beispiel)                                                    | `VideoId`     | `ListId`                             |
| --------------------------------------------------------------------- | ------------- | ------------------------------------ |
| `https://www.youtube.com/watch?v=8-Qekf_GBow`                         | `8-Qekf_GBow` | `''`                                 |
| `https://youtu.be/8-Qekf_GBow?si=AbCdEfGh12345678`                    | `8-Qekf_GBow` | `''`                                 |
| `https://www.youtube.com/shorts/8-Qekf_GBow`                          | `8-Qekf_GBow` | `''`                                 |
| `https://music.youtube.com/watch?v=y0OovVJzJXY&list=RDAMVMy0OovVJzJXY` | `y0OovVJzJXY` | `RDAMVMy0OovVJzJXY`                  |
| `https://music.youtube.com/playlist?list=PLL9hR76gpuABM39W7VRZdCyYqTzoEDM4v` | `''`    | `PLL9hR76gpuABM39W7VRZdCyYqTzoEDM4v` |
| `https://music.youtube.com/playlist?list=OLAK5uy_nVAYpLix-FGE4fa2fYfi5ZG1fdSgt3bMA` (Album) | `''` | `OLAK5uy_nVAYpLix-FGE4fa2fYfi5ZG1fdSgt3bMA` |

- Die `VideoId` ist der Wert hinter `v=` (bzw. nach `youtu.be/` oder `shorts/`) und immer 11 Zeichen lang (Buchstaben, Ziffern, `-` und `_`).  
- Die `ListId` ist der Wert hinter `list=`. Typische Anfänge sind `PL…` (Playlist), `OLAK5uy_…` (Album bei YouTube Music) und `RD…` (automatischer Mix/Radio).  
- Alle anderen Parameter der Adresse werden **nicht** übergeben. Insbesondere ist `si=…` nur eine Kennung des Teilen-Links und keine Video-ID. Auch eine Startzeit (`t=…`) wird nicht unterstützt, hierfür nach dem Start `CCAST_Seek` nutzen.  
- Videos von YouTube können auch mit `CCAST_PlayYouTubeMusic` wiedergegeben werden und umgekehrt, sofern der Inhalt im jeweiligen Dienst verfügbar ist.  

> [!TIP]
> Ein Radio passend zu einem Titel erhält man über die `ListId` `RDAMVM` + `VideoId` (Beispiel siehe Tabelle).

**Kombinationen der Parameter:**  

| `VideoId` | `ListId` | Ergebnis                                                                                                    |
| --------- | -------- | ----------------------------------------------------------------------------------------------------------- |
| gesetzt   | leer     | Spielt das Video bzw. den Titel. Danach folgen ggf. automatische Vorschläge des Dienstes.                    |
| gesetzt   | gesetzt  | Spielt das Video bzw. den Titel und anschließend die weiteren Einträge der Playlist. **Empfohlen.**          |
| leer      | gesetzt  | Spielt die Playlist ab dem ersten Eintrag. Läuft bereits eine Wiedergabe in derselben App, wird die App vorher neu gestartet (einige Sekunden Verzögerung). |
| leer      | leer     | Nicht zulässig.                                                                                             |

**Rückgabewert:**  
`true` bedeutet, dass das Gerät den Befehl angenommen hat. Ob die ID existiert, kann vorher nicht geprüft werden.  
Bei einer ungültigen oder nicht verfügbaren ID (z.B. Ländersperre, Altersfreigabe, privates Video) überspringt die App den Eintrag bzw. spielt einen Vorschlag ab.  
Ob die gewünschte Wiedergabe läuft, ist an den Statusvariablen `Titel` und `Künstler` zu erkennen.  
`false` mit Fehlermeldung erfolgt, wenn die App nicht gestartet werden konnte oder die YouTube Lounge die Anfrage ablehnt.  

Beispiele:  

```php
// Ein einzelnes Video in YouTube
CCAST_PlayYouTube(12345, '8-Qekf_GBow');

// Einen Titel in YouTube Music, gefolgt vom passenden Radio
CCAST_PlayYouTubeMusic(12345, 'y0OovVJzJXY', 'RDAMVMy0OovVJzJXY');

// Eine Playlist in YouTube Music vom Anfang
CCAST_PlayYouTubeMusic(12345, '', 'PLL9hR76gpuABM39W7VRZdCyYqTzoEDM4v');

// Ein Album in YouTube Music vom ersten Titel
CCAST_PlayYouTubeMusic(12345, '', 'OLAK5uy_nVAYpLix-FGE4fa2fYfi5ZG1fdSgt3bMA');
```

---  

```php
bool CCAST_LoadMediaQueue(integer $InstanzID, array $Items, bool $Repeat, integer $StartIndex, bool $Autoplay);
```

Lädt eine Liste von `Items` als Wiedergabeliste.  
Jeder Eintrag von `Items` muss mindestens das Feld `contentUrl` enthalten.  
Optional sind `streamType` und `contentType` möglich, wie bei den Funktionen zuvor.  
Außerdem können über das Feld `metadata` noch weitere Daten der Quelle, wie Titel, Bild/Thumbnail, Collection usw.. ergänzt werden.

Der Parameter `Repeat` kann für Wiederholung der Liste auf `true` gesetzt werden, sonst muss `false` angegeben werden.  
Der zuerst Wiedergegebene Eintrag ist in `StartIndex` zu übergeben und fängt mit 0 an.  
Über den Parameter `Autoplay` auf `true` kann die Wiedergabe sofort gestartet werden.  

Ist `Items` leer, fehlt bei einem Eintrag `contentUrl` oder liegt `StartIndex` außerhalb der Liste, liefert die Funktion `false` mit einer Fehlermeldung, ohne den Default Media Receiver zu starten.  

Beispiel:  

```php
$Items = [
  [
    'contentUrl'  => 'http://meinSymcon:3777/user/Alarmton.mp3',
    'metadata'    =>  // metadate enthält zusätzliche Daten des Objektes
    [
      'title' => 'Achtung Achtung' // Anzeigetitel
    ]
  ],
  [
    'contentUrl' => 'http://meineKamera/videoStream',
    'streamType'  => 'LIVE',
    'contentType' => 'video/mp4',
    'metadata'    =>  // metadate enthält zusätzliche Daten des Objektes
    [
      'images' =>
      [
        [
          'url' => 'http://meineKamera/SnapshotBild.jpg'  //Vorschaubild
        ]
      ],
      'title' => 'Meine Kamera' // Anzeigetitel
    ]    
  ]
];
CCAST_LoadMediaQueue(12345, $Items, true, 1, true);
```  

### Steuerung der Medienwiedergabe

```php
bool CCAST_SetPlayerState(integer $InstanzID, string $State);
```

Sendet einen Steuerbefehl an die aktuelle Wiedergabe.  

Auswahl von bekannten Befehlen:  

| State      |
| ---------- |
| PLAY       |
| PAUSE      |
| STOP       |
| QUEUE_NEXT |
| QUEUE_PREV |

Beispiel:  

```php
CCAST_SetPlayerState(12345, 'PAUSE');
```

Pausiert die aktuelle Wiedergabe.  

---  

```php
bool CCAST_Seek(integer $InstanzID, float $Time);
```

Springt auf den in `Time` übergebenen Zeitpunkt der Wiedergabe.  

Beispiel:  

```php
CCAST_Seek(12345, 30.5);
```

Springt bei der Aktuellen Wiedergabe auf 30,5 Sekunden.  

---  

```php
bool CCAST_SeekRelative(integer $InstanzID, float $Time);
```

Spult die Wiedergabe um die in `Time` übergebenen Zeit vor oder zurück.  

Beispiel:  

```php
CCAST_SeekRelative(12345, -10);
```

Wiedergabe 10 Sekunden zurückspulen.  

---  

```php
bool CCAST_SetRepeat(integer $InstanzID, string $Mode);
```

Steuert die Art der Wiederholung einer Wiedergabeliste.  

> [!NOTE]
> Wird nicht von allen Quellen unterstützt!

| Werte von Mode         | Bedeutung                                                      |
| ---------------------- | -------------------------------------------------------------- |
| REPEAT_OFF             | Keine Wiederholung                                             |
| REPEAT_SINGLE          | Aktuellen Titel wiederholen                                    |
| REPEAT_ALL             | Ganze Liste wiederholen                                        |
| REPEAT_ALL_AND_SHUFFLE | Ganze Liste wiederholen und nach jedem Durchlauf neu mischen   |

Andere Werte werden mit einer Fehlermeldung abgelehnt.  
`true` bedeutet, dass das Gerät den Befehl nicht abgelehnt hat. Ob es ihn übernommen hat, ist an der Statusvariable `Wiederholung` zu erkennen.  
`false` mit Fehlermeldung erfolgt, wenn die App den Befehl ablehnt (z.B. YouTube Music: `INVALID_REQUEST INVALID_PARAMS`).  

Beispiel:  

```php
CCAST_SetRepeat(12345, 'REPEAT_ALL');
```

---  

```php
bool CCAST_SetShuffle(integer $InstanzID, bool $Shuffle);
```

Schaltet die Zufallswiedergabe der Wiedergabeliste ein (`true`) oder aus (`false`).  
Die bisherige Funktion `CCAST_Shuffle` ist weiterhin als veralteter Alias vorhanden und schaltet die Zufallswiedergabe ein.  
Beim Einschalten wird die Liste sofort gemischt, dabei kann die App zu einem anderen Titel springen. Beim Ausschalten wird die ursprüngliche Reihenfolge wiederhergestellt.  
`true` bedeutet, dass das Gerät den Befehl nicht abgelehnt hat. Ob es ihn übernommen hat, ist an der Statusvariable `Zufallswiedergabe` zu erkennen (YouTube Music z.B. ignoriert den Befehl ohne Fehlermeldung).  

> [!NOTE]
> Wird nicht von allen Quellen unterstützt!

Beispiel:  

```php
CCAST_SetShuffle(12345, true);
```

---  

```php
bool CCAST_RequestMediaState(integer $InstanzID);
```

Fragt den aktuellen Status der Medienwiedergabe ab.  

Beispiel:  

```php
CCAST_RequestMediaState(12345);
```

## 8. Aktionen

**Grundsätzlich können alle bedienbaren Statusvariablen als Ziel einer [`Aktion`](https://www.symcon.de/service/dokumentation/konzepte/automationen/ablaufplaene/aktionen/) mit `Auf Wert schalten` angesteuert werden, so dass hier keine speziellen Aktionen benutzt werden müssen.**

Für Befehle mit Parametern, welche keine Statusvariable abbildet, stehen folgende Aktionen mit einer `Chrome Cast`-Instanz als Ziel zur Verfügung:  

| Aktion                            | Parameter                                                    | Entspricht                                          |
| --------------------------------- | ------------------------------------------------------------ | --------------------------------------------------- |
| Medien-URL abspielen              | URL, Inhaltstyp (oder Automatisch), Live-Stream               | `CCAST_LoadMediaURL`                                |
| Text ansagen                      | Text (max. 200 Zeichen), App nach der Ansage beenden          | `CCAST_PlayText`                                    |
| Webseite anzeigen                 | URL, Eingabe sperren, Automatisch neu laden                   | `CCAST_DisplayWebsite`                              |
| YouTube / YouTube Music abspielen | Dienst, VideoId, ListId                                       | `CCAST_PlayYouTube` bzw. `CCAST_PlayYouTubeMusic`   |
| App beenden                       | keine                                                        | `CCAST_CloseApp`                                    |

Die Bedeutung der Parameter ist in der [PHP-Befehlsreferenz](#7-php-befehlsreferenz) beim jeweiligen Befehl beschrieben.  

## 9. Anhang

### 1. Changelog

[Changelog der Library](../README.md#2-changelog)

### 2. Spenden

Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](https://paypal.me/Nall4chan)  

[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share)  

## 10. Lizenz

  IPS-Modul:  
  [Custom NC-SA](../LICENSE)  
