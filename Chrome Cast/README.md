[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Modul%20Version-0.10-blue.svg)]()
[![Version](https://img.shields.io/badge/Symcon%20Version-8.1%20%3E-green.svg)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v80-v81-q3-2025/)  
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Check Style](https://github.com/Nall-chan/CCast/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/CCast/actions)
[![Run Tests](https://github.com/Nall-chan/CCast/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/CCast/actions)  
[![Spenden](https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donate_SM.gif)](#2-spenden)[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](#2-spenden)  

# Chrome Cast   <!-- omit in toc -->
Beschreibung des Moduls.

## Inhaltsverzeichnis   <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-software-installation)
- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
- [5. Statusvariablen und Profile](#5-statusvariablen-und-profile)
    - [Statusvariablen](#statusvariablen)
  - [Profile](#profile)
- [6. Visualisierung](#6-visualisierung)
  - [1. Kachel-Visu](#1-kachel-visu)
  - [2. WebFront](#2-webfront)
- [7. PHP-Befehlsreferenz](#7-php-befehlsreferenz)
- [8. Anhang](#8-anhang)
  - [1. Changelog](#1-changelog)
  - [2. Spenden](#2-spenden)
- [9. Lizenz](#9-lizenz)


## 1. Funktionsumfang

* Abbilden vom Status in Symcon
* Steuerung von Lautstärke und Medien
* Wiedergabe von Medien aus dem LAN per Default Media Render

## 2. Voraussetzungen

- IP-Symcon ab Version 8.1

## 3. Software-Installation

* Über den Module Store das 'Chrome Cast'-Modul installieren.

## 4. Einrichten der Instanzen in IP-Symcon

 Es wird empfohlen neue Instanzen über das [Discovery-Modul](../Chrome%20Cast%20Discovery/README.md) zu erstellen.
 Unter 'Instanz hinzufügen' kann das 'Chrome Cast'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

![Config](imgs/Config.png)  

## 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

![Variables](imgs/ObjectTree.png)  

#### Statusvariablen

| Name                 | Typ     | Beschreibung                                            |
| -------------------- | ------- | ------------------------------------------------------- |
| Aktive App           | string  | Aktuelle App                                            |
| Lautstärke           | integer | Lautstärke in %                                         |
| Stumm                | bool    | Stummschaltung                                          |
| Wiedergabestatus     | integer | Status bei Medienwiedergabe                             |
| Wiederholung         | string  | Wiederholung                                            |
| Dauer in Sekunden    | integer | Dauer der aktuellen Wiedergabe in Sekunden              |
| Dauer                | string  | Dauer der aktuellen Wiedergabe als Text                 |
| Position in Sekunden | integer | Position der aktuellen Wiedergabe in Sekunden           |
| Position             | string  | Position der aktuellen Wiedergabe als Text              |
| Fortschritt          | float   | Aktueller Fortschritt der aktuellen Wiedergabe          |
| Titel                | string  | Titel der aktuellen Wiedergabe                          |
| Künstler             | string  | Künstler der aktuelle Wiedergabe                        |
| Sammlung             | string  | Sammlung, Album, Playlist o.ä. der aktuellen Wiedergabe |


### Profile

| Name                    | Typ    | Genutzt durch                                  |
| ----------------------- | ------ | ---------------------------------------------- |
| CCast.AppId.<InstanzID> | string | Enthält alle für dieses Gerät verfügbaren Apps |


## 6. Visualisierung

### 1. Kachel-Visu
Die Funktionalität, die das Modul in der Kachel Visu bietet.  

![Tile](imgs/Tile.png)  

### 2. WebFront
Die Funktionalität, die das Modul im WebFront bietet.  

![WebFront](imgs/WebFront.png)  

## 7. PHP-Befehlsreferenz

`bool CCAST_SetVolumen(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_SetVolumen(12345);`  

--- 
`bool CCAST_SetMute(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_SetMute(12345);`  

---
`bool CCAST_LaunchApp(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_LaunchApp(12345);`  

---
`bool CCAST_SetPlayerState(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_SetPlayerState(12345);`  

---
`bool CCAST_Seek(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_Seek(12345);`  

---
`bool CCAST_SeekRelative(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_SeekRelative(12345);`  

---
`bool CCAST_SetRepeat(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_SetRepeat(12345);`  

---
`bool CCAST_GetAppAvailability(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_GetAppAvailability(12345);`  

---
`bool CCAST_LoadMediaURL(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_LoadMediaURL(12345);`  

---
`bool CCAST_LoadMediaId(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_LoadMediaId(12345);`  

---
`bool CCAST_CloseApp(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_CloseApp(12345);`  

---
`bool CCAST_RequestState(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_RequestState(12345);`  

---
`bool CCAST_RequestMediaState(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_RequestMediaState(12345);`  

---
`bool CCAST_RequestIdleState(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_RequestIdleState(12345);`  

---
`bool CCAST_SendCommand(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_SendCommand(12345);`  

---
`bool CCAST_SendCommandToApp(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_SendCommandToApp(12345);`  

---
`bool CCAST_SendPing(integer $InstanzID);`  
Erklärung der Funktion.  

Beispiel:  
`CCAST_SendPing(12345);`  

## 8. Anhang

### 1. Changelog

[Changelog der Library](../README.md#2-changelog)

### 2. Spenden

Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

<a href="https://www.paypal.com/donate?hosted_button_id=G2SLW2MEMQZH2" target="_blank"><img src="https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donate_LG.gif" border="0" /></a>  

[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share) 

## 9. Lizenz

  IPS-Modul:  
  [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
