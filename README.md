# IPS_PresenceSimulation

Das PHP Module für IP Symcon simuliert die Anwesenheit innerhalb einer vorgegebenen Toleranz zu bestimmten Uhrzeiten.


### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-des-moduls-in-ip-symcon)
5. [Statusvariablen & Timer & Profile](#5-statusvariablen--timer--profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

Es werden alle verlinkten Targets (Variablen) entweder dirket geschaltet oder per IPS_RunScriptWaitEx($VARIABLE, $VALUE)
weiter verarbeitet.

### 2. Voraussetzungen

- IP-Symcon ab Version 4.x

### 3. Installation

In IP Symcon unter Kern Instanzen->Modules die URL 'https://github.com/freakyfreaky/IPS_Presence-Simulation.git' hinzufügen.
Anschließend an entsprechender Stelle im Objektbaum eine neue Instanz "Anwesenheits-Simulation" anlegen und bei Bedarf entsprechend benennen.

 __Beispiele:__
 * Anwesenheits-Simulation_Flur
 * Anwesenheits-Simulation_Wohnzimmer

Der verwendete Namen wird ebenfalls in den Log-Meldung bei Aktivierung und Deaktivierung der Simulation verwendet

### 4. Einrichten des Moduls in IP-Symcon

Innerhalb der angelegten Instanz müssen anschließend die Start Uhrzeit sowie die End Uhrzeit definiert werden, an welchen die Simulation die Geräte / Variablen schalten und Scripte ausführen soll.

Die Toleranzbereiche dienen der Zufallsberechnung der Start- und Endzeit um eine Answesenheit zu simulieren die nicht einer Routine gleicht.
Wird innerhalb der Toleranzen 0 angegeben, werden die eingegebene Start und Endzeit verwendet ohne eine Zufallsberechnung.  

Alle zu schaltenden Variablen müssen in der Kategroie "Targets (Simulation)" verlinkt werden.

__Konfigurationsseite__:

Name                        | Beschreibung
--------------------------- | ---------------------------------
Start-Zeitpunkt             | Angabe der Uhrzeit wann die Simulation beginnen soll. Immer das Format HH:MM verwenden
End-Zeitpunkt               | Angabe der Uhrzeit wann die Simulation beendet werden soll. Immer das Format HH:MM verwenden
+/- Toleranz Start          | Angabe der Toleranz innerhalb welcher der tatsächliche Start-Zeitpunkt berechnet wird
+/- Toleranz Ende           | Angabe der Toleranz innerhalb welcher der tatsächliche End-Zeitpunkt berechnet wird

### 5. Statusvariablen & Timer & Profile

Die benötighten Timer werden automatisch angelegt und täglich nachts um 00:01 berechnet.
Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                        | Beschreibung
--------------------------- | ----------------
Anwesenheit simulieren?     | Aktiviert oder Deaktiviert die Simulation
SimulationRefresh           | Zum automatisch berechneten Zeitpunkt werden die Tagesdaten um 00:00:01 für den neuen Tag berechnet.
SimulationTimerOn           | Zum automatisch berechneten Zeitpunkt werden alle Variablen angeschaltet/aktualisiert.
SimulationTimerOff          | Zum automatisch berechneten Zeitpunkt werden alle Variablen ausgeschaltet/aktualisiert.

Es werden keine zusätzlichen Profile benötigt.

### 6. WebFront

Über das WebFront kann die Simulation de-/aktiviert werden. Hierzu die Statusvariable an entsprechender Stelle verlinken oder referenzieren.


### 7. PHP-Befehlsreferenz

Die Simulation kann mit dem Befehl `PSS_SetupSimulation(integer $InstanzID, boolean $SetActive);` aus anderen Scripten heraus aktivert oder deaktiviert werden.

Name                        | Beschreibung
--------------------------- | ----------------
$InstanzID                  | Angabe der schaltenden Instanz
$SetActive                  | `true` um die Simulation zu aktivieren<br>`false` um die Simulation zu deaktiveren


__Beispiel:__  
`PSS_SetupSimulation(12345, true);`
