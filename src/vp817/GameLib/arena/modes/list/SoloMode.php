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
use vp817\GameLib\arena\states\ArenaStates;
use vp817\GameLib\event\PlayerJoinArenaEvent;
use vp817\GameLib\event\PlayerQuitArenaEvent;
use vp817\GameLib\managers\PlayerManager;
use function is_null;

class SoloMode extends ArenaMode
{

	/** @var PlayerManager $playerManager */
	private PlayerManager $playerManager;

	/**
	 * initialize arena solo mode
	 */
	public function __construct()
	{
		$this->playerManager = new PlayerManager();
	}

	/**
	 * @return int
	 */
	public function getMaxPlayersPerTeam(): int
	{
		return 1;
	}

	/**
	 * @param Arena $arena
	 * @param Player $player
	 * @param mixed ...$arguments
	 * @return void
	 */
	public function onJoin(Arena $arena, Player $player, ...$arguments): void
	{
		$bytes = $player->getUniqueId()->getBytes();
		$arenaMessages = $arena->getMessages();

		if ($this->playerManager->has($bytes)) {
			$player->sendMessage($arenaMessages->PlayerAlreadyInsideAnArena());
			return;
		}
		if (count($this->playerManager->getAll()) > $this->getMaxPlayersPerTeam() + 1) {
			$player->sendMessage($arenaMessages->ArenaIsFull());
			return;
		}
		if ($arena->getState() === ArenaStates::INGAME()) {
			$player->sendMessage($arenaMessages->ArenaIsAlreadyRunning());
			return;
		}

		$arenaPlayer = $this->playerManager->add($player);

		if (is_null($arenaPlayer)) { // shouldnt happen
			$player->sendMessage($arenaMessages->PlayerAlreadyInsideAnArena());
			return;
		}

		(new PlayerJoinArenaEvent($arenaPlayer, $arena))->call();

		$arenaPlayer->setAll();

		$player->teleport($arena->getLobbySettings()->getLocation());

		$player->sendMessage($arenaMessages->SucessfullyJoinedArena());
	}

	/**
	 * @param Arena $arena
	 * @param Player $player
	 * @param mixed ...$arguments
	 * @return void
	 */
	public function onQuit(Arena $arena, Player $player, ...$arguments): void
	{
		$bytes = $player->getUniqueId()->getBytes();
		$arenaMessages = $arena->getMessages();

		if (!$this->playerManager->has($bytes)) {
			$player->sendMessage($arenaMessages->NotInsideAnArenaToLeave());
			return;
		}

		if ($arena->getState() === ArenaStates::INGAME() || $arena->getState() === ArenaStates::RESTARTING()) {
			$player->sendMessage($arenaMessages->CantLeaveDueToState());
			return;
		}

		$arenaPlayer = $this->playerManager->get($bytes);

		if (is_null($arenaPlayer)) { // shouldnt happen
			$player->sendMessage($arenaMessages->NotInsideAnArenaToLeave());
			return;
		}

		(new PlayerQuitArenaEvent($arenaPlayer, $arena))->call();
		
		$arenaPlayer->setAll(true);

		$player->teleport($arena->getGameLib()->getWorldManager()->getDefaultWorld()->getSpawnLocation());

		$player->sendMessage($arenaMessages->SucessfullyLeftArena());
	}
}
