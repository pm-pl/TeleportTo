<?php

/**
  *  A free plugin for PocketMine-MP.
  *	
  *	Copyright (c) AEDXDEV
  *  
  *	Youtube: AEDX DEV 
  *	Discord: aedxdev
  *	Github: AEDXDEV
  *	Email: aedxdev@gmail.com
  *	Donate: https://paypal.me/AEDXDEV
  *   
  *        This program is free software: you can redistribute it and/or modify
  *        it under the terms of the GNU General Public License as published by
  *        the Free Software Foundation, either version 3 of the License, or
  *        (at your option) any later version.
  *
  *        This program is distributed in the hope that it will be useful,
  *        but WITHOUT ANY WARRANTY; without even the implied warranty of
  *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *        GNU General Public License for more details.
  *
  *        You should have received a copy of the GNU General Public License
  *        along with this program.  If not, see <http://www.gnu.org/licenses/>.
  *         
  */

namespace AEDXDEV\TeleportTo;

use AEDXDEV\TeleportTo\command\TpToCommand;
use AEDXDEV\TeleportTo\task\ParticleTask;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\ItemTypeIds;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;

use AEDXDEV\TeleportTo\libs\Vecnavium\FormsUI\CustomForm;
use AEDXDEV\TeleportTo\libs\Vecnavium\FormsUI\ModalForm;

class Main extends PluginBase implements Listener{
  
  use SingletonTrait;
  
  public Config $config;
  
  public Config $db;
  
  public const FAKE_ENCH_ID = -1;
  
  public array $save = [];
  
  public array $get = [];
  
  public function onEnable(): void{
    self::setInstance($this);
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getCommandMap()->register("teleportto", new TpToCommand($this));
    $this->db = new Config($this->getDataFolder() . "db.yml", 2, []);
    EnchantmentIdMap::getInstance()->register(self::FAKE_ENCH_ID, new Enchantment("Glow", 1, ItemFlags::ALL, ItemFlags::NONE, 1));
    $this->config = new Config($this->getDataFolder() . "config.yml", 2, [
      "DeleteForm" => [
        "teleports.enable" => true,
        "teleports.count" => 25,
      ]
    ]);
    $this->getScheduler()->scheduleRepeatingTask(new ParticleTask(), 11);
	}
	
	public function onClick(PlayerInteractEvent $event){
	  $player = $event->getPlayer();
	  $name = $player->getName();
    $blockPos = $event->getBlock()->getPosition();
    $pos = Position::fromObject($blockPos->asVector3()->getSide($event->getFace()), $blockPos->getWorld());
	  // get Id
	  if (isset($this->get[$name])) {
  	  foreach ($this->getDB()->getAll() as $id => $data){
  	    $from = $this->toPos($data["From"]);
  	    $to = $this->toPos($data["To"]);
  	    if ($from->distance($pos) < 2 || $to->distance($pos) < 2) {
  	      $player->sendMessage("§aId: §e" . $id);
  	      unset($this->get[$name]);
  	    }
  	  }
  	  return false;
	  }
	  // Item
	  $item = $event->getItem();
	  if ($item->getTypeId() !== ItemTypeIds::DIAMOND_HOE || $item->getCustomName() !== " §aTeleport§bTo ")return false;
	  if ($item->getNamedTag()->getString("TeleportTo", null) == null)return false;
	  if ($player->isSneaking()) {
	    foreach ($this->getDB()->getAll() as $id => $data){
	      $from = $this->toPos($data["From"]);
        $to = $this->toPos($data["To"]);
        if ($from->distance($pos) < 2) {
          if (isset($this->save[$name])) {
            $player->sendMessage("§gTeleport§bTo §f>§9>  §cYou can't add a Teleport here.");
          } else {
            $this->removeSureForm($player, $id);
          }
        }
      }
  	  if (!isset($this->save[$name])) {
  	    // From
  	    $this->save[$name] = $pos;
  	    $player->sendMessage("§aFrom: §e" . implode(" ", [$pos->x, $pos->y, $pos->z]));
  	  } else {
  	    // To
  	    $from = $this->save[$name];
  	    $to = $pos;
  	    $player->sendMessage("§aTo: §e" . implode(" ", [$pos->x, $pos->y, $pos->z]));
  		  $this->addTeleportForm($player, $from, $to);
  		  unset($this->save[$name]);
  	  }
  	  return false;
	  }
	  $this->addTeleportForm($player);
	}
	
	public function onMove(PlayerMoveEvent $event){
	  $player = $event->getPlayer();
	  $m = $this->getServer()->getWorldManager();
  	foreach ($this->getDB()->getAll() as $id => $data){
  	  $from = $data["From"];
  	  $from = new Position($from["X"], $from["Y"], $from["Z"], $m->getWorldByName($from["World"]));
  	  $to = $data["To"];
  	  $to = new Position($to["X"], $to["Y"], $to["Z"], $m->getWorldByName($to["World"]));
  	  if ($from->distance($player->getPosition()) < 1.4){
  	    $player->teleport($to);
  	  }
    }
	}
	
	public function getDB(): Config{
	  $db = $this->db;
	  return $db;
	}
	
	public function addTeleportForm(Player $player, ?Position $from = null, ?Position $to = null) {
    $form = new CustomForm(function(Player $player, $data){
      if ($data === null){
	      return false;
	    }
	    if (empty($data[0]) || empty($data[1])) {
	      $player->sendMessage("§gTeleport§bTo §f>§9>  §cFailed to save Teleport");
	    }
	    $id = self::NewId();
	    $from = $this->stringToPos($data[0], $player->getWorld());
	    $to = $this->stringToPos($data[1], $player->getWorld());
	    $this->addNewTeleport($id, $from, $to, $data[2]);
	    $player->sendMessage("§gTeleport§bTo §f>§9>  §aThe Teleport §8[§7{$id}§8] §awas saved successfully");
	  });
    $form->setTitle("§6Teleport§bTo");
    $form->addInput("From", " from position", ($from !== null ? implode(" ", [$from->x, $from->y, $from->z]) : ""));
    $form->addInput("To", " to position", ($to !== null ? implode(" ", [$to->x, $to->y, $to->z]) : ""));
    $form->addToggle("Particle", true);
    $form->sendToPlayer($player);
  }
  
  public function removeTeleportForm(Player $player){
    $all = "";
    foreach ($this->getDB()->getAll() as $id => $data){
      $from = implode(" ", $data["From"]);
      $to = implode(" ", $data["To"]);
      if ($id == $this->config->getNested("DeleteForm.teleports-count"))break;
      $all .= "§eId: §f$id  §eFrom: §f$from  §eTo: §f$to \n";
    }
    $form = new CustomForm(function(Player $player, $data){
      if ($data === null){
	      return false;
	    }
	    if (!isset($data[1])) {
	      $player->sendMessage("§gTeleport§bTo §f>§9>  §cFailed to remove Teleport");
	    }
	    $this->removeTeleport($data[1]);
	    $player->sendMessage("§gTeleport§bTo §f>§9>  §aThe Teleport was removed successfully");
	  });
    $form->setTitle("§gTeleport§bTo");
    $form->addLabel((bool) $this->config->getNested("DeleteForm.teleports-enable") ? $all : "");
    $form->addInput("Id", "teleport id");
    $form->sendToPlayer($player);
  }
  
  public function removeSureForm(Player $player, int $id){
    $form = new ModalForm(function (Player $player, $data) use ($id) {
      if ($data == true) {
        $this->removeTeleport($id);
      }
    });
    $form->setTitle("§gTeleport§bTo");
    $form->setContent("§eAre you sure from removing the teleport?");
    $form->setButton1("§aYes");
    $form->setButton2("§cNo");
    $player->sendForm($form);
  }
  
  private function toPos(array $data): Position{
    return new Position($data["X"], $data["Y"], $data["Z"], $this->getServer()->getWorldManager()->getWorldByName($data["World"]));
  }
  
  private function stringToPos(string $string, World $world): Position{
    [$x, $y, $z] = explode(" ", $string);
    return new Position($x, $y, $z, $world);
  }
	
	public function addNewTeleport(string $id, Position $from, Position $to, bool $particle){
	  $this->getDB()->set($id, [
	    "From" => [
	      "X" => floor($from->x),
	      "Y" => floor($from->y),
	      "Z" => floor($from->z),
	      "World" => $from->getWorld()->getFolderName()
	    ],
	    "To" => [
	      "X" => floor($to->x),
	      "Y" => floor($to->y),
	      "Z" => floor($to->z),
	      "World" => $to->getWorld()->getFolderName()
	    ],
	    "Particle" => $particle
	  ]);
	  $this->getDB()->save();
	}
	
	public function removeTeleport(string $id){
	  if (!$this->getDB()->exists($id))return false;
	  $this->getDB()->remove($id);
	  $this->getDB()->save();
	}
	
	private static function NewId(): int{
	  return count(array_keys(Main::getInstance()->getDB()->getAll()));
	}
}
