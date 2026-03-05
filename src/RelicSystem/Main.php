<?php

declare(strict_types=1);

namespace RelicSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\Chest;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\Position;

class Main extends PluginBase implements Listener {

    private RelicManager $manager;
    private Config $messages;
    private Config $chests;
    private array $creating = [];

    protected function onEnable() : void {
        $this->saveDefaultConfig();
        $this->saveResource("messages.yml");
        $this->messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        $this->chests = new Config($this->getDataFolder() . "chests.yml", Config::YAML);

        $this->manager = new RelicManager($this);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function getRelicManager(): RelicManager {
        return $this->manager;
    }

    public function getMessages(): Config {
        return $this->messages;
    }

    public function getChestConfig(): Config {
        return $this->chests;
    }

    public function setCreating(Player $player, string $relic): void {
        $this->creating[$player->getName()] = $relic;
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if(!$block instanceof Chest) return;

        $posKey = $block->getPosition()->getWorld()->getFolderName() . ":" .
            $block->getPosition()->getFloorX() . ":" .
            $block->getPosition()->getFloorY() . ":" .
            $block->getPosition()->getFloorZ();

        // Creating chest
        if(isset($this->creating[$player->getName()])){
            $relic = $this->creating[$player->getName()];
            $this->chests->set($posKey, $relic);
            $this->chests->save();
            unset($this->creating[$player->getName()]);
            $player->sendMessage($this->messages->get("relic-created"));
            return;
        }

        // Using relic
        if(!$this->chests->exists($posKey)) return;

        $item = $player->getInventory()->getItemInHand();
        $tag = $item->getNamedTag()->getTag("relic_name");
        if(!$tag instanceof StringTag) return;

        $relicName = $tag->getValue();
        if($this->chests->get($posKey) !== $relicName) return;

        $this->manager->activateRelic($player, $relicName);
        $item->setCount($item->getCount() - 1);
        $player->getInventory()->setItemInHand($item);
    }
}
