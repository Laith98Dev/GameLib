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
use vp817\GameLib\GameLib;
use vp817\GameLib\managers\PlayerManager;
use vp817\GameLib\player\ArenaPlayer;
use function is_null;
use function is_int;
use function is_object;
use function str_replace;

class SoloMode extends ArenaMode
{

	/** @var PlayerManager $playerManager */
	private PlayerManager $playerManager;
	/** @var GameLib $gamelib */
	private GameLib $gamelib;
	/** @var int $slots */
	private int $slots;

	/**
	 * @param ...$arguments
	 * @return void
	 * @throws TypeError
	 */
	public function init(mixed ...$arguments): void
	{
		$slots = $arguments[0];
		$gamelib = $arguments[1];

		if (!is_int($slots)) {
			throw new TypeError("The slots is invalid");
		}

		if (!is_object($gamelib)) {
			throw new TypeError("The arena is not an object");
		}

		if (!$gamelib instanceof GameLib) {
			throw new TypeError("The gamelib is invalid");
		}

		$this->playerManager = new PlayerManager();
		$this->gamelib = $gamelib;
		$this->slots = $slots;
	}

	/**
	 * @param string $bytes
	 * @return bool
	 */
	public function hasPlayer(string $bytes): bool
	{
		return $this->playerManager->has($bytes);
	}

	/**
	 * @return ArenaPlayer[]
	 */
	public function getPlayers(): array
	{
		return $this->playerManager->getAll();
	}

	/**
	 * @return int
	 */
	public function getMaxPlayersPerTeam(): int
	{
		return 1;
	}

	/**
	 * @return int
	 */
	public function getMaxPlayers(): int
	{
		return $this->slots;
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

			if (!is_null($onFail)) $onFail();
			return;
		}
		if ($this->getPlayerCount() > $this->getMaxPlayers()) {
			$player->sendMessage($arenaMessages->ArenaIsFull());

			if (!is_null($onFail)) $onFail();
			return;
		}
		if ($arena->getState()->equals(ArenaStates::INGAME())) {
			$player->sendMessage($arenaMessages->ArenaIsAlreadyRunning());

			if (!is_null($onFail)) $onFail();
			return;
		}

		$this->playerManager->add($player, function (ArenaPlayer $player) use ($arena, $arenaMessages, $bytes, $onSuccess): void {
			$event = new PlayerJoinArenaEvent($player, $arena);
			$event->call();

			$arenaPlayer = $event->getPlayer();
			$cells = $arenaPlayer->getCells();

			$arenaPlayer->setAll();

			$cells->teleport($arena->getLobbySettings()->getLocation());
			$arena->getMessageBroadcaster()->broadcastMessage(str_replace(["%name%", "%current%", "%max%"], [$arenaPlayer->getDisplayName(), $this->getPlayerCount(), $this->getMaxPlayers()], $arenaMessages->SucessfullyJoinedArena()));

			if (!is_null($onSuccess)) $onSuccess();
		});
	}

	/**
	 * @param Arena $arena
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function onQuit(Arena $arena, Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$bytes = $player->getUniqueId()->getBytes();
		$arenaMessages = $arena->getMessages();

		if (!$this->hasPlayer($bytes)) {
			$player->sendMessage($arenaMessages->NotInsideAnArenaToLeave());

			if (!is_null($onFail)) $onFail();
			return;
		}

		if ($arena->getState()->equals(ArenaStates::INGAME()) || $arena->getState()->equals(ArenaStates::RESTARTING())) {
			$player->sendMessage($arenaMessages->CantLeaveDueToState());

			if (!is_null($onFail)) $onFail();
			return;
		}

		$this->playerManager->get($bytes, function (ArenaPlayer $player) use ($arena, $arenaMessages, $bytes, $onSuccess): void {
			$event = new PlayerQuitArenaEvent($player, $arena);
			$event->call();

			$arenaPlayer = $event->getPlayer();

			$arenaPlayer->setAll(true);

			$this->playerManager->remove($bytes, function () use ($arena, $arenaMessages, $arenaPlayer, $onSuccess): void {
				$cells = $arenaPlayer->getCells();

				$cells->teleport($this->gamelib->getWorldManager()->getDefaultWorld()->getSpawnLocation());
				$arena->getMessageBroadcaster()->broadcastMessage(str_replace(["%name%", "%current%", "%max%"], [$arenaPlayer->getDisplayName(), $this->getPlayerCount(), $this->getMaxPlayers()], $arenaMessages->SucessfullyLeftArena()));
	
				if (!is_null($onSuccess)) $onSuccess();
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
		$players = $this->playerManager->getAll(true);
		for ($i = 1; $i <= $this->getMaxPlayers(); ++$i) {
			$player = $players[$i - 1];
			// TODO: EVENT?
			$player->getCells()->teleport($arena->getLocationOfSpawn($spawns[$i]));
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
