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

namespace vp817\GameLib\player;

use pocketmine\player\Player;

final class PlayerTeam
{

	/** @var string $name */
	private string $name;
	/** @var string $color */
	private string $color;
	/** @var array $players */
	private array $players = [];
	/** @var int $maxPlayers */
	private int $maxPlayers = -1;

	/**
	 * @param string $name
	 * @param string $color
	 */
	public function __construct(string $name, string $color)
	{
		$this->name = $name;
		$this->color = $color;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getColor(): string
	{
		return $this->color;
	}

	/**
	 * @param Player $player
	 * @return void
	 */
	public function addPlayer(Player $player): void
	{
		$bytes = $player->getUniqueId()->getBytes();
		if ($this->hasPlayer($bytes)) {
			return;
		}
		$this->players[$bytes] = $player;
	}

	/**
	 * @param string $bytes
	 * @return void
	 */
	public function removePlayer(string $bytes): void
	{
		if (!$this->hasPlayer($bytes)) {
			return;
		}
		unset($this->players[$bytes]);
	}

	/**
	 * @param string $bytes
	 * @return null|Player
	 */
	public function getPlayer(string $bytes): ?Player
	{
		if (!$this->hasPlayer($bytes)) {
			return null;
		}
		return $this->players[$bytes];
	}

	/**
	 * @param string $bytes
	 * @return bool
	 */
	public function hasPlayer(string $bytes): bool
	{
		if (!array_key_exists($bytes, $this->players)) {
			return false;
		}
		return true;
	}

	/**
	 * @param int $value
	 * @return void
	 */
	public function setMaxPlayers(int $value): void
	{
		$this->maxPlayers = $value;
	}

	/**
	 * @return int
	 */
	public function getMaxPlayers(): int
	{
		return $this->maxPlayers;
	}

	/**
	 * @return array
	 */
	public function getPlayers(): array
	{
		return $this->players;
	}
}