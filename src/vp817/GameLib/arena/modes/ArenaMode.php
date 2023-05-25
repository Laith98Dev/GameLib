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

namespace vp817\GameLib\arena\modes;

use Closure;
use pocketmine\player\Player;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\player\ArenaPlayer;

abstract class ArenaMode
{

	/**
	 * @return string
	 */
	public function name(): string
	{
		return str_replace("mode", "", basename(strtolower(static::class), ";"));
	}

	/**
	 * @param mixed ...$arguments
	 * @return void
	 */
	abstract public function init(mixed ...$arguments): void;

	/**
	 * @param object $value
	 * @return bool
	 */
	public function equals(object $value): bool
	{
		return $value instanceof static;
	}

	/**
	 * @return bool
	 */
	public function isTeamMode(): bool
	{
		return $this instanceof TeamModeAbstract;
	}

	/**
	 * @param string $bytes
	 * @return bool
	 */
	abstract public function hasPlayer(string $bytes): bool;

	/**
	 * @return ArenaPlayer[]
	 */
	abstract public function getPlayers(): array;

	/**
	 * @return int
	 */
	public function getPlayerCount(): int
	{
		return count($this->getPlayers());
	}

	/**
	 * @return int
	 */
	abstract public function getMaxPlayersPerTeam(): int;

	/**
	 * @return int
	 */
	abstract public function getMaxPlayers(): int;

	/**
	 * @param Arena $arena
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	abstract public function onJoin(Arena $arena, Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void;

	/**
	 * @param Arena $arena
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @param bool $notifyPlayers
	 * @return void
	 */
	abstract public function onQuit(Arena $arena, Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null, bool $notifyPlayers = true): void;

	/**
	 * @param Arena $arena
	 * @param array $spawns
	 * @return void
	 */
	abstract public function sendPlayersToTheirSpawn(Arena $arena, array $spawns): void;

	/**
	 * @param Arena $arena
	 * @return void
	 */
	abstract public function endGame(Arena $arena): void;
}
