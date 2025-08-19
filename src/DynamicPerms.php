<?php

declare(strict_types=1);

namespace AutoClickMonsters;

use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;

trait DynamicPerms{
    private function registerDynamicPermissions(): void{
        $pm = PermissionManager::getInstance();
        $base = "autoclick.use";
        $this->registerOnePermission($pm, $base, (string)($this->permDefaults[$base] ?? "op"));
        foreach($this->permLevels as $perm => $cps){
            $this->registerOnePermission($pm, (string)$perm, (string)($this->permDefaults[$perm] ?? "op"));
        }
    }

    private function registerOnePermission(PermissionManager $pm, string $name, string $default): void{
        if($pm->getPermission($name) instanceof Permission) return;
        $perm = new Permission($name, "");
        $rootNames = $this->defaultToRootNames($default);
        $parents = [];
        foreach($rootNames as $rn){
            $p = $pm->getPermission($rn);
            if($p instanceof Permission){
                $parents[] = $p;
            }
        }
        if(empty($parents)){
            $pm->addPermission($perm);
            return;
        }
        DefaultPermissions::registerPermission($perm, $parents);
    }

    private function defaultToRootNames(string $s): array{
        $v = strtolower($s);
        if($v === "op") return [DefaultPermissions::ROOT_OPERATOR];
        if($v === "notop" || $v === "nonop" || $v === "user") return [DefaultPermissions::ROOT_USER];
        if($v === "true" || $v === "all" || $v === "everyone") return [DefaultPermissions::ROOT_OPERATOR, DefaultPermissions::ROOT_USER];
        if($v === "false" || $v === "none" || $v === "nobody") return [];
        return [DefaultPermissions::ROOT_OPERATOR];
    }
}