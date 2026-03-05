<?php

namespace RelicSystem\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use RelicSystem\Main;

class RelicCommand extends Command{

    private Main $plugin;

    public function __construct(Main $plugin){
        parent::__construct("relic","Relic admin command");
        $this->plugin = $plugin;
        $this->setPermission("relic.admin");
    }

    public function execute(CommandSender $sender,string $label,array $args): bool{

        if(!$sender instanceof Player) return true;

        if(!isset($args[0])) return true;

        switch($args[0]){

            case "create":

                if(!isset($args[1])) return true;

                $this->plugin->setCreating($sender,$args[1]);
                $sender->sendMessage("§aClick a chest to bind relic.");

            break;

            case "give":

                if(!isset($args[1],$args[2],$args[3])) return true;

                $target = $this->plugin->getServer()->getPlayerExact($args[1]);

                if(!$target) return true;

                $item = $this->plugin->getRelicManager()->createRelic($args[2]);

                if(!$item) return true;

                $item->setCount((int)$args[3]);

                $target->getInventory()->addItem($item);

                $sender->sendMessage("§aRelic given.");

            break;
        }

        return true;
    }
}
