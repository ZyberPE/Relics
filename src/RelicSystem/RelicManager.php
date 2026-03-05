<?php

declare(strict_types=1);

namespace RelicSystem;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\StringTag;
use pocketmine\item\Item;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;

class RelicManager{

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    /**
     * Creates a relic item
     */
    public function createRelic(string $name) : ?Item{

        $relics = $this->plugin->getConfig()->get("relics");

        if(!isset($relics[$name])){
            return null;
        }

        $data = $relics[$name];

        // relic item = glowing nether star
        $item = VanillaItems::NETHER_STAR();

        $item->setCustomName($data["name"]);

        if(isset($data["lore"])){
            $item->setLore($data["lore"]);
        }

        // Add NBT tag so the plugin can identify the relic
        $item->getNamedTag()->setTag("relic_name", new StringTag($name));

        // Add glow effect
        $item->addEnchantment(
            new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1)
        );

        return $item;
    }

    /**
     * Activates relic reward
     */
    public function activateRelic(Player $player, string $name) : void{

        $relics = $this->plugin->getConfig()->get("relics");

        if(!isset($relics[$name])){
            return;
        }

        $data = $relics[$name];

        if(!isset($data["rewards"])){
            return;
        }

        $rewards = $data["rewards"];

        // Calculate total weight
        $totalWeight = 0;

        foreach($rewards as $reward){
            $totalWeight += (int)$reward["chance"];
        }

        if($totalWeight <= 0){
            return;
        }

        // Random roll
        $random = mt_rand(1, $totalWeight);
        $current = 0;

        foreach($rewards as $reward){

            $current += (int)$reward["chance"];

            if($random <= $current){

                // Replace player placeholder
                $command = str_replace(
                    "{player}",
                    $player->getName(),
                    $reward["command"]
                );

                // Execute command as console
                $this->plugin->getServer()->dispatchCommand(
                    $this->plugin->getServer()->getConsoleSender(),
                    $command
                );

                // Send reward message
                if(isset($reward["message"])){
                    $player->sendMessage($reward["message"]);
                }

                return;
            }
        }
    }
}
