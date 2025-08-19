<?php

declare(strict_types=1);

namespace AutoClickMonsters;

use AutoClickMonsters\event\AutoClickHitEvent;
use AutoClickMonsters\event\AutoClickStartEvent;
use AutoClickMonsters\event\AutoClickStopEvent;
use AutoClickMonsters\event\AutoClickTargetEvent;
use AutoClickMonsters\event\AutoClickToggleEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\entity\Living;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class Main extends PluginBase implements Listener {
    use DynamicPerms;

    private array $enabled = [];
    private array $active = [];
    private array $lastHitAt = [];
    private array $lastMobSeenAt = [];
    private array $internalHits = [];
    private int $cpsDefault;
    private float $reach;
    private float $idleStopSeconds;
    private bool $requireLook;
    private float $lookDotMin;
    private bool $startOnFirstHit;
    private array $messages;
    private array $permLevels;
    private array $permDefaults;
    private string $mode;

    protected function onEnable(): void {
        $this->saveDefaultConfig();
        $c = $this->getConfig();
        $this->cpsDefault = (int)$c->get("cps_default", 14);
        $this->reach = (float)$c->get("reach", 4.0);
        $this->idleStopSeconds = (float)$c->get("idle_stop_seconds", 3.0);
        $this->requireLook = (bool)$c->get("require_look_at_target", true);
        $this->lookDotMin = (float)$c->get("look_dot_min", 0.6);
        $this->startOnFirstHit = (bool)$c->get("start_on_first_hit", true);
        $this->messages = (array)$c->get("messages", []);
        $this->permLevels = (array)$c->get("permission_levels", []);
        $this->permDefaults = (array)$c->get("permission_defaults", []);
        $this->mode = (string)$c->get("mode", "single");
        $this->registerDynamicPermissions();
        $this->registerDynamicCommand();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void {
            $now = microtime(true);
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $name = $player->getName();
                if (!($this->enabled[$name] ?? false)) continue;
                if (!($this->active[$name] ?? false)) continue;
                if ($player->isClosed() || !$player->isAlive()) continue;
                $targets = $this->pickTargets($player);
                if (empty($targets)) {
                    $lastSeen = $this->lastMobSeenAt[$name] ?? $now;
                    if (($now - $lastSeen) >= $this->idleStopSeconds) {
                        $this->active[$name] = false;
                        $this->lastHitAt[$name] = 0.0;
                        $this->lastMobSeenAt[$name] = 0.0;
                        $player->sendMessage($this->msg("stopped_idle", "AutoClick off (idle)"));
                        (new AutoClickStopEvent($player, "idle"))->call();
                    }
                    continue;
                }
                $this->lastMobSeenAt[$name] = $now;
                $last = $this->lastHitAt[$name] ?? 0.0;
                $cps = max(1, $this->resolveCps($player));
                if (($now - $last) < (1.0 / $cps)) continue;
                $this->lastHitAt[$name] = $now;
                foreach ($targets as $target) {
                    $targetEv = new AutoClickTargetEvent($player, $target);
                    $targetEv->call();
                    if ($targetEv->isCancelled()) continue;
                    $finalTarget = $targetEv->getTarget();
                    $damage = $this->resolveAttackPoints($player->getInventory()->getItemInHand());
                    $hitEv = new AutoClickHitEvent($player, $finalTarget, $damage);
                    $hitEv->call();
                    if ($hitEv->isCancelled()) continue;
                    $this->internalHits[$player->getName()] = true;
                    $ev = new EntityDamageByEntityEvent($player, $finalTarget, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $hitEv->getDamage());
                    $finalTarget->attack($ev);
                    unset($this->internalHits[$player->getName()]);
                    if ($this->mode === "single") break;
                }
            }
        }), 1);
    }

    public function toggleEnabled(Player $player): bool {
        $name = $player->getName();
        $this->enabled[$name] = !($this->enabled[$name] ?? false);
        (new AutoClickToggleEvent($player, $this->enabled[$name]))->call();
        if ($this->enabled[$name]) {
            $this->active[$name] = false;
            $this->lastHitAt[$name] = 0.0;
            $this->lastMobSeenAt[$name] = 0.0;
            return true;
        } else {
            $this->active[$name] = false;
            (new AutoClickStopEvent($player, "toggle"))->call();
            return false;
        }
    }

    public function onHit(EntityDamageByEntityEvent $event): void {
        $damager = $event->getDamager();
        $entity = $event->getEntity();
        if (!$damager instanceof Player) return;
        if (!$entity instanceof Living) return;
        if ($entity instanceof Player) return;
        $name = $damager->getName();
        if (isset($this->internalHits[$name])) return;
        if (!($this->enabled[$name] ?? false)) return;
        if ($this->requireLook && !$this->isLookingAt($damager, $entity)) return;
        if (!($this->active[$name] ?? false)) {
            if ($this->startOnFirstHit) {
                $this->active[$name] = true;
                $this->lastHitAt[$name] = 0.0;
                $this->lastMobSeenAt[$name] = microtime(true);
                $damager->sendMessage($this->msg("started", "AutoClick started"));
                (new AutoClickStartEvent($damager))->call();
            }
        } else {
            $this->active[$name] = false;
            $damager->sendMessage($this->msg("disabled", "AutoClick disabled"));
            (new AutoClickStopEvent($damager, "manual"))->call();
        }
    }

    private function resolveCps(Player $player): int {
        $best = $this->cpsDefault;
        foreach ($this->permLevels as $perm => $cps) {
            if (!is_numeric($cps)) continue;
            if ($player->hasPermission((string)$perm)) {
                $best = max($best, (int)$cps);
            }
        }
        return $best;
    }

    private function pickTargets(Player $player): array {
        $bb = $player->getBoundingBox()->expandedCopy($this->reach, $this->reach, $this->reach);
        $targets = [];
        $nearest = null;
        $best = PHP_FLOAT_MAX;
        foreach ($player->getWorld()->getNearbyEntities($bb) as $e) {
            if (!$e instanceof Living) continue;
            if ($e instanceof Player) continue;
            if (!$e->isAlive()) continue;
            if ($this->requireLook && !$this->isLookingAt($player, $e)) continue;
            $d2 = $player->getPosition()->distanceSquared($e->getPosition());
            if ($d2 > $this->reach * $this->reach) continue;
            if ($this->mode === "aura") {
                $targets[] = $e;
            } else {
                if ($d2 < $best) {
                    $best = $d2;
                    $nearest = $e;
                }
            }
        }
        if ($this->mode === "single" && $nearest !== null) return [$nearest];
        return $targets;
    }

    private function isLookingAt(Player $player, Living $entity): bool {
        $from = $player->getEyePos();
        $to = $entity->getPosition()->add(0, $entity->getEyeHeight(), 0);
        $dir = $player->getDirectionVector()->normalize();
        $vec = $to->subtractVector($from)->normalize();
        $dot = $dir->dot($vec);
        return $dot >= $this->lookDotMin;
    }

    private function resolveAttackPoints(Item $item): int {
        if (method_exists($item, "getAttackPoints")) {
            return max(1, (int)$item->getAttackPoints());
        }
        return 1;
    }

    public function msg(string $key, string $fallback): string {
        return (string)($this->messages[$key] ?? $fallback);
    }

    private function registerDynamicCommand(): void {
        $cfg = (array)$this->getConfig()->get("command", []);
        $name = (string)($cfg["name"] ?? "autoclick");
        $desc = (string)($cfg["description"] ?? "Toggle autoclick");
        $usage = (string)($cfg["usage"] ?? "/autoclick");
        $perm = (string)($cfg["permission"] ?? "autoclick.use");
        $cmd = new AutoClickCommand($this, $name, $desc, $usage, $perm);
        $this->getServer()->getCommandMap()->register($this->getName(), $cmd);
    }
}