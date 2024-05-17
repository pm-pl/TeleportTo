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

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\ItemTypeIds;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;

use AEDXDEV\TeleportTo\libs\Vecnavium\FormsUI\CustomForm;

class Main extends PluginBase implements Listener{
  
  use SingletonTrait;
  
  public Config $config;
  
  public Config $db;
  
  public const FAKE_ENCH_ID = -1;
  
  public array $save = [];
  
  public array $get = [];
  
  public function onEnable(): void{
    self::setInstance($this);
		$this->getServer()->getCommandMap()->register("teleportto", new TpToCommand($this));
    $this->db = new Config($this->getDataFolder() . "db.yml", 2, []);
    EnchantmentIdMap::getInstance()->register(self::FAKE_ENCH_ID, new Enchantment("Glow", 1, ItemFlags::ALL, ItemFlags::NONE, 1));
    $this->config = new Config($this->getDataFolder() . "config.yml", 2, [
      "DeleteForm" => [
        "teleports.enable" => true,
        "teleports.count" => 25,
      ]
      ]);
	}
	
	public function onClick(PlayerInteractEvent $event){$from = implode(" ", $data["From"]);
	  $player = $event->getPlayer();
	  $name = $player->getName();
	  $vector = $event->getTouchVector();
	  $pos = new Position($vector->x, $vector->y, $vector->z, $player->getWorld());
	  // get Id
	  if (isset($this->get[$player->getName()])) {
  	  $m = $this->getServer()->getWorldManager();
  	  foreach ($this->getDB()->getAll() as $id => $data){
  	    $from = $data["From"];
  	    $from = new Position($from["X"], $from["Y"], $from["Z"], $m->getWorldByName($from["World"]));
  	    $to = $data["To"];
  	    $to = new Position($to["X"], $to["Y"], $to["Z"], $m->getWorldByName($to["World"]));
  	    if ($from->distance($pos) < 2 || $to->distance($pos) < 2) {
  	      $player->sendMessage("§aId: §e" . $id);
  	    }
  	  }
  	  return false;
	  }
	  // Item
	  $item = $event->getItem();
	  $tag = $item->getNamedTag();
	  if ($item->getTypeId() !== ItemTypeIds::DIAMOND_HOE || $item->getCustomName() !== " §aTeleport§bTo ")return false;
	  if (!$tag instanceof CompoundTag || $tag->getString("TeleportTo") == null)return false;
	  if ($player->isSneaking()) {
  	  if (!isset($this->save[$player->getName()])) {
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
	  $to_ = $event->getTo();
	  $m = $this->getServer()->getWorldManager();
  	foreach ($this->getDB()->getAll() as $id => $data){
  	  $from = $data["From"];
  	  $from = new Position($from["X"], $from["Y"], $from["Z"], $m->getWorldByName($from["World"]));
  	  $to = $data["To"];
  	  $to = new Position($to["X"], $to["Y"], $to["Z"], $m->getWorldByName($to["World"]));
  	  if ($from->distance($to_) < 2){
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
	    $from = explode(" ", $data[0]);
	    $from = new Position($from[0], $from[1], $from[2], $player->getWorld());
	    $to = explode(" ", $data[1]);
	    $to = new Position($to[0], $to[1], $to[2], $player->getWorld());
	    $this->addNewTeleport($id, $from, $to);
	    $player->sendMessage("§gTeleport§bTo §f>§9>  §aThe Teleport §8[§7{$id}§8] §awas saved successfully");
	  });
    $form->setTitle("§gTeleport§bTo");
    $form->addInput("From", " from position", ($from !== null ? implode(" ", [$from->x, $from->y, $from->z]) : ""));
    $form->addInput("To", " to position", ($to !== null ? implode(" ", [$to->x, $to->y, $to->z]) : ""));
    $form->sendToPlayer($player);
  }
  
  public function removeTeleportForm(Player $player){
    $all = "";
    foreach ($this->getDB()->getAll() as $id => $data){
      $from = implode(" ", $data["From"]);
      $to = implode(" ", $data["To"]);
      if (($id - 1) == $this->config->getNested("DeleteForm.teleports-count"))break;
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
	
	public function addNewTeleport(string $id, Position $from, Position $to){
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
	    ]
	  ]);
	}
	
	public function removeTeleport(string $id){
	  if (!$this->getDB()->exists($id))return false;
	  $this->getDB()->remove($id);
	}
	
	public static function NewId(): int{
	  return count(array_keys($this->getDB()->getAll()));
	}
}
