<?php

declare(strict_types=1);

namespace AutoClickMonsters\event;

use pocketmine\entity\Living;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

class AutoClickHitEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private Player $player,
        private Living $target,
        private float $damage
    ) {}

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getTarget(): Living {
        return $this->target;
    }

    public function getDamage(): float {
        return $this->damage;
    }

    public function setDamage(float $damage): void {
        $this->damage = $damage;
    }
}