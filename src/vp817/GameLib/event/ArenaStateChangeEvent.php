<?php

/**
 *   .oooooo.                                          ooooo         o8o   .o8
 *  d8P'  `Y8b                                         `888'         `"'  "888
 * 888            .oooo.   ooo. .oo.  .oo.    .ooooo.   888         oooo   888oooo.
 * 888           `P  )88b  `888P"Y88bP"Y88b  d88' `88b  888         `888   d88' `88b
 * 888     ooooo  .oP"888   888   888   888  888ooo888  888          888   888   888
 * `88.    .88'  d8(  888   888   888   888  888    .o  888       o  888   888   888
 *  `Y8bood8P'   `Y888""8o o888o o888o o888o `Y8bod8P' o888ooooood8 o888o  `Y8bod8P'
 * 
 * @author vp817, Laith98Dev
 * 
 * Copyright (C) 2023  vp817
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace vp817\GameLib\event;

use pocketmine\event\Event;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\arena\states\ArenaState;

class ArenaStateChangeEvent extends Event
{

	/**
	 * @param Arena $arena
	 * @param ArenaState $oldState
	 * @param ArenaState $newState
	 * @param int $timer
	 */
	public function __construct(
		protected Arena $arena,
		protected ArenaState $oldState,
		private ArenaState $newState
	) {
	}

	/**
	 * @return Arena
	 */
	public function getArena(): Arena
	{
		return $this->arena;
	}

	/**
	 * @return ArenaState
	 */
	public function getOldState(): ArenaState
	{
		return $this->oldState;
	}
	
	/**
	 * @return ArenaState
	 */
	public function getNewState(): ArenaState
	{
		return $this->newState;
	}
}
