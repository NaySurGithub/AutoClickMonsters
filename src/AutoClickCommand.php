<?php

declare(strict_types=1);

namespace AutoClickMonsters;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class AutoClickCommand extends Command {
    private Main $plugin;

    public function __construct(Main $plugin, string $name, string $description, string $usageMessage, string $permission) {
        parent::__construct($name, $description, $usageMessage);
        $this->plugin = $plugin;
        $this->setPermission($permission);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) return true;
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->plugin->msg("no_permission", "No permission"));
            return true;
        }
        $toggled = $this->plugin->toggleEnabled($sender);
        if ($toggled) {
            $sender->sendMessage($this->plugin->msg("toggled_on", "AutoClick ready, hit a mob to start"));
        } else {
            $sender->sendMessage($this->plugin->msg("toggled_off", "AutoClick disabled"));
        }
        return true;
    }
}