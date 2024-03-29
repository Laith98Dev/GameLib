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
use vp817\GameLib\arena\states\ArenaState;
use vp817\GameLib\arena\states\ArenaStates;

class ArenaTickTask extends Task
{

	private array $timeCache = [];

	/**
	 * @param Arena $arena
	 * @param int $countdownTime
	 * @param int $arenaTime
	 * @param int $restartTime
	 */
	public function __construct(
		private Arena $arena,
		private int $countdownTime,
		private int $arenaTime,
		private int $restartTime
	) {
		$this->timeCache = [
			"countdownTime" => $countdownTime,
			"arenaTime" => $arenaTime,
			"restartTime" => $restartTime
		];
	}

	/**
	 * @return int
	 */
	public function getCountdownTime(): int
	{
		return $this->countdownTime;
	}

	/**
	 * @return int
	 */
	public function getArenaTime(): int
	{
		return $this->arenaTime;
	}

	/**
	 * @return int
	 */
	public function getRestartTime(): int
	{
		return $this->restartTime;
	}

	/**
	 * @return void
	 */
	public function decrementCountdownTime(): void
	{
		--$this->countdownTime;
	}

	/**
	 * @return void
	 */
	public function decrementArenaTime(): void
	{
		--$this->arenaTime;
	}

	/**
	 * @return void
	 */
	public function decrementRestartTime(): void
	{
		--$this->restartTime;
	}

	/**
	 * @return void
	 */
	public function resetCountdownTime(): void
	{
		$this->countdownTime = $this->timeCache["countdownTime"];
	}

	/**
	 * @return void
	 */
	public function resetArenaTime(): void
	{
		$this->arenaTime = $this->timeCache["arenaTime"];
	}

	/**
	 * @return void
	 */
	public function resetRestartingTime(): void
	{
		$this->restartTime = $this->timeCache["restartTime"];
	}

	/**
	 * @param ArenaState $oldState
	 * @param ArenaState $newState
	 * @return void
	 */
	public function resetCountdownTimeIfRequired(ArenaState $oldState, ArenaState $newState): void
	{
		if ($oldState->equals(ArenaStates::COUNTDOWN()) && $newState->equals(ArenaStates::WAITING())) {
			$this->resetCountdownTime();
		}
	}

	/**
	 * @return void
	 */
	public function reload(): void
	{
		$this->resetCountdownTime();
		$this->resetArenaTime();
		$this->resetRestartingTime();
	}

	/**
	 * @return void
	 */
	public function onRun(): void
	{
		$this->arena->getState()->tick(arena: $this->arena);
	}
}
