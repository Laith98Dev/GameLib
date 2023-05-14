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

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\player\PlayerTeam;
use function strtolower;
use function array_key_exists;
use function array_filter;
use function count;
use function array_shift;
use function array_rand;

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
	 * @param string $name
	 * @param string $color
	 */
	public function addTeam(string $name, string $color): void
	{
		if ($this->hasTeam($name)) {
			return;
		}

		$this->list[strtolower($name)] = new PlayerTeam($name, $color);
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function removeTeam(string $name): void
	{
		if (!$this->hasTeam($name)) {
			return;
		}

		unset($this->list[strtolower($name)]);
	}

	/**
	 * @param string $name
	 * @return null|PlayerTeam
	 */
	public function getTeam(string $name): ?PlayerTeam
	{
		if (!$this->hasTeam($name)) {
			return null;
		}
		return $this->list[strtolower($name)];
	}

	/**
	 * @param string $name
	 * @return null|PlayerTeam
	 */
	public function hasTeam(string $name): bool
	{
		if (!array_key_exists(strtolower($name), $this->list)) {
			return false;
		}
		return true;
	}

	/**
	 * @param Player $player
	 * @param string $team
	 * @return void
	 */
	public function addPlayerToTeam(Player $player, string $team): void
	{
		if ($this->isPlayerInATeam($player)) {
			return;
		}

		$this->getTeam($team)->addPlayer($player);
	}

	/**
	 * @param Player $player
	 * @param bool $sendNoTeamsMsg
	 * @return void
	 */
	public function addPlayerToRandomTeam(Player $player, bool $sendNoTeamsMsg = true): void
	{
		$maxPlayersPerTeam = $this->arena->getMaxPlayersPerTeam();
		$availableTeams = array_filter($this->list, function ($value) use ($maxPlayersPerTeam) {
			return count($value->getPlayers()) < $maxPlayersPerTeam + 1;
		});
		$availableTeamsCount = count($availableTeams);

		if ($availableTeamsCount > 0) {
			if ($availableTeamsCount < 2) {
				$team = array_shift($availableTeams);
			} else if ($availableTeamsCount > 1) {
				$team = $availableTeams[array_rand($availableTeams)];
			}
		} else {
			if ($sendNoTeamsMsg) {
				$player->sendMessage(TextFormat::RED . "No teams available");
			}
			return;
		}

		$team->addPlayer($player);
	}

	/**
	 * @param Player $player
	 * @return bool
	 */
	public function isPlayerInATeam(Player $player): bool
	{
		foreach ($this->list as $teamName => $team) {
			if (!$team->hasPlayer($player->getUniqueId()->getBytes())) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param Player $player
	 * @return null|PlayerTeam
	 */
	public function getPlayerTeam(Player $player): ?PlayerTeam
	{
		foreach ($this->list as $teamName => $team) {
			if ($team->hasPlayer($player->getUniqueId()->getBytes())) {
				return $team;
			}
		}
		return null;
	}
}
