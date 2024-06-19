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

namespace AEDXDEV\TeleportTo\command;

use AEDXDEV\TeleportTo\Main;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use pocketmine\nbt\tag\ListTag;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;

class TpToCommand extends Command implements PluginOwned{
  
  public array $from = [];
  
  public function __construct(
    private Main $plugin
  ){
    parent::__construct("teleportto", "TeleportTo Main command", null, ["tpto"]);
    $this->setPermission("teleportto.cmd");
  }
  
  public function execute(CommandSender $sender, string $label, array $args){
    $name = $sender->getName();
    switch ($args[0] ?? "help") {
      case "help":
				$sender->sendMessage("§e========================");
				$sender->sendMessage("§a- /" . $label . " item - give setup item");
				$sender->sendMessage("§a- /" . $label . " from - get the first position");
				$sender->sendMessage("§a- /" . $label . " to - get the second position");
				$sender->sendMessage("§a- /" . $label . " get - get id from click on teleport");
				$sender->sendMessage("§a- /" . $label . " remove - remove or delete teleport by id");
				$sender->sendMessage("§e========================");
			break;
			case "item":
			  $player = $sender;
        if (isset($args[1])) {
          $player = $this->plugin->getServer()->getPlayerByPrefix($args[1]);
        }
        if (!$player instanceof Player)return;
        $item = VanillaItems::DIAMOND_HOE()->setCustomName(" §aTeleport§bTo ");
        $tag = $item->getNamedTag();
        $tag->setTag(Item::TAG_ENCH, new ListTag());
        $tag->setString("TeleportTo", "TeleportTo");
        $item->setNamedTag($tag);
        $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(Main::FAKE_ENCH_ID)));
        $player->getInventory()->addItem($item);
			break;
			case "from":
			  if (!$sender instanceof Player)return;
			  $from = $sender->getPosition();
			  $this->from[$name] = $from;
			  $sender->sendMessage("§aFrom: §e" . implode(" ", [$from->x, $from->y, $from->z]));
			break;
			case "to":
			  if (!$sender instanceof Player)return;
			  if (!isset($this->from[$name])) {
			    $sender->sendMessage("use '/" . $label . " from' before '/" . $label . " to'");
			    break;
			  }
			  $from = $this->from[$name];
			  $to = $sender->getPosition();
			  $sender->sendMessage("§aTo: §e" . implode(" ", [$to->x, $to->y, $to->z]));
			  $this->plugin->addTeleportForm($sender, $from, $to);
			  unset($this->from[$name]);
			break;
			case "get":
			  if (isset($this->plugin->get[$name])){
			    $sender->sendMessage("§eYou are already used this command.");
			    $sender->sendMessage("§eClick on a teleport place to get id");
			    break;
			  }
			  $this->plugin->get[$name] = "";
			  $sender->sendMessage("§aClick on the teleport place to get id");
			break;
			case "reomve":
			case "delete":
			  if (!isset($args[1])) {
			    $sender->sendMessage("§cUsage: /" . $label . " " . $args[0] . "<Id: int>");
			  }
			  $this->plugin->removeTeleport($args[1]);
			break;
    }
  }
}
