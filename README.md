[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Modul%20Version-0.20-blue.svg)]()
[![Version](https://img.shields.io/badge/Symcon%20Version-8.1%20%3E-green.svg)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v80-v81-q3-2025/)  
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Check Style](https://github.com/Nall-chan/CCast/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/CCast/actions)
[![Run Tests](https://github.com/Nall-chan/CCast/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/CCast/actions)  
[![Spenden](https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donate_SM.gif)](#2-spenden)[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](#2-spenden)  

# Chrome Cast Library <!-- omit in toc -->  
Einbinden von Google Cast (ChromeCast) fähigen Geräten in Symcon.  

## Inhaltsverzeichnis <!-- omit in toc -->

- [1. Vorbemerkungen](#1-vorbemerkungen)
	- [Zur Library](#zur-library)
	- [Zur Integration von Geräten](#zur-integration-von-geräten)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-software-installation)
- [4. Enthaltende Module](#4-enthaltende-module)
- [5. Anhang](#5-anhang)
	- [1. GUID der Module](#1-guid-der-module)
	- [2. Changelog](#2-changelog)
	- [3. Spenden](#3-spenden)
- [6. Lizenz](#6-lizenz)

----------
## 1. Vorbemerkungen

### Zur Library

Diese Library befindet sich noch in der Testphase.  
Der Funktionsumfang kann, auch je nach Gerät, sich noch stark verändern.  
Ebenso ist es möglich das noch Fehlermeldungen auftreten oder gar die Verbindung zum Gerät verloren geht.

Feedback hierzu ist im Symcon Forum im entsprechenden Thread gerne erwünscht.  

----------
### Zur Integration von Geräten  

Getestet wurde zum Großteil mit einem Google Nest Hub und TV-Boxen / Android TVs verschiedener Hersteller.  
Bei nativen Android Geräten mit Android TV (Google TV) wurden nicht alle Funktionen getestet.  
Die Steuerung von nativen Android Apps auf diesen Geräten wird nur eingeschränkt möglich sein.  

## 2. Voraussetzungen

* IP-Symcon ab Version 8.1
* Geräte welche ChromeCast unterstützen (z.B. Nest Hub, Android TV usw.)
 
 ## 3. Software-Installation
  
  Über den 'Module-Store' in IPS das Modul `ChromeCast` hinzufügen.  
   **Bei kommerzieller Nutzung (z.B. als Errichter oder Integrator) wenden Sie sich bitte an den Autor.**  
![Module-Store](imgs/install.png) 

  ## 4. Enthaltende Module

- __Chrome Cast Discovery__ ([Dokumentation](Chrome%20Cast%20Discovery/README.md))  
	Auffinden von ChromeCast fähigen Geräten im Netzwerk  

- __Chrome Cast__ ([Dokumentation](Chrome%20Cast/README.md))  
	Geräte Instanz welche ein ChromeCast Geräten in Symcon abbildet  

## 5. Anhang

###  1. GUID der Module
 
| Modul                 | Typ       | Prefix |                  GUID                  |
| :-------------------- | :-------- | :----: | :------------------------------------: |
| Chrome Cast Discovery | Discovery | CCAST  | {21E489CA-B260-4978-B038-B4AA5E07C17D} |
| Chrome Cast           | Gerät     | CCAST  | {9034A9D8-F004-22EA-9391-BF2E5E1CAB31} |

----------
### 2. Changelog

**Version 0.20:**  
- PHP-Befehle ergänzt um:
  - Repeat
  - Shuffle
  - Like & Dislike
  - Lyrics
  - TTS (Sprachausgabe)
  - Laden von Webseiten 
  
**Version 0.10:**  
- Test Release für Symcon 8.1  

----------
### 3. Spenden  
  
  Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

<a href="https://www.paypal.com/donate?hosted_button_id=G2SLW2MEMQZH2" target="_blank"><img src="https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donate_LG.gif" border="0" /></a>

[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share) 

## 6. Lizenz

  IPS-Modul:  
  [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
 
