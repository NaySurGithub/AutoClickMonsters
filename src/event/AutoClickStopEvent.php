<?php

declare(strict_types=1);

namespace AutoClickMonsters\event;

use pocketmine\event\Event;
use pocketmine\player\Player;

class AutoClickStopEvent extends Event {
    public function __construct(
        private Player $player,
        private string $reason
    ) {}

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getReason(): string {
        return $this->reason;
    }
}