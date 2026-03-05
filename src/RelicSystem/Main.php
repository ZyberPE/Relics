<?php

declare(strict_types=1);

namespace RelicSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\Chest;
use pocketmine\nbt\tag\StringTag;

use RelicSystem\command\RelicCommand;
use RelicSystem\command\RelicsCommand;

class Main extends PluginBase implements Listener{

    private RelicManager $relicManager;
    private Config $messages;
    private Config $chests;

    private array $creating = [];

    protected function onEnable(): void{

        $this->saveDefaultConfig();
        $this->saveResource("messages.yml");
        $this->saveResource("chests.yml");

        $this->messages = new Config($this->getDataFolder()."messages.yml");
        $this->chests = new Config($this->getDataFolder()."chests.yml");

        $this->relicManager = new RelicManager($this);

        $this->getServer()->getPluginManager()->registerEvents($this,$this);

        $this->getServer()->getCommandMap()->register("relic",new RelicCommand($this));
        $this->getServer()->getCommandMap()->register("relics",new RelicsCommand($this));
    }

    public function getRelicManager(): RelicManager{
        return $this->relicManager;
    }

    public function getMessages(): Config{
        return $this->messages;
    }

    public function getChestConfig(): Config{
        return $this->chests;
    }

    public function setCreating(Player $player,string $relic): void{
        $this->creating[$player->getName()] = $relic;
    }

    public function getCreating(Player $player): ?string{
        return $this->creating[$player->getName()] ?? null;
    }

    public function clearCreating(Player $player): void{
        unset($this->creating[$player->getName()]);
    }

    public function onInteract(PlayerInteractEvent $event): void{

        $player = $event->getPlayer();
        $block = $event->getBlock();

        if(!$block instanceof Chest) return;

        $pos = $block->getPosition();

        $key =
            $pos->getWorld()->getFolderName().":".
            $pos->getFloorX().":".
            $pos->getFloorY().":".
            $pos->getFloorZ();

        // create relic chest
        if(($relic = $this->getCreating($player)) !== null){

            $this->chests->set($key,$relic);
            $this->chests->save();

            $this->clearCreating($player);

            $player->sendMessage($this->messages->get("relic-created"));
            return;
        }

        // using relic
        if(!$this->chests->exists($key)) return;

        $item = $player->getInventory()->getItemInHand();
        $tag = $item->getNamedTag()->getTag("relic_name");

        if(!$tag instanceof StringTag) return;

        $relic = $tag->getValue();

        if($this->chests->get($key) !== $relic) return;

        $this->relicManager->activateRelic($player,$relic);

        $item->setCount($item->getCount()-1);
        $player->getInventory()->setItemInHand($item);
    }
}
