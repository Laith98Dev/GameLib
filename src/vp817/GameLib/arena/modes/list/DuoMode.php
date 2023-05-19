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

namespace vp817\GameLib\arena\modes\list;

use pocketmine\player\Player;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\arena\modes\ArenaMode;
use vp817\GameLib\managers\TeamManager;
use vp817\GameLib\player\ArenaPlayer;

class DuoMode extends ArenaMode
{

	/** @var TeamManager $teamManager */
	private TeamManager $teamManager;

	/**
	 * @param mixed ...$arguments
	 * @return void
	 */
	public function init(mixed ...$arguments): void
	{
		$teams = $arguments[0];
		$arena = $arguments[1];

		$this->teamManager = new TeamManager($arena);
		foreach ($teams as $key => $value) {
			$this->teamManager->addTeam($value);
		}
	}

	/**
	 * @param string $bytes
	 * @return bool
	 */
	public function hasPlayer(string $bytes): bool
	{
		return false; // TODO
	}

	/**
	 * @return ArenaPlayer[]
	 */
	public function getPlayers(): array
	{
		return []; // TODO
	}

	/**
	 * @return int
	 */
	public function getMaxPlayersPerTeam(): int
	{
		return 2;
	}

	/**
	 * @return int
	 */
	public function getMaxPlayers(): int
	{
		return count($this->teamManager->getTeams()) * $this->getMaxPlayersPerTeam();
	}

	/**
	 * @param Arena $arena
	 * @param Player $player
	 * @return void
	 */
	public function onJoin(Arena $arena, Player $player): void
	{
	}

	/**
	 * @param Arena $arena
	 * @param Player $player
	 * @param mixed ...$arguments
	 * @return void
	 */
	public function onQuit(Arena $arena, Player $player): void
	{
	}


	/**
	 * @param Arena $arena
	 * @param array $spawns
	 * @return void
	 */
	public function setupSpawns(Arena $arena, array $spawns): void
	{
		
	}

	/**
	 * @param Arena $arena
	 * @return void
	 */
	public function endGame(Arena $arena): void
	{
		// TODO
	}
}
