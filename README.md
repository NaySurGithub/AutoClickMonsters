# AutoClickMonsters

Un plugin **PocketMine-MP** permettant d’activer un **autoclick automatique** sur les monstres.  
Il est configurable et prend en charge plusieurs niveaux de permissions (CPS différents selon le rang).

---

## ✨ Fonctionnalités
- Activation / désactivation avec `/autoclick`
- Démarrage automatique lors du premier coup porté à un monstre
- Détection de la cible la plus proche (reach configurable)
- Support du **mode Single** (attaque une cible) et **mode Aura** (attaque plusieurs cibles à la fois)
- Permissions dynamiques avec différents niveaux de CPS :
  - `autoclicklevel1.use` → 10 CPS
  - `autoclicklevel2.use` → 15 CPS
  - `autoclicklevel3.use` → 20 CPS
- Messages entièrement configurables
- Système d’événements (AutoClickStartEvent, AutoClickStopEvent, etc.)

---

## ⚙️ Configuration (config.yml par défaut)

```yaml
cps_default: 14
reach: 4.0
idle_stop_seconds: 3.0
require_look_at_target: true
look_dot_min: 0.6
only_monsters: true
start_on_first_hit: true
mode: single # "single" ou "aura"
messages:
  enabled: "§aAutoClick activé"
  disabled: "§cAutoClick désactivé"
  started: "§aAutoClick démarré"
  stopped_idle: "§cAutoClick désactivé (inactif)"
  no_permission: "§cVous n'avez pas la permission"
  toggled_on: "§aAutoClick prêt, tapez un mob pour démarrer"
  toggled_off: "§cAutoClick désactivé"
permission_levels:
  autoclicklevel1.use: 10
  autoclicklevel2.use: 15
  autoclicklevel3.use: 20
permission_defaults:
  autoclick.use: op
  autoclicklevel1.use: op
  autoclicklevel2.use: op
  autoclicklevel3.use: op
command:
  name: autoclick
  description: Toggle autoclick
  usage: /autoclick
  permission: autoclick.use
