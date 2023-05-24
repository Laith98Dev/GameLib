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

use Closure;
use pocketmine\player\Player;
use TypeError;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\arena\modes\ArenaMode;
use vp817\GameLib\arena\states\ArenaStates;
use vp817\GameLib\event\PlayerJoinArenaEvent;
use vp817\GameLib\event\PlayerQuitArenaEvent;
use vp817\GameLib\managers\TeamManager;
use vp817\GameLib\player\ArenaPlayer;
use vp817\GameLib\util\Team;
use function is_array;
use function is_object;
use function str_replace;
use function strtolower;

class DuoMode extends ArenaMode
{

	/** @var TeamManager $teamManager */
	private TeamManager $teamManager;

	/**
	 * @param mixed ...$arguments
	 * @return void
	 * @throws TypeError
	 */
	public function init(mixed ...$arguments): void
	{
		$teams = $arguments[0];
		$arena = $arguments[1];

		if (!is_array($teams)) {
			throw new TypeError("The teams is invalid");
		}

		if (!is_object($arena)) {
			throw new TypeError("The arena is not an object");
		}

		if (!$arena instanceof Arena) {
			throw new TypeError("The arena is invalid");
		}

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
		return $this->teamManager->isPlayerInATeamFromBytes($bytes);
	}

	/**
	 * @return ArenaPlayer[]
	 */
	public function getPlayers(): array
	{
		return $this->teamManager->getTeamsPlayers();
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
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function onJoin(Arena $arena, Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$bytes = $player->getUniqueId()->getBytes();
		$arenaMessages = $arena->getMessages();

		if ($this->hasPlayer($bytes)) {
			$player->sendMessage($arenaMessages->PlayerAlreadyInsideAnArena());

			if (!is_null($onFail)) {
				$onFail();
			}
			return;
		}
		if ($this->getPlayerCount() > $this->getMaxPlayers()) {
			$player->sendMessage($arenaMessages->ArenaIsFull());

			if (!is_null($onFail)) {
				$onFail();
			}
			return;
		}
		if ($arena->getState()->equals(ArenaStates::INGAME())) {
			$player->sendMessage($arenaMessages->ArenaIsAlreadyRunning());

			if (!is_null($onFail)) {
				$onFail();
			}
			return;
		}

		$this->teamManager->addPlayerToRandomTeam($player, function (ArenaPlayer $player, Team $team) use ($arena, $arenaMessages, $bytes, $onSuccess): void {
			$event = new PlayerJoinArenaEvent($player, $arena);
			$event->call();

			$arenaPlayer = $event->getPlayer();
			$cells = $arenaPlayer->getCells();

			$arenaPlayer->setAll();

			$cells->teleport($arena->getLobbySettings()->getLocation());
			$cells->sendMessage(str_replace(["%name%", "%current%", "%max%"], [$arenaPlayer->getDisplayName(), $this->getPlayerCount(), $this->getMaxPlayers()], $arenaMessages->SucessfullyJoinedArena()));

			if (!is_null($onSuccess)) {
				$onSuccess();
			}
		});
	}

	/**
	 * @param Arena $arena
	 * @param Player $player
	 * @param mixed ...$arguments
	 * @return void
	 */
	public function onQuit(Arena $arena, Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$bytes = $player->getUniqueId()->getBytes();
		$arenaMessages = $arena->getMessages();

		if (!$this->hasPlayer($bytes)) {
			$player->sendMessage($arenaMessages->NotInsideAnArenaToLeave());

			if (!is_null($onFail)) {
				$onFail();
			}
			return;
		}

		if ($arena->getState()->equals(ArenaStates::INGAME()) || $arena->getState()->equals(ArenaStates::RESTARTING())) {
			$player->sendMessage($arenaMessages->CantLeaveDueToState());

			if (!is_null($onFail)) {
				$onFail();
			}
			return;
		}

		$this->teamManager->getTeamOfPlayerFromBytes($bytes, function (Team $team) use ($arena, $arenaMessages, $bytes, $onSuccess): void {
			$team->getPlayer($bytes, function (ArenaPlayer $player) use ($team, $arena, $arenaMessages, $bytes, $onSuccess): void {
				$event = new PlayerQuitArenaEvent($player, $arena);
				$event->call();

				$arenaPlayer = $event->getPlayer();

				$arenaPlayer->setAll(true);

				$team->removePlayer($bytes, function () use ($arena, $arenaMessages, $arenaPlayer, $onSuccess): void {
					$cells = $arenaPlayer->getCells();

					$cells->teleport($arena->getGameLib()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
					$cells->sendMessage(str_replace(["%name%", "%current%", "%max%"], [$arenaPlayer->getDisplayName(), $this->getPlayerCount(), $this->getMaxPlayers()], $arenaMessages->SucessfullyLeftArena()));
	
					if (!is_null($onSuccess)) {
						$onSuccess();
					}
				});
			});
		});
	}


	/**
	 * @param Arena $arena
	 * @param array $spawns
	 * @return void
	 */
	public function setupSpawns(Arena $arena, array $spawns): void
	{
		$players = $this->getPlayers();
		foreach ($players as $key => $player) {
			$this->teamManager->getTeamOfPlayerFromBytes($player->getCells()->getUniqueId()->getBytes(), function (Team $team) use ($arena, $spawns, $player): void {
				// TODO: EVENT?
				$player->getCells()->teleport($arena->getLocationOfSpawn($spawns[strtolower($team->getName())]));
			});
		}
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
