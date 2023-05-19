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

namespace vp817\GameLib\tasks;

use pocketmine\scheduler\Task;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\arena\states\ArenaStates;

class ArenaTickTask extends Task
{

	/**
	 * @param Arena $arena
	 * @param int $countdownTime
	 * @param int $arenaTime
	 * @param int $restartingTime
	 */
	public function __construct(private Arena $arena, private int $countdownTime, private int $arenaTime, private int $restartingTime)
	{
	}

	/**
	 * @return void
	 */
	public function onRun(): void
	{
		$arena = $this->arena;
		$state = $arena->getState();

		if ($state->equals(ArenaStates::WAITING())) {
			$state->tick($arena, 0);
		} else if ($state->equals(ArenaStates::COUNTDOWN())) {
			$this->countdownTime = $state->tick($arena, $this->countdownTime);
		} else if ($state->equals(ArenaStates::INGAME())) {
			$this->arenaTime = $state->tick($arena, $this->arenaTime);
		} else if ($state->equals(ArenaStates::RESTARTING())) {
			$this->restartingTime = $state->tick($arena, $this->restartingTime);
		} else if ($state->equals(ArenaStates::RESETTING())) {
			$state->tick($arena, 0);
		}
	}
}
