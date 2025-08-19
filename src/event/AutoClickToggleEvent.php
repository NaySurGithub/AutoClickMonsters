<?php

declare(strict_types=1);

namespace AutoClickMonsters\event;

use pocketmine\event\Event;
use pocketmine\player\Player;

class AutoClickToggleEvent extends Event {
    public function __construct(
        private Player $player,
        private bool $enabled
    ) {}

    public function getPlayer(): Player {
        return $this->player;
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }
}