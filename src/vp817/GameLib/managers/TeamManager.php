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
use vp817\GameLib\utils\Team;
use function array_filter;
use function array_key_exists;
use function array_merge;
use function array_rand;
use function count;
use function is_null;
use function strtolower;

final class TeamManager
{

	private array $list = [];

	/**
	 * @param Arena $arena
	 */
	public function __construct(
		private Arena $arena
	) {
	}

	/**
	 * @param Team $team
	 * @param Closure|null $onSuccess
	 * @param Closure|null $onFail
	 * @return void
	 */
	public function addTeam(Team $team, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$name = strtolower($team->getName());
		if ($this->hasTeam(name: $name)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		$this->list[strtolower($name)] = $team;

		if (!is_null($onSuccess)) $onSuccess();
	}

	/**
	 * @param string $name
	 * @param Closure|null $onSuccess
	 * @param Closure|null $onFail
	 * @return void
	 */
	public function removeTeam(string $name, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		if (!$this->hasTeam(name: $name)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		unset($this->list[strtolower($name)]);

		if (!is_null($onSuccess)) $onSuccess();
	}

	/**
	 * @param string $name
	 * @param Closure $onSuccess
	 * @param Closure|null $onFail
	 * @return void
	 */
	public function getTeam(string $name, Closure $onSuccess, ?Closure $onFail = null): void
	{
		if (!$this->hasTeam(name: $name)) {
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

		foreach ($this->getTeams() as $team) {
			$players = array_merge($players, $team->getPlayers());
		}

		return $players;
	}

	/**
	 * @param Player $player
	 * @param string $teamName
	 * @param Closure|null $onSuccess
	 * @param Closure|null $onFail
	 * @return void
	 */
	public function addPlayerToTeam(Player $player, string $teamName, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		if ($this->isPlayerInATeamFromBytes(bytes: $player->getUniqueId()->getBytes())) {
			return;
		}

		$this->getTeam(
			name: $teamName,
			onSuccess: static function (Team $team) use ($player, $onSuccess, $onFail): void {
				$team->addPlayer($player, $onSuccess, $onFail);
			},
			onFail: $onFail
		);
	}

	/**
	 * @param Player $player
	 * @param Closure|null $onSuccess
	 * @param Closure|null $onFail
	 * @return void
	 */
	public function addPlayerToRandomTeam(Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$maxPlayersPerTeam = $this->arena->getMode()->getMaxPlayersPerTeam();
		$availableTeams = array_filter($this->list, static function ($value) use ($maxPlayersPerTeam) {
			return count($value->getPlayers()) < $maxPlayersPerTeam;
		});

		if (empty($availableTeams)) {
			if (!is_null($onFail)) $onFail($this->arena->getMessages()->NoTeamsAvailable());
			return;
		}

		$team = $availableTeams[array_rand($availableTeams)];
		$team->addPlayer(
			player: $player,
			onSuccess: fn (ArenaPlayer $player) => !is_null($onSuccess) ? $onSuccess($player, $team) : null
		);
	}

	/**
	 * @param string $bytes
	 * @return bool
	 */
	public function isPlayerInATeamFromBytes(string $bytes): bool
	{
		foreach ($this->list as $team) {
			if ($team->hasPlayer(bytes: $bytes)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $bytes
	 * @param Closure $onSuccess
	 * @param Closure|null $onFail
	 * @return void
	 */
	public function getTeamOfPlayerFromBytes(string $bytes, Closure $onSuccess, ?Closure $onFail = null): void
	{
		foreach ($this->list as $team) {
			if ($team->hasPlayer(bytes: $bytes)) {
				$onSuccess($team);
				return;
			}
		}

		if (!is_null($onFail)) {
			$onFail();
		}
	}
}
