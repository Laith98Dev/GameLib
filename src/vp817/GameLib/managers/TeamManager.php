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

namespace vp817\GameLib\managers;

use Closure;
use pocketmine\player\Player;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\player\ArenaPlayer;
use vp817\GameLib\util\Team;
use function array_filter;
use function array_key_exists;
use function array_rand;
use function array_shift;
use function count;
use function is_null;
use function strtolower;

final class TeamManager
{
	/** @var array $list */
	private array $list = [];
	/** @var Arena $arena */
	private Arena $arena;

	/**
	 * @param Arena $arena
	 */
	public function __construct(Arena $arena)
	{
		$this->arena = $arena;
	}

	/**
	 * @param Team $team
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function addTeam(Team $team, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$name = strtolower($team->getName());
		if ($this->hasTeam($name)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		$this->list[strtolower($name)] = $team;

		if (!is_null($onSuccess)) $onSuccess();
	}

	/**
	 * @param string $name
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function removeTeam(string $name, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		if (!$this->hasTeam($name)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		unset($this->list[strtolower($name)]);

		if (!is_null($onSuccess)) $onSuccess();
	}

	/**
	 * @param string $name
	 * @param Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function getTeam(string $name, Closure $onSuccess, ?Closure $onFail = null): void
	{
		if (!$this->hasTeam($name)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		$onSuccess($this->list[strtolower($name)]);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasTeam(string $name): bool
	{
		return array_key_exists(strtolower($name), $this->list);
	}

	/**
	 * @return Team[]
	 */
	public function getTeams(): array
	{
		return $this->list;
	}

	/**
	 * @return ArenaPlayer[]
	 */
	public function getTeamsPlayers(): array
	{
		$players = [];

		foreach ($this->getTeams() as $name => $team) {
			if (count($team->getPlayers()) < 1) continue;

			foreach ($team->getPlayers() as $bytes => $player) {
				$players[] = $player;
			}
		}

		return $players;
	}

	/**
	 * @param Player $player
	 * @param string $teamName
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function addPlayerToTeam(Player $player, string $teamName, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		if ($this->isPlayerInATeamFromBytes($player->getUniqueId()->getBytes())) {
			return;
		}

		$this->getTeam($teamName, function (Team $team) use ($player, $onSuccess, $onFail): void {
			$team->addPlayer($player, $onSuccess, $onFail);
		}, $onFail);
	}

	/**
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function addPlayerToRandomTeam(Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$maxPlayersPerTeam = $this->arena->getMode()->getMaxPlayersPerTeam();
		$availableTeams = array_filter($this->list, function ($value) use ($maxPlayersPerTeam) {
			return count($value->getPlayers()) < $maxPlayersPerTeam + 1;
		});
		$availableTeamsCount = count($availableTeams);

		if ($availableTeamsCount > 0 && $availableTeamsCount < 2) {
			$team = array_shift($availableTeams);
		} else if ($availableTeamsCount > 1) {
			$team = $availableTeams[array_rand($availableTeams)];
		} else {
			if (!is_null($onFail)) $onFail($this->arena->getMessages()->NoTeamsAvailable());
			return;
		}

		$team->addPlayer($player, function (ArenaPlayer $player) use ($onSuccess, $team): void {
			if (!is_null($onSuccess)) $onSuccess($player, $team);
		});
	}

	/**
	 * @param string $bytes
	 * @return bool
	 */
	public function isPlayerInATeamFromBytes(string $bytes): bool
	{
		$retVal = false;

		foreach ($this->list as $teamName => $team) {
			if (!$team->hasPlayer($bytes)) continue;

			$retVal = true;
		}

		return $retVal;
	}

	/**
	 * @param string $bytes
	 * @param Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function getTeamOfPlayerFromBytes(string $bytes, Closure $onSuccess, ?Closure $onFail = null): void
	{
		$retVal = null;

		foreach ($this->list as $teamName => $team) {
			if (!$team->hasPlayer($bytes)) continue;
			
			$retVal = $team;
		}

		if (is_null($retVal)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		$onSuccess($retVal);
	}
}
