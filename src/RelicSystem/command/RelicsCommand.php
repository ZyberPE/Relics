<?php

namespace RelicSystem\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use RelicSystem\Main;

class RelicsCommand extends Command{

    private Main $plugin;

    public function __construct(Main $plugin){
        parent::__construct("relics","Teleport to relics");
        $this->plugin = $plugin;
        $this->setPermission("relic.use");
    }

    public function execute(CommandSender $sender,string $label,array $args): bool{

        if(!$sender instanceof Player) return true;

        $tp = $this->plugin->getConfig()->get("teleport");

        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($tp["world"]);

        if($world === null){
            $sender->sendMessage("§cWorld not loaded");
            return true;
        }

        $sender->teleport($world->getSafeSpawn());
        $sender->sendMessage("§aTeleported");

        return true;
    }
}
