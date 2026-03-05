<?php

declare(strict_types=1);

namespace RelicSystem;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\StringTag;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;

class RelicManager {

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function createRelicItem(string $name){
        $data = $this->plugin->getConfig()->get("relics")[$name] ?? null;
        if($data === null) return null;

        $item = VanillaItems::NETHER_STAR();
        $item->setCustomName($data["name"]);
        $item->setLore($data["lore"]);

        $item->getNamedTag()->setTag("relic_name", new StringTag($name));
        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));

        return $item;
    }

    public function activateRelic(Player $player, string $name): void {
        $data = $this->plugin->getConfig()->get("relics")[$name];
        $rewards = $data["rewards"];

        $totalWeight = 0;
        foreach($rewards as $reward){
            $totalWeight += $reward["chance"];
        }

        $rand = mt_rand(1, $totalWeight);
        $current = 0;

        foreach($rewards as $reward){
            $current += $reward["chance"];
            if($rand <= $current){

                $cmd = str_replace("{player}", $player->getName(), $reward["command"]);
                $this->plugin->getServer()->dispatchCommand(
                    $this->plugin->getServer()->getConsoleSender(),
                    $cmd
                );

                $player->sendMessage($reward["message"]);
                return;
            }
        }
    }
}
