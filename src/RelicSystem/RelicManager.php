<?php

namespace RelicSystem;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\Server;

class RelicManager{

    private Main $plugin;
    private Config $relics;
    private Config $chests;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;

        @mkdir($plugin->getDataFolder());

        $this->relics = new Config($plugin->getDataFolder() . "relics.yml", Config::YAML);
        $this->chests = new Config($plugin->getDataFolder() . "chests.yml", Config::YAML);
    }

    public function getRelics(): Config{
        return $this->relics;
    }

    public function getChests(): Config{
        return $this->chests;
    }

    public function saveChests(): void{
        $this->chests->save();
    }

    public function createRelicItem(string $name, int $amount = 1): Item{
        $data = $this->relics->get($name);

        $item = VanillaItems::NETHER_STAR();
        $item->setCount($amount);

        $customName = TextFormat::colorize($data["name"]);
        $item->setCustomName($customName);

        $lore = [];
        foreach($data["lore"] as $line){
            $lore[] = TextFormat::colorize($line);
        }

        $item->setLore($lore);

        $nbt = $item->getNamedTag();
        $nbt->setString("relic_name", $name);
        $item->setNamedTag($nbt);

        $item->setEnchantmentGlintOverride(true);

        return $item;
    }

    public function isRelic(Item $item): bool{
        return $item->getNamedTag()->getTag("relic_name") !== null;
    }

    public function getRelicName(Item $item): ?string{
        return $item->getNamedTag()->getString("relic_name", "");
    }

    public function addChest(Position $pos, string $relic): void{
        $key = $pos->getWorld()->getFolderName() . ":" . $pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ();

        $this->chests->set($key, [
            "world" => $pos->getWorld()->getFolderName(),
            "x" => $pos->getFloorX(),
            "y" => $pos->getFloorY(),
            "z" => $pos->getFloorZ(),
            "relic" => $relic
        ]);

        $this->chests->save();
    }

    public function isRelicChest(Position $pos): bool{
        $key = $pos->getWorld()->getFolderName() . ":" . $pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ();
        return $this->chests->exists($key);
    }

    public function openRelic(Player $player, string $relic): void{
        $data = $this->relics->get($relic);

        if(!isset($data["commands"])){
            return;
        }

        $commands = $data["commands"];

        $totalChance = 0;
        foreach($commands as $cmd){
            $totalChance += $cmd["chance"];
        }

        $rand = mt_rand(1, $totalChance);
        $current = 0;

        foreach($commands as $cmd){

            $current += $cmd["chance"];

            if($rand <= $current){

                $command = str_replace("{player}", $player->getName(), $cmd["command"]);

                Server::getInstance()->dispatchCommand(
                    Server::getInstance()->getCommandMap()->getCommand("say")->getOwningPlugin(),
                    $command
                );

                if(isset($cmd["message"])){
                    $player->sendMessage(TextFormat::colorize($cmd["message"]));
                }

                return;
            }
        }
    }
}
