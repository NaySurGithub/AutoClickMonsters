<?php

declare(strict_types=1);

namespace AutoClickMonsters\event;

use pocketmine\event\Event;
use pocketmine\player\Player;

class AutoClickStartEvent extends Event {
    public function __construct(
        private Player $player
    ) {}

    public function getPlayer(): Player {
        return $this->player;
    }
}