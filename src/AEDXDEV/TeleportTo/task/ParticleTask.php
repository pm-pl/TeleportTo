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

namespace AEDXDEV\TeleportTo\task;

use AEDXDEV\TeleportTo\Main;
use pocketmine\scheduler\Task;
use pocketmine\block\utils\DyeColor;
use pocketmine\world\Position;
use pocketmine\world\particle\DustParticle;

class ParticleTask extends Task {
	
	public function __construct(){
	  // NOPE [;
	}
	
	public function onRun(): void{
  	foreach (Main::getInstance()->getDB()->getAll() as $id => $data){
  	  $m = Main::getInstance()->getServer()->getWorldManager();
  	  $from = $data["From"];
  	  $from = new Position($from["X"], $from["Y"], $from["Z"], $m->getWorldByName($from["World"]));
  	  $to = $data["To"];
  	  $to = new Position($to["X"], $to["Y"], $to["Z"], $m->getWorldByName($to["World"]));
  	  if (!$data["Particle"])continue;
  	  for ($i = 0; $i < 10; $i++) {
  	    $fromParticle = $from->add(
  	      mt_rand(-5, 5) / 10,
  	      mt_rand(-5, 5) / 5,
  	      mt_rand(-5, 5) / 10
  	    );
  	    $from->getWorld()->addParticle($fromParticle, new DustParticle(DyeColor::RED()->getRgbValue()));
  	  }
  	  for ($i = 0; $i < 10; $i++) {
  	    $toParticle = $to->add(
  	      mt_rand(-5, 5) / 10,
  	      mt_rand(-5, 5) / 5,
  	      mt_rand(-5, 5) / 10
  	   );
  	   $to->getWorld()->addParticle($toParticle, new DustParticle(DyeColor::ORANGE()->getRgbValue()));
  	  }
    }
	}
}
